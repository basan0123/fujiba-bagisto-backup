#!/usr/bin/env python3
"""INFORMATION-ONLY metrics for an AI styling-concept garment vs the real fabric
(never a gate: AI-drawn fabric is untrusted). Read-only on sources.

  concept_eval.py image  --source SRC_CROP --ai AI_IMG --outdir DIR --name NAME [--correct]
  concept_eval.py frames --source SRC_CROP --frames f0.png f1.png ... --outdir DIR --name NAME

Garment mask = GrabCut seeded by the fabric's dark ground (L*<22) — valid for
dark-ground prints such as FV-89-B.
Per-pixel ΔE00 = distance of each garment pixel to the nearest source palette
colour; the same statistic on the source crop itself is reported as baseline.
--correct: histogram-match the AI garment pixels ONLY (per Lab channel, CDF
mapping to the source crop's distribution), blended back with a feathered mask;
real fabric pixels are never touched (the source is only read).
Seams: rows/cols where the garment's mean |gradient| along a straight line is a
robust outlier (> median + 6*MAD over the garment span) are listed.
"""
import argparse, json, os, sys
import cv2
import numpy as np

HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, HERE)
import heavy_guard  # noqa: E402
from fabric_gate import ciede2000  # noqa: E402
from ai_pattern_compare import lab_f, palette, orb_stats  # noqa: E402



def src_palette(src, k=12):
    lab = lab_f(cv2.resize(src, (240, int(240 * src.shape[0] / src.shape[1])), interpolation=cv2.INTER_AREA))
    px = lab.reshape(-1, 3).astype(np.float32)
    cv2.setRNGSeed(7)
    _, lbl, cen = cv2.kmeans(px, k, None, (3, 50, 0.2), 5, cv2.KMEANS_PP_CENTERS)
    return cen


def nearest_de(lab, cen):
    flat = lab.reshape(-1, 3)
    best = np.full(len(flat), 1e9, np.float32)
    for c in cen:
        best = np.minimum(best, ciede2000(flat, np.broadcast_to(c, flat.shape)))
    return best.reshape(lab.shape[:2])


def garment_mask(img, cen=None):
    """GrabCut seeded by the fabric's dark ground (L*<22, absent from wall/floor/skin):
    rect = 1-99 pct bbox of dark pixels, dark pixels = definite foreground."""
    f = 512 / max(img.shape[:2])
    sm = cv2.resize(img, None, fx=f, fy=f, interpolation=cv2.INTER_AREA)
    dark = lab_f(sm)[..., 0] < 22
    dark = cv2.morphologyEx(dark.astype(np.uint8), cv2.MORPH_OPEN, np.ones((3, 3), np.uint8)) > 0
    ys, xs = np.where(dark)
    x0, x1 = np.percentile(xs, [1, 99]).astype(int); y0, y1 = np.percentile(ys, [0.5, 99.5]).astype(int)
    pad = 6
    x0, y0 = max(0, x0 - pad), max(0, y0 - pad)
    x1, y1 = min(sm.shape[1] - 1, x1 + pad), min(sm.shape[0] - 1, y1 + pad)
    gm = np.full(sm.shape[:2], cv2.GC_BGD, np.uint8)
    gm[y0:y1, x0:x1] = cv2.GC_PR_FGD
    gm[dark & (gm == cv2.GC_PR_FGD)] = cv2.GC_FGD
    bg, fg = np.zeros((1, 65), np.float64), np.zeros((1, 65), np.float64)
    cv2.grabCut(sm, gm, None, bg, fg, 5, cv2.GC_INIT_WITH_MASK)
    m = ((gm == cv2.GC_FGD) | (gm == cv2.GC_PR_FGD)).astype(np.uint8) * 255
    n, lbl, st, _ = cv2.connectedComponentsWithStats(m)
    if n > 1:
        m = (lbl == 1 + int(np.argmax(st[1:, cv2.CC_STAT_AREA]))).astype(np.uint8) * 255
    m = cv2.morphologyEx(m, cv2.MORPH_CLOSE, np.ones((9, 9), np.uint8))
    m = cv2.erode(m, np.ones((3, 3), np.uint8))
    m = cv2.resize(m, (img.shape[1], img.shape[0]), interpolation=cv2.INTER_LINEAR)
    return m > 127


def weighted_palette_de(src, ai_pix_bgr):
    """share-weighted ΔE00 of AI k=6 palette vs source k=6 palette (greedy match)."""
    (ps, _), (pa, sh) = palette(src), palette(ai_pix_bgr)
    used, tot = set(), 0.0
    for c, s in zip(pa, sh):
        d = [(float(ciede2000(np.array([c]), np.array([sc]))[0]), j) for j, sc in enumerate(ps) if j not in used]
        dv, j = min(d); used.add(j); tot += dv * s
    return round(tot, 3)


