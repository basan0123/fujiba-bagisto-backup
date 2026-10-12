#!/usr/bin/env python3
"""WS-L fabric twin audit + tile builder (deterministic; staging only).

    twin_audit.py build-wm  <in.json> <templates.npz>      fixed-position watermark templates
    twin_audit.py audit     <in.json> <out.json>           per-image audit + tile attempts + tier

in.json: {"run_id", "tiles_dir", "wm_templates", "products": [{"product_id", "sku",
          "public_name", "applicable", "images": [{"key","path","kind","sha256"}]}]}
kind: site_own | legacy | drive | drive_cutout | mac | shoot | supplier_ref
supplier_ref images are measured for an internal note only and are NEVER a tile source.

Everything is measured with cv2/numpy + tesseract; nothing is generated. Thresholds and tier
rules live in twin_common.py (pre-registered). Every tile attempt is returned for
fi_twin_attempts, including failures.
"""
import hashlib
import json
import os
import re
import subprocess
import sys
import tempfile
import time
import traceback

import cv2
import numpy as np

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import twin_common as T  # noqa: E402

OWN_KINDS = {'site_own', 'legacy', 'drive', 'drive_cutout', 'mac', 'shoot'}
AN = 1024                       # analysis resolution for masks / regions / OCR geometry
WM_SIZES = {'9x16': (540, 960), '1x1': (700, 700), '3x4': (600, 800), '4x3': (800, 600)}
WM_Z = 9.0                      # calibrated 2026-10-12: 0/20 clean supplier scans >= 9 (max 8.4); 4/8 overlay photos >= 9
WM_Z_CLEAR = 4.0                # below every clean scan seen (min 4.14) -> "no overlay"


def sha256(path):
    h = hashlib.sha256()
    with open(path, 'rb') as f:
        for ch in iter(lambda: f.read(1 << 16), b''):
            h.update(ch)
    return h.hexdigest()


def aspect_group(w, h):
    r = w / float(h)
    for k, rr in (('9x16', 9 / 16), ('1x1', 1.0), ('3x4', 0.75), ('4x3', 4 / 3)):
        if abs(r - rr) < 0.012:
            return k
    return None


def highpass_L(bgr, size):
    img = cv2.resize(bgr, size, interpolation=cv2.INTER_AREA)
    L = cv2.cvtColor(img, cv2.COLOR_BGR2LAB)[..., 0].astype(np.float32)
    return L - cv2.GaussianBlur(L, (0, 0), 5)


# ------------------------------------------------------------------ watermark

def build_wm(paths, out):
    """Mean high-pass per aspect group; a fixed-position overlay survives the mean."""
    groups = {}
    for p in paths:
        img = T.load_bgr(p)
        if img is None:
            continue
        g = aspect_group(img.shape[1], img.shape[0])
        if g is None:
            continue
        hp = highpass_L(img, WM_SIZES[g])
        acc = groups.setdefault(g, [np.zeros(hp.shape[::-1][::-1], np.float64), 0])
        acc[0] += hp
        acc[1] += 1
    save = {}
    info = {}
    for g, (s, n) in groups.items():
        if n < 20:
            continue
        mean = (s / n).astype(np.float32)
        med = float(np.median(mean))
        mad = float(np.median(np.abs(mean - med))) + 1e-6
        z = (mean - med) / (1.4826 * mad)
        strokes = (z > 5).astype(np.uint8)
        # ignore borders (vignetting / frame edges) and the bottom code-sticker zone
        h, w = strokes.shape
        strokes[:int(0.06 * h)] = 0
        strokes[int(0.9 * h):] = 0
        strokes[:, :int(0.04 * w)] = 0
        strokes[:, int(0.96 * w):] = 0
        blob = cv2.dilate(strokes, np.ones((9, 9), np.uint8))
        n_lbl, lbl, st, _ = cv2.connectedComponentsWithStats(blob, 8)
        if n_lbl <= 1:
            continue
        # overlay text = the component with the most stroke pixels, elongated
        best, score = None, 0
        for i in range(1, n_lbl):
            x, y, bw, bh, a = st[i]
            sp = int(strokes[lbl == i].sum())
            if sp > score and max(bw, bh) > 0.18 * w:
                best, score = i, sp
        if best is None:
            continue
        x, y, bw, bh, _ = st[best]
        pad = 8
        x0, y0, x1, y1 = max(0, x - pad), max(0, y - pad), min(w, x + bw + pad), min(h, y + bh + pad)
        tpl = mean[y0:y1, x0:x1]
        save[f'{g}_tpl'] = tpl
        save[f'{g}_box'] = np.array([x0, y0, x1, y1])
        save[f'{g}_mask'] = (strokes[y0:y1, x0:x1] > 0).astype(np.uint8)
        info[g] = {'n_photos': n, 'box': [int(x0), int(y0), int(x1), int(y1)], 'stroke_px': int(score)}
    np.savez_compressed(out, **save)
    return info


class WM:
    def __init__(self, path):
        self.t = {}
        if path and os.path.exists(path):
            z = np.load(path)
            for g in WM_SIZES:
                if f'{g}_tpl' in z:
                    # match on the stroke shape (the mean map's background is just averaged noise)
                    tpl = cv2.GaussianBlur(z[f'{g}_mask'].astype(np.float32), (0, 0), 1.0)
                    self.t[g] = (tpl, z[f'{g}_box'], z[f'{g}_mask'])

    def detect(self, bgr):
        """Slide the overlay stroke templates (3 scales x 3 angles) over the whole high-pass map.

        z = (best score - mean) / std of the score map. Calibrated on supplier scans (known
        clean) vs owner photos with a visible overlay; threshold WM_Z. No template hit means
        'not found', which is reported as unknown (None) unless the framing is a known group."""
        if not self.t:
            return {'method': 'no_templates', 'present': None}
        h, w = bgr.shape[:2]
        g = aspect_group(w, h) or ('9x16' if w < h else '4x3')
        size = WM_SIZES.get(g, (int(round(800 * w / max(h, w))), int(round(800 * h / max(h, w)))))
        hp = highpass_L(bgr, size)
        best = None
        for tg, (tpl0, box, _) in self.t.items():
            for sc in (0.8, 1.0, 1.25):
                for ang in (-8, 0, 8):
                    t = cv2.resize(tpl0, None, fx=sc, fy=sc, interpolation=cv2.INTER_LINEAR)
                    if ang:
                        M = cv2.getRotationMatrix2D((t.shape[1] / 2, t.shape[0] / 2), ang, 1.0)
                        t = cv2.warpAffine(t, M, (t.shape[1], t.shape[0]))
                    if t.shape[0] >= hp.shape[0] or t.shape[1] >= hp.shape[1]:
                        continue
                    r = cv2.matchTemplate(hp, t, cv2.TM_CCOEFF_NORMED)
                    mx = float(r.max())
                    z = (mx - float(r.mean())) / (float(r.std()) + 1e-6)
                    if best is None or z > best[0]:
                        yy, xx = np.unravel_index(int(r.argmax()), r.shape)
                        best = (z, mx, tg, sc, ang, xx, yy, t.shape)
        if best is None:
            return {'method': 'template_scan', 'present': None}
        z, mx, tg, sc, ang, xx, yy, ts = best
        fx, fy = w / size[0], h / size[1]
        present = bool(z >= WM_Z)
        return {'method': 'template_scan', 'score': round(mx, 3), 'z': round(z, 2), 'present': present, 'tpl': tg,
                'scale': sc, 'angle': ang, 'box': [int(xx * fx), int(yy * fy), int((xx + ts[1]) * fx), int((yy + ts[0]) * fy)]}


