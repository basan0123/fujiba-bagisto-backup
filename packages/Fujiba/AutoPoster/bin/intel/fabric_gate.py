#!/usr/bin/env python3
"""
Fabric Integrity Gate — Fujiba Intelligence Agent, Phase 1.

Stricter than bin/v2/gate.py (which stays untouched: it is another effort's
file and uses CIE76 ΔE ≤ 3.0 / SSIM ≥ 0.80). This gate implements the master
specification:

  Colour     CIEDE2000  mean ≤ 2.0 and p95 ≤ 5.0, inside the fabric mask,
             excluding any declared shadow region.
  Structure  SSIM ≥ 0.95 on luminance, inside the mask.
  Pattern    ORB feature-match inlier ratio ≥ 0.6 (RANSAC homography), plus an
             edge-map difference check.
  Coverage   fabric mask area in the output ≥ the planned area.

Any failing check ⇒ the asset is rejected and can never be published.

    fabric_gate.py --source orig.jpg --candidate out.jpg [--mask m.png]
                   [--shadow s.png] [--planned-coverage 0.42] [--json]

Prints JSON {passed, checks[]}. Exit 0 = pass, 2 = fail, 3 = usage/IO error.

Thresholds mirror config/fujiba_intel.php `fabric_gate`; pass --config-json to
override so PHP stays the single source of truth.
"""
import argparse
import json
import sys

import cv2
import numpy as np

DEFAULTS = {
    'ciede2000_mean_max': 2.0,
    'ciede2000_p95_max': 5.0,
    'ssim_min': 0.95,
    'pattern_inlier_min': 0.6,
    'edge_diff_max': 0.15,
}


# --------------------------------------------------------------------------
# Colour — CIEDE2000. Implemented here because the existing gate uses CIE76,
# which is a different formula, not a different threshold.
# --------------------------------------------------------------------------
def ciede2000(lab1, lab2):
    """Per-pixel ΔE00. Inputs are float arrays shaped (...,3) in CIE L*a*b*."""
    L1, a1, b1 = lab1[..., 0], lab1[..., 1], lab1[..., 2]
    L2, a2, b2 = lab2[..., 0], lab2[..., 1], lab2[..., 2]

    kL = kC = kH = 1.0

    C1 = np.sqrt(a1 ** 2 + b1 ** 2)
    C2 = np.sqrt(a2 ** 2 + b2 ** 2)
    Cbar = (C1 + C2) / 2.0

    Cbar7 = Cbar ** 7
    G = 0.5 * (1 - np.sqrt(Cbar7 / (Cbar7 + 25.0 ** 7 + 1e-12)))

    a1p, a2p = (1 + G) * a1, (1 + G) * a2
    C1p = np.sqrt(a1p ** 2 + b1 ** 2)
    C2p = np.sqrt(a2p ** 2 + b2 ** 2)

    h1p = np.degrees(np.arctan2(b1, a1p)) % 360
    h2p = np.degrees(np.arctan2(b2, a2p)) % 360

    dLp = L2 - L1
    dCp = C2p - C1p

    dhp = h2p - h1p
    dhp = np.where(dhp > 180, dhp - 360, dhp)
    dhp = np.where(dhp < -180, dhp + 360, dhp)
    # When either chroma is zero the hue difference is undefined → treat as 0.
    dhp = np.where((C1p * C2p) == 0, 0.0, dhp)
    dHp = 2 * np.sqrt(C1p * C2p) * np.sin(np.radians(dhp / 2.0))

    Lbarp = (L1 + L2) / 2.0
    Cbarp = (C1p + C2p) / 2.0

    hsum = h1p + h2p
    hdiff = np.abs(h1p - h2p)
    hbarp = np.where(
        (C1p * C2p) == 0, hsum,
        np.where(hdiff <= 180, hsum / 2.0,
                 np.where(hsum < 360, (hsum + 360) / 2.0, (hsum - 360) / 2.0)))

    T = (1
         - 0.17 * np.cos(np.radians(hbarp - 30))
         + 0.24 * np.cos(np.radians(2 * hbarp))
         + 0.32 * np.cos(np.radians(3 * hbarp + 6))
         - 0.20 * np.cos(np.radians(4 * hbarp - 63)))

    dTheta = 30 * np.exp(-(((hbarp - 275) / 25.0) ** 2))
    Cbarp7 = Cbarp ** 7
    Rc = 2 * np.sqrt(Cbarp7 / (Cbarp7 + 25.0 ** 7 + 1e-12))
    Sl = 1 + (0.015 * (Lbarp - 50) ** 2) / np.sqrt(20 + (Lbarp - 50) ** 2)
    Sc = 1 + 0.045 * Cbarp
    Sh = 1 + 0.015 * Cbarp * T
    Rt = -np.sin(np.radians(2 * dTheta)) * Rc

    return np.sqrt(
        (dLp / (kL * Sl)) ** 2
        + (dCp / (kC * Sc)) ** 2
        + (dHp / (kH * Sh)) ** 2
        + Rt * (dCp / (kC * Sc)) * (dHp / (kH * Sh))
    )


