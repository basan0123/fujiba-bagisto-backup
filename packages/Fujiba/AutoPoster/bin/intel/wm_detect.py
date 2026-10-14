#!/usr/bin/env python3
"""Detect the "Fujiba Elegance Emporium" watermark baked into product photos and
pick the largest watermark-free fabric rectangle. Read-only: never edits pixels.

Two styles exist in the catalogue, so two independent detectors (either => present):
  1. script  – white italic script with dark stroke (FV-89-B/D style). Normalised
               cross-correlation (TM_CCOEFF_NORMED) of a high-pass residual
               (gray - median21) at a 2000px working size against
               storage/app/intel/twin/wm_script_tpl.npz. That template is the
               sign-agreement residual of FV-89-B and FV-89-D (`build-template`).
  2. ocr     – sans-serif text, usually blue (FV-89-C / FV-61 / FV-82 style).
               tesseract (eng, psm 11) on a "blue ink" map (B - R) and a light-
               stroke top-hat map, rotated -30..30 deg; words fuzzily matching
               fujiba/elegance/emporium (ratio >= 0.75, conf >= 60), >= 2 reads.

    wm_detect.py detect <img> [...]
    wm_detect.py crop <img> --aspect 0.75     largest wm-free fabric rect (w/h)
    wm_detect.py build-template <B.jpg> <D.jpg>
"""
import argparse, csv, difflib, io, json, subprocess, sys, tempfile
import os
import cv2
import numpy as np

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import heavy_guard  # noqa: E402

TPL = '/var/www/bagisto/storage/app/intel/twin/wm_script_tpl.npz'
SCRIPT_THRESH = 0.25    # FV-89-B 0.55 / D 0.57 vs <= 0.064 on 40 wm-free / other-style photos
SCRIPT_SCALES = (0.9, 1.0, 1.1)
WORDS = ('fujiba', 'elegance', 'emporium')
OCR_ANGLES = range(-30, 31, 5)
WORK = 2000
_tpl = None


def _template():
    global _tpl
    if _tpl is None:
        d = np.load(TPL)
        _tpl = (d['tpl'].astype(np.float32), d['mask'].astype(np.uint8))
    return _tpl


def _work(bgr):
    f = WORK / max(bgr.shape[:2])
    if abs(f - 1) < 1e-3:
        return bgr, 1.0
    return cv2.resize(bgr, None, fx=f, fy=f, interpolation=cv2.INTER_AREA if f < 1 else cv2.INTER_CUBIC), f


def residual(gray):
    return gray.astype(np.float32) - cv2.medianBlur(gray, 21).astype(np.float32)


def detect_script(bgr):
    img, f = _work(bgr)
    res = residual(cv2.cvtColor(img, cv2.COLOR_BGR2GRAY))
    tpl, _ = _template()
    best = (-1.0, None)
    for s in SCRIPT_SCALES:
        t = cv2.resize(tpl, None, fx=s, fy=s, interpolation=cv2.INTER_AREA)
        if t.shape[0] >= res.shape[0] or t.shape[1] >= res.shape[1]:
            continue
        r = cv2.matchTemplate(res, t, cv2.TM_CCOEFF_NORMED)
        _, mv, _, ml = cv2.minMaxLoc(r)
        if mv > best[0]:
            best = (float(mv), [ml[0] / f, ml[1] / f, t.shape[1] / f, t.shape[0] / f])
    sc, box = best
    hit = sc >= SCRIPT_THRESH
    return {'score': round(sc, 4), 'threshold': SCRIPT_THRESH, 'hit': hit,
            'box': [int(v) for v in box] if hit else None}


def _ocr_words(gray_ink):
    with tempfile.NamedTemporaryFile(suffix='.png') as tf:
        cv2.imwrite(tf.name, gray_ink)
        out = subprocess.run(['tesseract', tf.name, '-', '--psm', '11', '-l', 'eng', 'tsv'],
                             capture_output=True, text=True, timeout=60).stdout
    for row in csv.DictReader(io.StringIO(out), delimiter='\t', quoting=csv.QUOTE_NONE):
        t = ''.join(ch for ch in (row.get('text') or '').lower() if ch.isalpha())
        try:
            conf = float(row.get('conf', -1))
        except (TypeError, ValueError):
            continue
        if len(t) < 4 or conf < 60:
            continue
        if max(difflib.SequenceMatcher(None, t, w).ratio() for w in WORDS) >= 0.75:
            yield t, conf, int(row['left']), int(row['top']), int(row['width']), int(row['height'])