def ocr_words(bgr):
    """tesseract eng psm 11 on a CLAHE copy; returns words with boxes in ORIGINAL pixels."""
    img, s = T.resize_max(bgr, 1600)
    L = cv2.cvtColor(img, cv2.COLOR_BGR2LAB)[..., 0]
    v = cv2.createCLAHE(3.0, (8, 8)).apply(L)
    with tempfile.TemporaryDirectory() as td:
        fn = os.path.join(td, 'o.png')
        cv2.imwrite(fn, v)
        try:
            out = subprocess.run(['tesseract', fn, 'stdout', '-l', 'eng', '--psm', '11', 'tsv'],
                                 capture_output=True, text=True, timeout=90).stdout
        except subprocess.TimeoutExpired:
            return None
    words = []
    for line in out.splitlines()[1:]:
        c = line.split('\t')
        if len(c) != 12 or not c[11].strip():
            continue
        conf = float(c[10])
        if conf < 40:
            continue
        x, y, w, h = [int(c[i]) / s for i in (6, 7, 8, 9)]
        words.append({'t': c[11].strip(), 'conf': int(conf), 'box': [int(x), int(y), int(x + w), int(y + h)]})
    return words


def _lev(a, b):
    a, b = a.lower(), b.lower()
    d = list(range(len(b) + 1))
    for i, ca in enumerate(a, 1):
        p, d[0] = d[0], i
        for j, cb in enumerate(b, 1):
            p, d[j] = d[j], min(d[j] + 1, d[j - 1] + 1, p + (ca != cb))
    return d[-1]


CODE_RE = re.compile(r'^F[A-Z][-_]?\d{0,3}[A-Z]?[-_]?[A-Z0-9]{1,3}$')


def classify_words(words):
    tags = {'brand_overlay': [], 'label': [], 'price': [], 'code_sticker': []}
    for wd in words or []:
        t = re.sub(r'[^A-Za-z0-9¥\-]', '', wd['t'])
        if not t:
            continue
        tl = t.lower()
        if any(_lev(tl, k) <= (1 if len(k) <= 6 else 2) for k in ('elegance', 'emporium')) and len(tl) >= 6:
            tags['brand_overlay'].append(wd)
        elif _lev(tl, 'fujiba') <= 1 and len(tl) >= 5:
            tags['label'].append(wd)
        elif tl in ('tokyo', 'japan', 'tokyojapan') or _lev(tl, 'tokyo') <= 1:
            tags['label'].append(wd)
        elif tl.startswith('price') or '¥' in t:
            tags['price'].append(wd)
        elif CODE_RE.match(t.upper()) and len(t) >= 5:
            tags['code_sticker'].append(wd)
    return tags


# --------------------------------------------------------------- masks & lines

def fabric_mask(lab, bgr):
    """Fabric vs backdrop: smooth near-neutral or near-white pixels connected to the border."""
    L, a, b = lab[..., 0], lab[..., 1], lab[..., 2]
    C = np.hypot(a, b)
    mu = cv2.blur(L, (9, 9))
    sd = np.sqrt(np.maximum(cv2.blur(L * L, (9, 9)) - mu * mu, 0))
    white = (L > 93) & (C < 4)
    cand = (((C < 7) & (sd < 3.5)) | white).astype(np.uint8)
    cand = cv2.morphologyEx(cand, cv2.MORPH_CLOSE, np.ones((5, 5), np.uint8))
    n, lbl, st, _ = cv2.connectedComponentsWithStats(cand, 4)
    h, w = cand.shape
    border = set(np.unique(np.concatenate([lbl[0], lbl[-1], lbl[:, 0], lbl[:, -1]])).tolist()) - {0}
    back = np.isin(lbl, list(border)) & (cand > 0) if border else np.zeros_like(cand, bool)
    # tiny backdrop specks inside fabric are fabric (plain ground of the print)
    fab = (~back).astype(np.uint8)
    fab = cv2.morphologyEx(fab, cv2.MORPH_OPEN, np.ones((7, 7), np.uint8))
    n2, l2, s2, _ = cv2.connectedComponentsWithStats(fab, 8)
    keep = np.zeros_like(fab)
    for i in range(1, n2):
        if s2[i, cv2.CC_STAT_AREA] >= 0.01 * h * w:
            keep[l2 == i] = 1
    # fill holes
    inv = (1 - keep).astype(np.uint8)
    n3, l3, s3, _ = cv2.connectedComponentsWithStats(inv, 4)
    bl = set(np.unique(np.concatenate([l3[0], l3[-1], l3[:, 0], l3[:, -1]])).tolist())
    for i in range(1, n3):
        if i not in bl:
            keep[l3 == i] = 1
    cut = float(((L > 97) & (C < 2.5) & back).mean())
    return keep, float(back.mean()), cut, white


def long_lines(gray, fab):
    """LSD segments >= 12 % of the short side, split into boundary (fabric|backdrop) and internal."""
    h, w = gray.shape
    lsd = cv2.createLineSegmentDetector()
    segs = lsd.detect(cv2.GaussianBlur(gray, (3, 3), 0))[0]
    out = {'boundary': [], 'internal': []}
    if segs is None:
        return out
    minlen = 0.12 * min(h, w)
    for s in segs[:, 0, :]:
        x1, y1, x2, y2 = [float(v) for v in s]
        ln = np.hypot(x2 - x1, y2 - y1)
        if ln < minlen:
            continue
        nx, ny = -(y2 - y1) / ln, (x2 - x1) / ln
        ts = np.linspace(0.1, 0.9, 9)
        px, py = x1 + ts * (x2 - x1), y1 + ts * (y2 - y1)
        sides = []
        for sg in (+1, -1):
            qx = np.clip((px + sg * 7 * nx).round().astype(int), 0, w - 1)
            qy = np.clip((py + sg * 7 * ny).round().astype(int), 0, h - 1)
            sides.append(float(fab[qy, qx].mean()))
        ang = float(np.degrees(np.arctan2(y2 - y1, x2 - x1)))
        rec = {'p': [round(x1, 1), round(y1, 1), round(x2, 1), round(y2, 1)], 'len': round(ln, 1), 'ang': round(ang, 2)}
        if min(sides) > 0.8:
            out['internal'].append(rec)
        elif max(sides) > 0.8 and min(sides) < 0.2:
            out['boundary'].append(rec)
    return out