def to_lab(bgr):
    """OpenCV Lab is 0-255 scaled; convert back to real L*a*b* ranges."""
    lab = cv2.cvtColor(bgr.astype(np.uint8), cv2.COLOR_BGR2LAB).astype(np.float64)
    lab[..., 0] *= 100.0 / 255.0
    lab[..., 1] -= 128.0
    lab[..., 2] -= 128.0
    return lab


# --------------------------------------------------------------------------
# Structure — SSIM on luminance (Wang et al. 2004), gaussian-weighted.
# --------------------------------------------------------------------------
def ssim_map(a, b):
    a = a.astype(np.float64)
    b = b.astype(np.float64)
    C1, C2 = (0.01 * 255) ** 2, (0.03 * 255) ** 2

    mu_a = cv2.GaussianBlur(a, (11, 11), 1.5)
    mu_b = cv2.GaussianBlur(b, (11, 11), 1.5)
    mu_a2, mu_b2, mu_ab = mu_a ** 2, mu_b ** 2, mu_a * mu_b

    sa = cv2.GaussianBlur(a * a, (11, 11), 1.5) - mu_a2
    sb = cv2.GaussianBlur(b * b, (11, 11), 1.5) - mu_b2
    sab = cv2.GaussianBlur(a * b, (11, 11), 1.5) - mu_ab

    return ((2 * mu_ab + C1) * (2 * sab + C2)) / ((mu_a2 + mu_b2 + C1) * (sa + sb + C2))


# --------------------------------------------------------------------------
# Pattern — ORB + RANSAC inlier ratio, and an edge-map difference.
# --------------------------------------------------------------------------
def orb_inlier_ratio(src_gray, cand_gray):
    orb = cv2.ORB_create(nfeatures=2000)
    k1, d1 = orb.detectAndCompute(src_gray, None)
    k2, d2 = orb.detectAndCompute(cand_gray, None)

    if d1 is None or d2 is None or len(k1) < 8 or len(k2) < 8:
        return None, 'too few keypoints to judge'

    matcher = cv2.BFMatcher(cv2.NORM_HAMMING, crossCheck=True)
    matches = matcher.match(d1, d2)
    if len(matches) < 8:
        return None, 'too few matches to judge'

    src = np.float32([k1[m.queryIdx].pt for m in matches]).reshape(-1, 1, 2)
    dst = np.float32([k2[m.trainIdx].pt for m in matches]).reshape(-1, 1, 2)

    _, mask = cv2.findHomography(src, dst, cv2.RANSAC, 5.0)
    if mask is None:
        return 0.0, 'no consistent homography'

    return float(mask.sum()) / float(len(matches)), None


def edge_diff(src_gray, cand_gray):
    e1 = cv2.Canny(src_gray, 100, 200) > 0
    e2 = cv2.Canny(cand_gray, 100, 200) > 0
    union = np.logical_or(e1, e2).sum()
    if union == 0:
        return 0.0
    return float(np.logical_xor(e1, e2).sum()) / float(union)