def strip_from_mask(img, mask):
    """bounding box contents with non-garment pixels replaced by garment median (for palette/ORB)."""
    ys, xs = np.where(mask)
    y0, y1, x0, x1 = ys.min(), ys.max() + 1, xs.min(), xs.max() + 1
    crop = img[y0:y1, x0:x1].copy()
    mk = mask[y0:y1, x0:x1]
    med = np.median(crop[mk], axis=0).astype(np.uint8)
    crop[~mk] = med
    return crop, (int(x0), int(y0), int(x1 - x0), int(y1 - y0))


def seams(img, mask):
    g = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY).astype(np.float32)
    gy = np.abs(cv2.Sobel(g, cv2.CV_32F, 0, 1, ksize=3))
    gx = np.abs(cv2.Sobel(g, cv2.CV_32F, 1, 0, ksize=3))
    out = {}
    for name, grad, axis in (('horizontal_rows', gy, 1), ('vertical_cols', gx, 0)):
        cnt = mask.sum(axis=axis)
        prof = np.where(cnt > 0.5 * cnt.max(), (grad * mask).sum(axis=axis) / np.maximum(cnt, 1), np.nan)
        v = prof[~np.isnan(prof)]
        med = np.median(v); mad = np.median(np.abs(v - med)) + 1e-6
        idx = np.where(prof > med + 6 * mad)[0]
        out[name] = {'positions': idx.tolist()[:40], 'median': round(float(med), 2),
                     'max_z': round(float((np.nanmax(prof) - med) / mad), 2)}
    return out


def hist_match(img, mask, src):
    lab = cv2.cvtColor(img, cv2.COLOR_BGR2LAB)
    slab = cv2.cvtColor(src, cv2.COLOR_BGR2LAB)
    out = lab.copy()
    for c in range(3):
        a = lab[..., c][mask]; s = slab[..., c].ravel()
        ah = np.bincount(a, minlength=256).cumsum() / a.size
        sh = np.bincount(s, minlength=256).cumsum() / s.size
        lut = np.searchsorted(sh, ah).clip(0, 255).astype(np.uint8)
        out[..., c] = lut[lab[..., c]]
    corr = cv2.cvtColor(out, cv2.COLOR_LAB2BGR)
    feather = cv2.GaussianBlur(mask.astype(np.float32), (0, 0), 2)[..., None]
    return (corr * feather + img * (1 - feather)).astype(np.uint8)


def summarise(src, img, cen, src_base):
    mask = garment_mask(img, cen)
    de = nearest_de(lab_f(img), cen)[mask]
    strip, bbox = strip_from_mask(img, mask)
    return mask, {'mask_frac': round(float(mask.mean()), 4), 'bbox': bbox,
                  'de00_nearest_palette_mean': round(float(de.mean()), 3),
                  'de00_nearest_palette_p95': round(float(np.percentile(de, 95)), 3),
                  'source_baseline_mean': src_base[0], 'source_baseline_p95': src_base[1],
                  'weighted_palette_de00': weighted_palette_de(src, strip),
                  'orb_vs_source': orb_stats(src, strip)}


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('mode', choices=['image', 'frames'])
    ap.add_argument('--source', required=True); ap.add_argument('--outdir', required=True)
    ap.add_argument('--name', required=True); ap.add_argument('--ai')
    ap.add_argument('--frames', nargs='*', default=[]); ap.add_argument('--correct', action='store_true')
    a = ap.parse_args()
    heavy_guard.acquire('concept_eval')
    src = cv2.imread(a.source)
    cen = src_palette(src)
    sb = nearest_de(lab_f(src), cen)
    src_base = (round(float(sb.mean()), 3), round(float(np.percentile(sb, 95)), 3))
    res = {'source': a.source, 'note': 'information only, AI fabric untrusted'}
    if a.mode == 'image':
        img = cv2.imread(a.ai)
        mask, res['raw'] = summarise(src, img, cen, src_base)
        res['raw']['seams'] = seams(img, mask)
        cv2.imwrite(os.path.join(a.outdir, a.name + '_mask.png'), mask.astype(np.uint8) * 255)
        if a.correct:
            corr = hist_match(img, mask, src)
            p = os.path.join(a.outdir, a.name + '_corrected.png'); cv2.imwrite(p, corr)
            _, res['corrected'] = summarise(src, corr, cen, src_base)
            res['corrected']['path'] = p
    else:
        stats, means = [], []
        for f in a.frames:
            img = cv2.imread(f)
            mask, s = summarise(src, img, cen, src_base)
            s['frame'] = f; stats.append(s)
            means.append(lab_f(img)[mask].mean(0))
        res['frames'] = stats
        res['frame_mean_lab_de00'] = [[round(float(ciede2000(np.array([means[i]]), np.array([means[j]]))[0]), 3)
                                       for j in range(len(means))] for i in range(len(means))]
    print(json.dumps(res, indent=1))
    with open(os.path.join(a.outdir, a.name + '_eval.json'), 'w') as fh:
        json.dump(res, fh, indent=1)


if __name__ == '__main__':
    sys.exit(main())