def detect_ocr(bgr):
    f = 1000 / max(bgr.shape[:2])
    small = cv2.resize(bgr, None, fx=f, fy=f, interpolation=cv2.INTER_AREA if f < 1 else cv2.INTER_CUBIC)
    h, w = small.shape[:2]
    b, r = small[..., 0].astype(np.int16), small[..., 2].astype(np.int16)
    gray = cv2.cvtColor(small, cv2.COLOR_BGR2GRAY)
    inks = {'blue': np.clip((b - r) * 3, 0, 255).astype(np.uint8),
            'light': np.clip((gray.astype(np.int16) - cv2.medianBlur(gray, 15)) * 3, 0, 255).astype(np.uint8)}
    found, pts = [], []
    for name, ink in inks.items():
        for ang in OCR_ANGLES:
            M = cv2.getRotationMatrix2D((w / 2, h / 2), ang, 1.0)
            rot = cv2.warpAffine(255 - ink, M, (w, h), borderValue=255)
            Minv = cv2.invertAffineTransform(M)
            for t, conf, x, y, ww, hh in _ocr_words(rot):
                found.append({'word': t, 'conf': round(conf, 1), 'ink': name, 'angle': ang})
                c = np.array([[x, y], [x + ww, y], [x + ww, y + hh], [x, y + hh]], np.float32)
                pts.append(cv2.transform(c[None], Minv)[0] / f)
        if len(found) >= 2:
            break   # strong enough; skip second ink map
    hit = len(found) >= 2   # >=2 matched word reads, so one noisy read is not a hit
    box = None
    if hit:
        p = np.concatenate(pts)
        x0, y0 = p.min(0); x1, y1 = p.max(0)
        box = [int(x0), int(y0), int(x1 - x0), int(y1 - y0)]
    return {'hit': hit, 'box': box, 'reads': found[:12]}


def detect(bgr, use_ocr=True):
    s = detect_script(bgr)
    o = detect_ocr(bgr) if use_ocr else {'hit': False, 'box': None, 'reads': []}
    boxes = [b for b in (s['box'], o['box']) if b]
    return {'watermark_present': bool(boxes), 'boxes': boxes, 'script': s, 'ocr': o}