def max_rectangles(mask, k=4, min_side=16):
    """Top-k (mostly) non-overlapping maximal axis-aligned rectangles of a binary grid."""
    m = mask.astype(np.uint8).copy()
    H, W = m.shape
    rects = []
    for _ in range(k):
        hist = np.zeros(W, int)
        best = (0, None)
        for y in range(H):
            hist = np.where(m[y] > 0, hist + 1, 0)
            stack = []
            for x in range(W + 1):
                cur = hist[x] if x < W else 0
                start = x
                while stack and stack[-1][1] >= cur:
                    sx, sh = stack.pop()
                    area = sh * (x - sx)
                    if area > best[0] and sh >= min_side and (x - sx) >= min_side:
                        best = (area, (sx, y - sh + 1, x - sx, sh))
                    start = sx
                stack.append((start, cur))
        if best[1] is None:
            break
        x, y, w, h = best[1]
        rects.append(best[1])
        m[y:y + h, x:x + w] = 0
    return rects


# -------------------------------------------------------------- colour reference

def colour_reference(lab, fab, back):
    """ColorChecker grid or a plain grey card / white paper quadrilateral not touching the frame."""
    L = lab[..., 0]
    C = np.hypot(lab[..., 1], lab[..., 2])
    h, w = L.shape
    mu = cv2.blur(L, (7, 7))
    sd = np.sqrt(np.maximum(cv2.blur(L * L, (7, 7)) - mu * mu, 0))
    uni = (sd < 2.5).astype(np.uint8)
    n, lbl, st, cen = cv2.connectedComponentsWithStats(uni, 4)
    squares = []
    papers = []
    for i in range(1, n):
        x, y, bw, bh, a = st[i]
        if a < 0.0002 * h * w:
            continue
        touches = x <= 1 or y <= 1 or x + bw >= w - 1 or y + bh >= h - 1
        fill = a / float(bw * bh)
        if 0.75 <= bw / float(bh) <= 1.33 and fill >= 0.8 and a <= 0.015 * h * w:
            squares.append((cen[i][0], cen[i][1], a))
        if a >= 0.015 * h * w and not touches:
            comp = (lbl == i).astype(np.uint8)
            if float(C[comp > 0].mean()) < 5:
                cnts, _ = cv2.findContours(comp, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
                c = max(cnts, key=cv2.contourArea)
                hull = cv2.convexHull(c)
                sol = cv2.contourArea(c) / max(1.0, cv2.contourArea(hull))
                ap = cv2.approxPolyDP(hull, 0.03 * cv2.arcLength(hull, True), True)
                if len(ap) == 4 and sol >= 0.93:
                    papers.append({'box': [int(x), int(y), int(bw), int(bh)], 'L': round(float(L[comp > 0].mean()), 1)})
    checker = False
    if len(squares) >= 12:
        A = np.array(squares)
        areas = A[:, 2]
        med = np.median(areas)
        sel = A[(areas > 0.6 * med) & (areas < 1.6 * med)]
        if len(sel) >= 12:
            # >= 3 rows of >= 4 roughly collinear centres
            ys = np.sort(sel[:, 1])
            gap = np.sqrt(med) * 0.6
            rows = np.split(ys, np.where(np.diff(ys) > gap)[0] + 1)
            checker = sum(1 for r in rows if len(r) >= 4) >= 3
    return {'checker': bool(checker), 'paper_or_card': papers[:3], 'present': bool(checker or papers)}


def cast_of(lab):
    """Grayworld on low-chroma bright pixels (cat-1.1 method) — backdrop, selvedge, white motifs."""
    L, a, b = lab[..., 0], lab[..., 1], lab[..., 2]
    C = np.hypot(a, b)
    sel = (C < 15) & (L > np.percentile(L, 50))
    frac = float(sel.mean())
    if frac < 0.02:
        return None, frac
    return round(float(np.hypot(a[sel].mean(), b[sel].mean())), 2), round(frac, 3)


# ----------------------------------------------------------------- image audit

def audit_image(path, kind, wm):
    t0 = time.time()
    raw = T.load_bgr(path)
    if raw is None:
        return {'ok': False, 'error': 'unreadable'}
    H, W = raw.shape[:2]
    small, s_an = T.resize_max(raw, AN)
    lab = T.lab32(small)
    gray = cv2.cvtColor(small, cv2.COLOR_BGR2GRAY)
    s800, _ = T.resize_max(raw, 800)
    sharp = float(cv2.Laplacian(cv2.cvtColor(s800, cv2.COLOR_BGR2GRAY), cv2.CV_64F).var())
    fab, back_share, cut_share, _ = fabric_mask(lab, small)
    cast, neutral = cast_of(lab)
    h, w = fab.shape
    res = {'ok': True, 'w': W, 'h': H, 'kind': kind, 'sharpness': round(sharp, 1), 'cast': cast,
           'neutral_frac': neutral, 'coverage': round(float(fab.mean()), 3), 'backdrop_share': round(back_share, 3),
           'cutout': bool(cut_share > 0.15)}
    overlay = np.zeros_like(fab)
    words = None
    if kind in OWN_KINDS:
        words = ocr_words(raw)
        tags = classify_words(words)
        res['ocr'] = {k: [x['t'] for x in v][:6] for k, v in tags.items() if v}
        for k, v in tags.items():
            for wd in v:
                x0, y0, x1, y1 = [int(round(c * s_an)) for c in wd['box']]
                bh = max(4, y1 - y0)
                padx, pady = (bh * 6, bh * 2) if k in ('label', 'code_sticker') else (bh, bh)
                overlay[max(0, y0 - pady):min(h, y1 + pady), max(0, x0 - padx):min(w, x1 + padx)] = 1
        wmr = wm.detect(raw)
        res['wm_template'] = wmr
        brand = bool(tags['brand_overlay'])
        if wmr.get('present'):
            x0, y0, x1, y1 = [int(round(c * s_an)) for c in wmr['box']]
            ph, pw = int(0.03 * h), int(0.03 * w)
            overlay[max(0, y0 - ph):min(h, y1 + ph), max(0, x0 - pw):min(w, x1 + pw)] = 1
        if brand or wmr.get('present'):
            wm_present = True
        elif wmr.get('present') is False and wmr.get('z', 99) < WM_Z_CLEAR:
            wm_present = False           # clearly below the overlay score range
        else:
            wm_present = None            # inconclusive: faint overlay cannot be ruled out
        res['watermark'] = wm_present
        res['overlay_price'] = bool(tags['price'])
        res['label_present'] = bool(tags['label'])
        res['code_sticker'] = bool(tags['code_sticker'])
    lines = long_lines(gray, fab)
    res['lines'] = {'boundary': len(lines['boundary']), 'internal': len(lines['internal'])}
    lineband = np.zeros_like(fab)
    bw_ = max(3, int(0.012 * max(h, w)))
    for l in lines['internal']:
        x1, y1, x2, y2 = [int(round(v)) for v in l['p']]
        cv2.line(lineband, (x1, y1), (x2, y2), 1, 2 * bw_)
    clean = (fab > 0) & (overlay == 0) & (lineband == 0)
    clean = cv2.erode(clean.astype(np.uint8), np.ones((5, 5), np.uint8))
    res['clean_share'] = round(float(clean.mean()), 3)
    res['colour_ref'] = colour_reference(lab, fab, 1 - fab)
    # region proposals on a 1/4 grid
    g = cv2.resize(clean, (max(1, w // 4), max(1, h // 4)), interpolation=cv2.INTER_NEAREST)
    rects = max_rectangles(g, k=4, min_side=16)
    regions = []
    for (x, y, rw, rh) in rects:
        regions.append([x * 4 / s_an, y * 4 / s_an, rw * 4 / s_an, rh * 4 / s_an])   # original px
    res['regions'] = [[round(v, 1) for v in r] for r in regions]
    if rects:
        x, y, rw, rh = [v * 4 for v in rects[0]]
        qe, qr = T.quadfit_evenness(small[y:y + rh, x:x + rw], clean[y:y + rh, x:x + rw])
        res['quad_evenness'], res['quad_range_L'] = qe, qr
    res['photo_class'] = ('cutout' if res['cutout'] else 'packaged' if res.get('label_present') and res['clean_share'] < 0.35
                          else 'folded' if res['lines']['internal'] >= 2 else 'flat' if res['coverage'] > 0.85 and res['lines']['internal'] == 0
                          else 'flat_region' if rects else 'unknown')
    res['_clean'] = clean           # analysis-resolution clean mask (not serialised)
    res['_s_an'] = s_an
    res['_lines'] = lines
    res['secs'] = round(time.time() - t0, 2)
    return res


# ------------------------------------------------------------------ tile attempts

def work_image(raw):
    img, s = T.resize_max(raw, T.WORK_MAX_SIDE)
    return img, s


def region_crop(img, s_work, reg, clean, s_an, margin=0.01):
    x, y, w, h = [v * s_work for v in reg]
    H, W = img.shape[:2]
    mx, my = margin * w, margin * h
    x0, y0 = int(max(0, x + mx)), int(max(0, y + my))
    x1, y1 = int(min(W, x + w - mx)), int(min(H, y + h - my))
    crop = img[y0:y1, x0:x1]
    cm = cv2.resize(clean, (W, H), interpolation=cv2.INTER_NEAREST)[y0:y1, x0:x1]
    return crop, cm, (x0, y0)


def edges_homography(crop, cmask, lines_an, s_an, s_work, off):
    """Homography from the two long fabric boundary edges bounding the region (strip sides)."""
    H, W = crop.shape[:2]
    k = s_work / s_an
    cand = []
    for l in lines_an['boundary']:
        x1, y1, x2, y2 = [v * k for v in l['p']]
        x1 -= off[0]; x2 -= off[0]; y1 -= off[1]; y2 -= off[1]
        ang = abs(l['ang']) % 180
        vert = min(abs(ang - 90), abs(ang + 90 - 180)) <= 15
        if not vert or np.hypot(x2 - x1, y2 - y1) < 0.35 * H:
            continue
        xm = (x1 + x2) / 2
        if -0.15 * W <= xm <= 1.15 * W:
            cand.append((xm, (x1, y1, x2, y2)))
    if len(cand) < 2:
        return None, 'fewer than two long fabric edges along the region'
    left = min(cand, key=lambda c: c[0])[1]
    right = max(cand, key=lambda c: c[0])[1]
    if abs(min(cand)[0] - max(cand)[0]) < 0.5 * W:
        return None, 'edges too close'

    def x_at(l, y):
        x1, y1, x2, y2 = l
        return x1 + (x2 - x1) * (y - y1) / ((y2 - y1) or 1e-6)
    ya, yb = 0.1 * H, 0.9 * H
    src = np.float32([[x_at(left, ya), ya], [x_at(right, ya), ya], [x_at(right, yb), yb], [x_at(left, yb), yb]])
    wa, wb = src[1, 0] - src[0, 0], src[2, 0] - src[3, 0]
    conv = float(np.degrees(np.arctan2(abs(wa - wb), (yb - ya))))
    if conv < 0.3:
        return None, f'edges already parallel (convergence {conv:.2f} deg)'
    wd = (wa + wb) / 2
    x0 = (W - wd) / 2
    dst = np.float32([[x0, ya], [x0 + wd, ya], [x0 + wd, yb], [x0, yb]])
    Hm = cv2.getPerspectiveTransform(src, dst)
    return {'H': Hm, 'convergence_deg': round(conv, 3)}, None


def try_tile(crop, cmask, lat, method, fixes, extra=None):
    """One deterministic tile attempt on a (possibly fixed) region. Returns attempt dict + tile."""
    att = {'method': method, 'fixes': list(fixes), 'lattice': {k: lat.get(k) for k in ('b1', 'b2', 'ncc1', 'ncc2', 'best_peak', 'reason', 'stripe')}}
    if extra:
        att.update(extra)
    if not lat.get('found'):
        att.update(result='no_lattice', worked=False, note=lat.get('reason'))
        return att, None
    if not lat.get('passes'):
        att.update(result='lattice_weak', worked=False, note=lat.get('reason'))
        return att, None
    u, w, rot, skew = T.supercell(lat['b1'], lat['b2'])
    nu, nw = int(round(np.hypot(*u))), int(round(np.hypot(*w)))
    att.update(u=[round(float(u[0]), 3), round(float(u[1]), 3)], w=[round(float(w[0]), 3), round(float(w[1]), 3)],
               rotation_deg=round(rot, 3), skew_deg=round(skew, 3), nu=nu, nw=nw)
    if abs(rot) > T.ROTATE_FREE_DEG:
        att['fixes'].append('rotate')
    if abs(skew) > T.ROTATE_FREE_DEG:
        att['fixes'].append('deskew')
    if min(nu, nw) < T.MIN_TILE_PX:
        att.update(result='too_small', worked=False, note=f'super-cell {nu}x{nw}px < {T.MIN_TILE_PX}')
        return att, None
    H, W = cmask.shape
    # feasible origins: whole super-cell + 24 px margin inside the region
    xs = [p[0] for p in ((0, 0), u, w, (u[0] + w[0], u[1] + w[1]))]
    ys = [p[1] for p in ((0, 0), u, w, (u[0] + w[0], u[1] + w[1]))]
    m = 24
    ox0, ox1 = m - min(xs), W - m - max(xs)
    oy0, oy1 = m - min(ys), H - m - max(ys)
    if ox1 < ox0 or oy1 < oy0:
        att.update(result='region_smaller_than_repeat', worked=False,
                   note=f'repeat {nu}x{nw}px does not fit region {W}x{H}px with seam margin')
        return att, None
    best = None
    tried = 0
    for fy in np.linspace(0, 1, 4):
        for fx in np.linspace(0, 1, 4):
            o = (float(ox0 + fx * (ox1 - ox0)), float(oy0 + fy * (oy1 - oy0)))
            mx, my = T.grid_maps(o, u, w, nu, nw)
            if T.inside(cmask, mx, my).mean() < 0.995:
                continue
            tried += 1
            sc = T.seam_check(crop, cmask, o, u, w, nu, nw)
            key = sc['de_mean'] + 10 * sc['dissim'] if ('u' in sc and 'w' in sc) else 1e9
            if best is None or key < best[0]:
                best = (key, o, sc)
    if best is None:
        att.update(result='occluded', worked=False, note='no clean origin for a full repeat in this region')
        return att, None
    _, o, sc = best
    mx, my = T.grid_maps(o, u, w, nu, nw)
    tile = T.sample(crop, mx, my)
    art = T.artefact_check(crop, cmask, o, u, w, nu, nw, tile)
    clipped = float(((tile.max(axis=2) >= 254) | (tile.max(axis=2) <= 2)).mean())
    att.update(origin=[round(o[0], 2), round(o[1], 2)], origins_tried=tried, seam=sc, artefact=art,
               clipped_share=round(clipped, 4))
    ok = sc['pass'] and art['pass']
    if ok:
        att.update(result='tile_ok', worked=True)
    else:
        why = []
        if not sc['pass']:
            why.append(f"seam dE00 {sc['de_mean']}/{sc['de_p95']} 1-NCC {sc['dissim']}")
        if not art['pass']:
            why.append(art.get('reason') or f"artefact share {art.get('artefact_share')}")
        att.update(result='seam_fail' if not sc['pass'] else 'artefact_fail', worked=False, note='; '.join(why))
    att['_tile'] = tile if ok else None
    att['_geom'] = (o, u, w, nu, nw)
    return att, tile if ok else None


def flatten(crop, cmask, u, w):
    """Labelled colour correction: divide linear RGB by the repeat-window illumination field."""
    field, Y = T.illumination_field(crop, cmask, (abs(u[0]) + abs(w[0]), abs(u[1]) + abs(w[1])))
    ok = np.isfinite(field) & (cmask > 0)
    if ok.mean() < 0.3:
        return None, {'reason': 'illumination field undefined (region < 1 repeat window)'}
    ref = float(np.median(field[ok]))
    gain = np.where(ok, ref / np.maximum(field, 1e-6), np.nan)
    g = gain[ok]
    stats = {'gain_p1': round(float(np.percentile(g, 1)), 4), 'gain_p99': round(float(np.percentile(g, 99)), 4)}
    if stats['gain_p1'] < 1 - T.FLATTEN_GAIN_MAX or stats['gain_p99'] > 1 + T.FLATTEN_GAIN_MAX:
        return None, dict(stats, reason=f'gain outside ±{T.FLATTEN_GAIN_MAX:.0%} (too uneven to correct honestly)')
    gfill = np.where(np.isfinite(gain), gain, 1.0).astype(np.float32)
    lin = T.srgb_to_lin(crop)
    out = T.lin_to_srgb(lin * gfill[..., None])
    m2 = (cmask > 0) & np.isfinite(gain)
    return (out, m2.astype(np.uint8)), stats


def finish(att, img, mask, lat, a):
    """Evenness / wrinkles / colour reliability for a tile that passed the gates."""
    o, u, w, nu, nw = att['_geom']
    field, _ = T.illumination_field(img, mask, (abs(u[0]) + abs(w[0]), abs(u[1]) + abs(w[1])))
    ev, rng = T.evenness_from_field(field, mask)
    ws, wrms = T.wrinkle_score(img, mask, lat['b1'], lat['b2'])
    att.update(evenness=ev, illum_range_L=rng, wrinkles=ws, wrinkle_rms_L=wrms)
    colour_ok = (a.get('cast') is not None and a['cast'] <= T.CAST_MAX and ev is not None and ev >= T.EVEN_MIN
                 and att.get('clipped_share', 0) < 0.01)
    fixes = set(att['fixes'])
    att['colour_ok'] = bool(colour_ok)
    att['level'] = 'A' if (not fixes and colour_ok and a['sharpness'] >= T.SHARP_MIN) else 'B'
    return att


def same_photo_as_supplier(path, sup_paths):
    """SIFT + RANSAC homography, then NCC after alignment: >= 0.92 means the same photograph
    (a crop/rescale of the supplier file), not merely the same fabric."""
    if not sup_paths:
        return {'checked': False, 'same_photo': False, 'note': 'no supplier reference for this product'}
    a = T.load_bgr(path)
    if a is None:
        return {'checked': False, 'same_photo': False}
    a, _ = T.resize_max(a, 1000)
    ga = cv2.cvtColor(a, cv2.COLOR_BGR2GRAY)
    sift = cv2.SIFT_create(4000)
    ka, da = sift.detectAndCompute(ga, None)
    best = {'checked': True, 'same_photo': False, 'ncc': None}
    for sp in sup_paths:
        b = T.load_bgr(sp)
        if b is None or da is None:
            continue
        b, _ = T.resize_max(b, 1400)
        gb = cv2.cvtColor(b, cv2.COLOR_BGR2GRAY)
        kb, db = sift.detectAndCompute(gb, None)
        if db is None:
            continue
        m = cv2.BFMatcher().knnMatch(da, db, k=2)
        good = [x[0] for x in m if len(x) == 2 and x[0].distance < 0.75 * x[1].distance]
        if len(good) < 12:
            continue
        src = np.float32([ka[g.queryIdx].pt for g in good]).reshape(-1, 1, 2)
        dst = np.float32([kb[g.trainIdx].pt for g in good]).reshape(-1, 1, 2)
        Hm, inl = cv2.findHomography(src, dst, cv2.RANSAC, 4.0)
        if Hm is None:
            continue
        warped = cv2.warpPerspective(gb, np.linalg.inv(Hm), (ga.shape[1], ga.shape[0]))
        valid = cv2.warpPerspective(np.ones_like(gb), np.linalg.inv(Hm), (ga.shape[1], ga.shape[0])) > 0
        c = T.ncc(ga, warped, valid)
        if c is not None and (best['ncc'] is None or c > best['ncc']):
            best.update(ncc=round(c, 4), inliers=int(inl.sum()), same_photo=bool(c >= 0.92 and inl.sum() >= 50))
    return best


def product_tiles(prod, audits, raws, tiles_dir, run_id):
    """Every own photo x top regions x {crop, homography, flatten}; stop at the first A."""
    attempts = []
    best_a, best_b = None, None
    order = []
    for im in prod['images']:
        a = audits.get(im['key'])
        if not a or not a.get('ok') or im['kind'] not in OWN_KINDS:
            continue
        for ri, reg in enumerate(a['regions'][:3]):
            order.append((a['sharpness'] >= T.SHARP_MIN, reg[2] * reg[3], im, a, ri, reg))
    order.sort(key=lambda t: (not t[0], -t[1]))
    for sharp_ok, area, im, a, ri, reg in order[:8]:
        if best_a:
            break
        raw = raws.get(im['key'])
        if raw is None:
            raw = T.load_bgr(im['path'])
        img, s_work = work_image(raw)
        crop, cm, off = region_crop(img, s_work, reg, a['_clean'], a['_s_an'])
        if min(crop.shape[:2]) < 64:
            attempts.append({'image': im['key'], 'region': ri, 'method': 'crop', 'result': 'too_small', 'worked': False,
                             'note': f'region {crop.shape[1]}x{crop.shape[0]}px'})
            continue
        base = {'image': im['key'], 'kind': im['kind'], 'path': im['path'], 'region': ri,
                'region_px': [int(off[0]), int(off[1]), int(crop.shape[1]), int(crop.shape[0])], 'work_scale': round(s_work, 4),
                'sharpness': a['sharpness']}
        lat = T.detect_lattice(crop, cm)
        att, tile = try_tile(crop, cm, lat, 'crop', [], base)
        if att.get('worked'):
            finish(att, crop, cm, lat, a)
        attempts.append(att)
        cands = [(att, crop, cm, lat)]
        # perspective: homography from fabric edges, when the lattice is weak / seam fails
        if not att['worked'] or att['fixes']:
            hm, why = edges_homography(crop, cm, a['_lines'], a['_s_an'], s_work, off)
            if hm is None:
                attempts.append(dict(base, method='homography', fixes=['homography'], result='not_applicable', worked=False, note=why))
            else:
                Hm = hm['H']
                rc = cv2.warpPerspective(crop, Hm, (crop.shape[1], crop.shape[0]), flags=cv2.INTER_LINEAR, borderMode=cv2.BORDER_CONSTANT)
                rm = cv2.warpPerspective(cm, Hm, (crop.shape[1], crop.shape[0]), flags=cv2.INTER_NEAREST, borderMode=cv2.BORDER_CONSTANT)
                rm = cv2.erode(rm, np.ones((5, 5), np.uint8))
                lat2 = T.detect_lattice(rc, rm)
                att2, tile2 = try_tile(rc, rm, lat2, 'homography', ['homography'], dict(base, convergence_deg=hm['convergence_deg'], H=np.round(Hm, 6).tolist()))
                if att2.get('worked'):
                    finish(att2, rc, rm, lat2, a)
                attempts.append(att2)
                cands.append((att2, rc, rm, lat2))
        # labelled illumination flattening for seam colour failures / uneven light
        for prev, pc, pm, plat in list(cands):
            uneven = prev.get('worked') and (prev.get('evenness') is None or prev['evenness'] < T.EVEN_MIN)
            if prev.get('result') != 'seam_fail' and not uneven:
                continue
            u, w = np.array(prev['u']), np.array(prev['w'])
            fl, st = flatten(pc, pm, u, w)
            if fl is None:
                attempts.append(dict(base, method=prev['method'] + '+illum_flatten', fixes=prev['fixes'] + ['illum_flatten'],
                                     result='flatten_rejected', worked=False, note=st.get('reason'), flatten=st))
                continue
            fc, fm = fl
            att3, tile3 = try_tile(fc, fm, plat, prev['method'] + '+illum_flatten', prev['fixes'] + ['illum_flatten'],
                                   dict(base, flatten=st))
            if att3.get('worked'):
                finish(att3, fc, fm, plat, a)
            attempts.append(att3)
            cands.append((att3, fc, fm, plat))
        for c_att, cimg, cmask, clat in cands:
            if not c_att.get('worked'):
                continue
            level = c_att['level']
            if level == 'A' and best_a is None:
                best_a = c_att
            elif level == 'B' and (best_b is None or c_att['seam']['de_mean'] < best_b['seam']['de_mean']):
                best_b = c_att
    for cand_name in ('best_a', 'best_b'):
        c = locals()[cand_name]
        if c is not None and c.get('kind') == 'legacy':
            same = same_photo_as_supplier(c['path'], [im['path'] for im in prod['images'] if im['kind'] == 'supplier_ref'])
            c['provenance_check'] = same
            if same.get('same_photo'):
                c['worked'] = False
                c['result'] = 'provenance_supplier'
                c['note'] = 'legacy image is the supplier photo (internal reference only)'
                if cand_name == 'best_a':
                    best_a = None
                else:
                    best_b = None
    chosen = best_a or best_b
    if chosen is not None:
        d = os.path.join(tiles_dir, str(prod['product_id']))
        os.makedirs(d, exist_ok=True)
        fn = os.path.join(d, f"tile_{run_id}.png")
        cv2.imwrite(fn, chosen['_tile'])
        if os.path.getsize(fn) == 0:
            raise IOError('zero-size tile ' + fn)
        o, u, w, nu, nw = chosen['_geom']
        side = {'product_id': prod['product_id'], 'source_path_internal': chosen['path'], 'source_kind': chosen['kind'],
                'region_px': chosen['region_px'], 'work_scale': chosen['work_scale'], 'origin': chosen['origin'],
                'u': chosen['u'], 'w': chosen['w'], 'nu': nu, 'nw': nw, 'method': chosen['method'],
                'fixes': chosen['fixes'], 'seam': chosen['seam'], 'artefact': {k: v for k, v in chosen['artefact'].items() if k != 'copy_stats'},
                'H': chosen.get('H'), 'flatten': chosen.get('flatten'), 'version': T.VERSION,
                'note': 'tile = one rectangular repeat sampled from real photo pixels (bilinear); tiling it reproduces the print'}
        with open(fn + '.json', 'w') as f:
            json.dump(side, f, indent=1)
        chosen['output_path'] = fn
    for at in attempts:
        at.pop('_tile', None)
        at.pop('_geom', None)
    return attempts, best_a, best_b, chosen


# ----------------------------------------------------------------- product level

def summarise(prod, audits, attempts, best_a, best_b, chosen):
    own = [(im, audits.get(im['key'])) for im in prod['images'] if im['kind'] in OWN_KINDS]
    own = [(im, a) for im, a in own if a and a.get('ok')]
    sup = [(im, audits.get(im['key'])) for im in prod['images'] if im['kind'] == 'supplier_ref']
    out = {'product_id': prod['product_id'], 'photos_count': len(prod['images']),
           'own_photos_count': len(own), 'drive_photos_count': sum(1 for im in prod['images'] if im['kind'] in ('drive', 'drive_cutout')),
           'supplier_ref_count': len(sup)}
    if own:
        best_im, best = max(own, key=lambda t: (t[1]['sharpness'] >= T.SHARP_MIN, max(t[1]['w'], t[1]['h'])))
        out['best_photo_px'] = int(max(best['w'], best['h']))
        out['best_photo_sharpness'] = best['sharpness']
        wmv = [a.get('watermark') for _, a in own]
        out['watermark_present'] = True if any(v is True for v in wmv) else (False if wmv and all(v is False for v in wmv) else None)
        cr = [a['colour_ref']['present'] for _, a in own]
        out['colour_reference'] = bool(any(cr))
    else:
        out.update(best_photo_px=None, best_photo_sharpness=None, watermark_present=None, colour_reference=None)
    # lattice / repeat (best attempt with a lattice)
    lat_atts = [a for a in attempts if a.get('lattice', {}).get('b1') and a.get('lattice', {}).get('b2')]
    win = chosen or (max(lat_atts, key=lambda a: min(a['lattice'].get('ncc1') or 0, a['lattice'].get('ncc2') or 0)) if lat_atts else None)
    if win and win.get('nu'):
        out['repeat_px_x'] = win['nu']
        out['repeat_px_y'] = win['nw']
        out['repeat_lattice'] = {'b1': win['lattice']['b1'], 'b2': win['lattice']['b2'], 'ncc1': win['lattice']['ncc1'],
                                 'ncc2': win['lattice']['ncc2'], 'u': win.get('u'), 'w': win.get('w'),
                                 'source': win.get('image'), 'units': 'work px of source photo'}
    any_lat_pass = any(a.get('lattice', {}).get('ncc1') and (a['lattice'].get('ncc1') or 0) >= T.LATTICE_NCC_MIN
                       and (a['lattice'].get('ncc2') or 0) >= T.LATTICE_NCC_MIN for a in attempts)
    fits = any(a.get('result') in ('tile_ok', 'seam_fail', 'artefact_fail', 'occluded') for a in attempts)
    if any_lat_pass and fits:
        out['full_repeat_visible'] = True
    elif own and any(a.get('sharpness', 0) >= T.SHARP_MIN for _, a in own) and attempts:
        out['full_repeat_visible'] = False
    else:
        out['full_repeat_visible'] = None
    # flat vs angled — judged on the best own photo
    out['flat_vs_angled'] = flat_class(own, attempts)
    ev = [a.get('evenness') for a in attempts if a.get('evenness') is not None]
    out['lighting_evenness'] = (chosen or {}).get('evenness') if chosen else (max(ev) if ev else None)
    if out['lighting_evenness'] is None and own:
        out['lighting_evenness'] = max((a.get('quad_evenness') or 0) for _, a in own) or None
    wr = [a.get('wrinkles') for a in attempts if a.get('wrinkles') is not None]
    out['wrinkles_shadows'] = (chosen or {}).get('wrinkles') if chosen else (min(wr) if wr else None)
    # tier
    if not prod.get('applicable', True):
        tier, reason = 'C', 'not yardage (accessory) — fabric twin not applicable'
    elif best_a:
        tier = 'A'
        reason = (f"tile from own {best_a['kind']} photo, plain crop, seam dE00 {best_a['seam']['de_mean']}/{best_a['seam']['de_p95']}, "
                  f"1-NCC {best_a['seam']['dissim']}, lattice NCC {min(best_a['lattice']['ncc1'], best_a['lattice']['ncc2']):.3f}, "
                  f"cast {next((a['cast'] for im, a in own if im['key'] == best_a['image']), None)}, evenness {best_a.get('evenness')}")
    elif best_b:
        tier = 'B'
        why = []
        if best_b['fixes']:
            why.append('needs ' + '+'.join(best_b['fixes']))
        if not best_b.get('colour_ok'):
            why.append('colour not reliable (cast>6/unknown or uneven light)')
        reason = (f"tile from own {best_b['kind']} photo after deterministic fix: {', '.join(why) or 'fix'}; seam dE00 "
                  f"{best_b['seam']['de_mean']}/{best_b['seam']['de_p95']}, 1-NCC {best_b['seam']['dissim']}")
    else:
        tier = 'C'
        reason = c_reason(own, sup, attempts)
    out['tier'] = tier
    out['reason'] = reason
    out['tier_audit'] = audit_tier(prod, own, attempts)
    rank = {'A': 0, 'B': 1, 'C': 2}
    out['raised_by_step2'] = rank[tier] < rank[out['tier_audit']]
    if chosen:
        out.update(best_source_path=chosen['path'], best_source_kind=chosen['kind'], tile_path=chosen.get('output_path'),
                   tile_w=chosen['nu'], tile_h=chosen['nw'], seam_de00_mean=chosen['seam']['de_mean'],
                   seam_de00_p95=chosen['seam']['de_p95'], seam_ncc=chosen['seam']['ncc'],
                   fixes_applied=','.join(chosen['fixes']) or None)
    elif own:
        bi, ba = max(own, key=lambda t: (t[1]['sharpness'] >= T.SHARP_MIN, t[1].get('clean_share', 0), max(t[1]['w'], t[1]['h'])))
        out.update(best_source_path=bi['path'], best_source_kind=bi['kind'])
    return out


def audit_tier(prod, own, attempts):
    """Tier from photo measurements only (before any tile/seam work): A = a flat sharp own photo
    with a passing lattice and reliable colour; B = some own region shows a repeat a fix might
    rescue (lattice found, weak or rotated); C = nothing to work with."""
    if not prod.get('applicable', True) or not own:
        return 'C'
    by_key = {im['key']: a for im, a in own}
    crop = [a for a in attempts if a.get('method') == 'crop']
    for at in crop:
        lat = at.get('lattice') or {}
        ph = by_key.get(at.get('image'), {})
        if (lat.get('ncc1') or 0) >= T.LATTICE_NCC_MIN and (lat.get('ncc2') or 0) >= T.LATTICE_NCC_MIN \
                and ph.get('sharpness', 0) >= T.SHARP_MIN and ph.get('cast') is not None and ph['cast'] <= T.CAST_MAX \
                and (ph.get('quad_evenness') or 0) >= T.EVEN_MIN and not ({'rotate', 'deskew'} & set(at.get('fixes', []))):
            return 'A'
    for at in crop:
        lat = at.get('lattice') or {}
        if lat.get('b1') and ((lat.get('ncc1') or 0) >= 0.6):
            return 'B'
    return 'C'


def flat_class(own, attempts):
    """flat | flat_region | angled | folded | packaged | cutout | unknown (best own photo)."""
    if not own:
        return None
    by_key = {im['key']: a for im, a in own}
    ok = [a for a in attempts if a.get('worked')]
    plain = [a for a in ok if not ({'homography', 'rotate', 'deskew'} & set(a.get('fixes', [])))]
    if plain:
        src = by_key.get(plain[0].get('image'), {})
        return 'flat' if src.get('photo_class') == 'flat' else 'flat_region'
    if ok:
        return 'angled'
    classes = [a.get('photo_class') for _, a in own]
    for c in ('flat', 'flat_region', 'folded', 'packaged', 'cutout'):
        if c in classes:
            return c
    return 'unknown'


def c_reason(own, sup, attempts):
    if not own:
        return 'no own photo (only supplier reference images, internal-only) — new shoot needed'
    res = [a.get('result') for a in attempts]
    bits = []
    if not attempts:
        bits.append('no clean flat region found in any own photo (folded/packaged/overlaid views)')
    elif all(r in ('no_lattice', 'too_small', 'not_applicable') for r in res):
        notes = [a.get('note') for a in attempts if a.get('result') == 'no_lattice']
        bits.append('no full repeat visible in any clean region' + (f" ({notes[0]})" if notes else ''))
    elif 'region_smaller_than_repeat' in res:
        bits.append('repeat larger than the largest clean flat region (strip/fold too narrow)')
    if 'lattice_weak' in res:
        bits.append('repeat detected but not consistent (non-flat / perspective / wrinkles)')
    if 'seam_fail' in res:
        s = [a for a in attempts if a.get('result') == 'seam_fail']
        b = min(s, key=lambda a: a['seam']['de_mean'])
        bits.append(f"seam check failed (best dE00 {b['seam']['de_mean']}/{b['seam']['de_p95']}, 1-NCC {b['seam']['dissim']})")
    if 'artefact_fail' in res:
        bits.append('watermark/sticker/glare inside every candidate repeat')
    if 'occluded' in res:
        bits.append('overlays/folds leave no clean full repeat')
    if 'flatten_rejected' in res:
        bits.append('lighting too uneven to correct within ±15 %')
    if any(a.get('watermark') for _, a in own):
        bits.append('watermarked photos')
    return '; '.join(dict.fromkeys(bits)) or 'no gated tile from own photos'


def supplier_note(prod, audits, raws):
    """Internal-only: would the supplier scan tile? (never used as a source)."""
    for im in prod['images']:
        if im['kind'] != 'supplier_ref':
            continue
        raw = raws.get(im['key'])
        if raw is None:
            raw = T.load_bgr(im['path'])
        if raw is None:
            continue
        img, _ = T.resize_max(raw, 1200)
        h, w = img.shape[:2]
        c = img[int(0.08 * h):int(0.92 * h), int(0.08 * w):int(0.92 * w)]
        lat = T.detect_lattice(c)
        return {'tileable': bool(lat.get('found') and lat.get('passes')), 'ncc': [lat.get('ncc1'), lat.get('ncc2')],
                'reason': lat.get('reason')}
    return None


def dhash_cat11(path):
    """Same dHash as bin/intel/catalog_analyse.py (9x8 INTER_AREA on the full-res grey image)."""
    raw = cv2.imread(path, cv2.IMREAD_COLOR)
    if raw is None:
        return None
    g = cv2.cvtColor(raw, cv2.COLOR_BGR2GRAY)
    small = cv2.resize(g, (9, 8), interpolation=cv2.INTER_AREA)
    v = 0
    for bit in (small[:, 1:] > small[:, :-1]).flatten():
        v = (v << 1) | int(bit)
    return '%016x' % v


def main():
    mode, inp, out = sys.argv[1], sys.argv[2], sys.argv[3]
    T.guard('twin_audit_' + mode)
    if mode == 'supplier-dhash':
        files = sorted(f for f in os.listdir(inp) if f.lower().endswith(('.jpg', '.jpeg', '.png', '.webp')))
        res = {f: dhash_cat11(os.path.join(inp, f)) for f in files}
        with open(out, 'w') as fh:
            json.dump(res, fh)
        print(json.dumps({'supplier_originals': len(res)}))
        return 0
    job = json.load(open(inp))
    if mode == 'build-wm':
        paths = [im['path'] for p in job['products'] for im in p['images'] if im['kind'] in ('site_own', 'drive')]
        print(json.dumps(build_wm(paths, out)))
        return 0
    wm = WM(job.get('wm_templates'))
    tiles_dir = job['tiles_dir']
    run_id = job['run_id']
    results = []
    for n, prod in enumerate(job['products']):
        t0 = time.time()
        try:
            audits, raws = {}, {}
            for im in prod['images']:
                if im['kind'] == 'supplier_ref':
                    continue
                try:
                    audits[im['key']] = audit_image(im['path'], im['kind'], wm)
                except Exception as e:  # noqa: BLE001
                    audits[im['key']] = {'ok': False, 'error': f'{type(e).__name__}: {e}'}
            if prod.get('applicable', True):
                attempts, best_a, best_b, chosen = product_tiles(prod, audits, raws, tiles_dir, run_id)
            else:
                attempts, best_a, best_b, chosen = [], None, None, None
            summ = summarise(prod, audits, attempts, best_a, best_b, chosen)
            summ['supplier_ref'] = supplier_note(prod, audits, raws) if not best_a else None
            for k, a in audits.items():
                for x in ('_clean', '_s_an', '_lines'):
                    a.pop(x, None)
            results.append({'product_id': prod['product_id'], 'summary': summ, 'images': audits, 'attempts': attempts,
                            'secs': round(time.time() - t0, 1)})
        except Exception as e:  # noqa: BLE001 — record, never crash the batch
            results.append({'product_id': prod['product_id'], 'error': f'{type(e).__name__}: {e}',
                            'trace': traceback.format_exc()[-1500:]})
        if (n + 1) % 10 == 0:
            with open(out + '.part', 'w') as f:
                f.write(T.jdump(results))
            print(json.dumps({'progress': n + 1, 'of': len(job['products'])}), flush=True)
    with open(out, 'w') as f:
        f.write(T.jdump(results))
    if os.path.getsize(out) == 0:
        raise IOError('zero-size output')
    print(json.dumps({'done': len(results), 'out': out}))
    return 0


if __name__ == '__main__':
    sys.exit(main())