# --------------------------------------------------------------------------
def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--source', required=True, help='original fabric photo (truth)')
    ap.add_argument('--candidate', required=True, help='rendered asset to verify')
    ap.add_argument('--mask', help='white = fabric region to check')
    ap.add_argument('--shadow', help='white = declared shadow layer, excluded from ΔE')
    ap.add_argument('--planned-coverage', type=float, default=None)
    ap.add_argument('--config-json', help='JSON overriding thresholds')
    args = ap.parse_args()

    cfg = dict(DEFAULTS)
    if args.config_json:
        try:
            cfg.update(json.loads(args.config_json))
        except ValueError as exc:
            print(json.dumps({'passed': False, 'error': f'bad --config-json: {exc}'}))
            return 3

    src = cv2.imread(args.source, cv2.IMREAD_COLOR)
    cand = cv2.imread(args.candidate, cv2.IMREAD_COLOR)
    if src is None or cand is None:
        print(json.dumps({'passed': False, 'error': 'could not read source or candidate'}))
        return 3

    if cand.shape[:2] != src.shape[:2]:
        cand = cv2.resize(cand, (src.shape[1], src.shape[0]), interpolation=cv2.INTER_AREA)

    h, w = src.shape[:2]
    if args.mask:
        m = cv2.imread(args.mask, cv2.IMREAD_GRAYSCALE)
        if m is None:
            print(json.dumps({'passed': False, 'error': 'could not read mask'}))
            return 3
        mask = cv2.resize(m, (w, h), interpolation=cv2.INTER_NEAREST) > 127
    else:
        mask = np.ones((h, w), dtype=bool)

    colour_mask = mask.copy()
    if args.shadow:
        s = cv2.imread(args.shadow, cv2.IMREAD_GRAYSCALE)
        if s is not None:
            colour_mask &= ~(cv2.resize(s, (w, h), interpolation=cv2.INTER_NEAREST) > 127)

    checks = []

    # 1. Colour (CIEDE2000)
    if colour_mask.sum() == 0:
        checks.append({'check': 'colour_ciede2000', 'pass': False,
                       'detail': 'mask excludes every pixel'})
    else:
        de = ciede2000(to_lab(src), to_lab(cand))[colour_mask]
        de_mean, de_p95 = float(de.mean()), float(np.percentile(de, 95))
        checks.append({
            'check': 'colour_ciede2000',
            'mean': round(de_mean, 3), 'p95': round(de_p95, 3),
            'mean_max': cfg['ciede2000_mean_max'], 'p95_max': cfg['ciede2000_p95_max'],
            'pass': de_mean <= cfg['ciede2000_mean_max'] and de_p95 <= cfg['ciede2000_p95_max'],
        })

    # 2. Structure (SSIM on luminance)
    g1 = cv2.cvtColor(src, cv2.COLOR_BGR2GRAY)
    g2 = cv2.cvtColor(cand, cv2.COLOR_BGR2GRAY)
    if mask.sum() == 0:
        checks.append({'check': 'structure_ssim', 'pass': False, 'detail': 'empty mask'})
    else:
        s_val = float(ssim_map(g1, g2)[mask].mean())
        checks.append({'check': 'structure_ssim', 'ssim': round(s_val, 4),
                       'min': cfg['ssim_min'], 'pass': s_val >= cfg['ssim_min']})

    # 3. Pattern (ORB inliers + edge map), restricted to the fabric bounding box
    ys, xs = np.where(mask)
    if ys.size:
        y0, y1, x0, x1 = ys.min(), ys.max() + 1, xs.min(), xs.max() + 1
        r1, r2 = g1[y0:y1, x0:x1], g2[y0:y1, x0:x1]

        ratio, note = orb_inlier_ratio(r1, r2)
        if ratio is None:
            # Undecidable is NOT a pass — a flat region cannot prove its pattern.
            checks.append({'check': 'pattern_orb_inliers', 'pass': False,
                           'detail': note, 'min': cfg['pattern_inlier_min']})
        else:
            checks.append({'check': 'pattern_orb_inliers', 'inlier_ratio': round(ratio, 3),
                           'min': cfg['pattern_inlier_min'],
                           'pass': ratio >= cfg['pattern_inlier_min']})

        ed = edge_diff(r1, r2)
        checks.append({'check': 'pattern_edge_diff', 'edge_diff': round(ed, 4),
                       'max': cfg['edge_diff_max'], 'pass': ed <= cfg['edge_diff_max']})

    # 4. Coverage
    if args.planned_coverage is not None:
        actual = float(mask.sum()) / float(h * w)
        checks.append({'check': 'coverage', 'actual': round(actual, 4),
                       'planned': args.planned_coverage,
                       'pass': actual >= args.planned_coverage - 1e-6})

    passed = all(c.get('pass') for c in checks) and bool(checks)
    print(json.dumps({'passed': passed, 'checks': checks}, ensure_ascii=False))
    return 0 if passed else 2


if __name__ == '__main__':
    sys.exit(main())