def fabric_mask(bgr):
    """Patterned fabric vs grey/white backdrop & white selvage (heuristic, measured)."""
    hsv = cv2.cvtColor(bgr, cv2.COLOR_BGR2HSV)
    s, v = hsv[..., 1].astype(np.int32), hsv[..., 2].astype(np.int32)
    gray = cv2.cvtColor(bgr, cv2.COLOR_BGR2GRAY).astype(np.float32)
    var = cv2.GaussianBlur(gray * gray, (31, 31), 0) - cv2.GaussianBlur(gray, (31, 31), 0) ** 2
    fab = ((s > 70) | (v < 70)) & (var > 150)
    fab = cv2.morphologyEx(fab.astype(np.uint8) * 255, cv2.MORPH_CLOSE, np.ones((25, 25), np.uint8))
    fab = cv2.morphologyEx(fab, cv2.MORPH_OPEN, np.ones((25, 25), np.uint8))
    # pale flowers (low saturation) punch holes: fill every hole enclosed by fabric
    cs, _ = cv2.findContours(fab, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    big = [c for c in cs if cv2.contourArea(c) > 0.01 * fab.size]
    out = np.zeros_like(fab)
    cv2.drawContours(out, big, -1, 255, thickness=cv2.FILLED)
    return out


def largest_rect(allowed, aspect):
    """Largest axis-aligned rect of w/h=aspect fully inside allowed (bool). Binary search on h."""
    H, W = allowed.shape
    ii = np.pad((~allowed).astype(np.int32).cumsum(0).cumsum(1), ((1, 0), (1, 0)))

    def fits(h):
        w = int(round(h * aspect))
        if h < 1 or w < 1 or h > H or w > W:
            return None
        s = ii[h:, w:] - ii[:-h, w:] - ii[h:, :-w] + ii[:-h, :-w]
        ys, xs = np.where(s == 0)
        if not len(ys):
            return None
        k = np.argmin((ys + h / 2 - H / 2) ** 2 + (xs + w / 2 - W / 2) ** 2)
        return int(xs[k]), int(ys[k]), w, h

    lo, hi, best = 1, H, None
    while lo <= hi:
        mid = (lo + hi) // 2
        r = fits(mid)
        if r:
            best, lo = r, mid + 1
        else:
            hi = mid - 1
    return best


def _overlaps(a, b):
    return not (a[0] + a[2] <= b[0] or b[0] + b[2] <= a[0] or a[1] + a[3] <= b[1] or b[1] + b[3] <= a[1])


def wm_free_crop(bgr, aspect, wm=None, margin=60, edge=30, scale=4, exclude=()):
    """Largest watermark-free rect (w/h = aspect) inside the fabric, source px [x,y,w,h].
    The chosen crop is re-checked with OCR on its own pixels."""
    wm = wm or detect(bgr)
    fab = cv2.erode(fabric_mask(bgr), np.ones((2 * edge + 1, 2 * edge + 1), np.uint8)) > 0
    # bottom selvage carries the printed SKU tag / "Made in Japan" text: keep 8% clear
    fab[int(fab.shape[0] * 0.92):] = False
    for x, y, w, h in exclude:   # caller-declared non-fabric (packaging, fold lines)
        fab[max(0, y):y + h, max(0, x):x + w] = False
    for x, y, w, h in wm['boxes']:
        fab[max(0, y - margin):y + h + margin, max(0, x - margin):x + w + margin] = False
    small = cv2.resize(fab.astype(np.uint8), (fab.shape[1] // scale, fab.shape[0] // scale),
                       interpolation=cv2.INTER_NEAREST) > 0
    r = largest_rect(small, aspect)
    if not r:
        return None, wm, None
    x, y, w, h = [v * scale for v in r]
    rect = [x + scale, y + scale, w - 2 * scale, int(round((w - 2 * scale) / aspect))]
    if any(_overlaps(rect, b) for b in wm['boxes']):
        return None, wm, None
    ocr = detect_ocr(bgr[rect[1]:rect[1] + rect[3], rect[0]:rect[0] + rect[2]])
    recheck = {'ocr_hit': ocr['hit'], 'ocr_reads': ocr['reads']}
    return (None if ocr['hit'] else rect), wm, recheck


def build_template(b_path, d_path, box=(740, 990, 510, 400), dxy=(29, -5)):
    rb = residual(cv2.cvtColor(_work(cv2.imread(b_path))[0], cv2.COLOR_BGR2GRAY))
    rd = residual(cv2.cvtColor(_work(cv2.imread(d_path))[0], cv2.COLOR_BGR2GRAY))
    x, y, w, h = box; dx, dy = dxy
    a = rb[y:y + h, x:x + w]; b = rd[y + dy:y + dy + h, x + dx:x + dx + w]
    agree = (np.sign(a) == np.sign(b)) & (np.abs(a) > 6) & (np.abs(b) > 6)
    t = np.where(agree, np.sign(a) * np.minimum(np.abs(a), np.abs(b)), 0).astype(np.float32)
    m = cv2.dilate(agree.astype(np.uint8), np.ones((3, 3), np.uint8))
    np.savez_compressed(TPL, tpl=t, mask=m)
    return {'template': TPL, 'agree_frac': round(float(agree.mean()), 4)}


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('mode', choices=['detect', 'crop', 'build-template'])
    ap.add_argument('images', nargs='+')
    ap.add_argument('--aspect', type=float, default=1.0)
    ap.add_argument('--no-ocr', action='store_true')
    a = ap.parse_args()
    heavy_guard.acquire('wm_detect')
    if a.mode == 'build-template':
        print(json.dumps(build_template(*a.images[:2])))
        return 0
    out = []
    for p in a.images:
        img = cv2.imread(p)
        if img is None:
            out.append({'path': p, 'error': 'unreadable'}); continue
        if a.mode == 'detect':
            out.append({'path': p, **detect(img, use_ocr=not a.no_ocr)})
        else:
            r, wm, rc = wm_free_crop(img, a.aspect)
            out.append({'path': p, 'crop': r, 'watermark': wm, 'crop_recheck': rc})
    print(json.dumps(out))
    return 0


if __name__ == '__main__':
    sys.exit(main())
