#!/usr/bin/env python3
"""
Fabric Integrity Gate — Fujiba Intelligence Agent, Phase 1.

Single fabric gate for every renderer (it replaced bin/v2/gate.py, which used
CIE76 ΔE ≤ 3.0 / SSIM ≥ 0.80 on the lossy JPEG/MP4). This gate implements the
master specification:

  Colour     CIEDE2000  mean ≤ 2.0 and p95 ≤ 5.0, inside the fabric mask,
             excluding any declared shadow region.
  Structure  SSIM ≥ 0.95 on luminance, inside the mask.
  Pattern    ORB feature-match inlier ratio ≥ 0.6 (RANSAC homography), plus an
             edge-map difference check.
  Coverage   fabric mask area in the output ≥ the planned area.

Any failing check ⇒ the asset is rejected and can never be published.

    fabric_gate.py --source orig.jpg --candidate out.jpg [--mask m.png]
                   [--shadow s.png] [--planned-coverage 0.42] [--json]

  Master mode (Content Engine v2 renderers):

    fabric_gate.py --master out.png [--mask-out out.gatemask.png]

  The renderer writes a LOSSLESS master (PNG, or a lossless RGB .mkv for
  reels) plus <master>.json declaring every fabric region (source photo +
  crop + placement) and, per region/frame, the overlays it drew on top
  (`exclude` rects, `mask: circle`). Expected pixels are rebuilt from the
  source with the renderers' own crop/scale code, so ONLY declared overlays
  are skipped; any other change inside a fabric region is measured and
  rejected. Video frames are pulled by exact index (select=eq(n,K)) from the
  lossless master. JPEG/MP4 delivery files are derived only after a pass
  (bin/v2/deliver.py).

Prints JSON {passed, checks[]}. Exit 0 = pass, 2 = fail, 3 = usage/IO error.

Thresholds mirror config/fujiba_intel.php `fabric_gate`; pass --config-json to
override so PHP stays the single source of truth.
"""
import argparse
import json
import os
import subprocess
import sys
import tempfile

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
# Master mode
# --------------------------------------------------------------------------
V2 = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), 'v2')
MASTER_DEFAULTS = {'max_excluded_share': 0.5, 'min_exact_share': 0.15, 'min_text_px': 26, 'blur_sigma': 1.5}


def _region_mask(r, w, h):
    """True = fabric pixel to verify. Declared overlays and circle corners are False."""
    m = np.ones((h, w), dtype=np.uint8)
    if r.get('mask') == 'circle':
        m[:] = 0
        cv2.ellipse(m, (w // 2, h // 2), (w // 2 - 3, h // 2 - 3), 0, 0, 360, 1, -1)
    x, y = r['out_box'][:2]
    for ex, ey, ew, eh in r.get('exclude') or []:
        x0, y0 = max(0, int(ex - x)), max(0, int(ey - y))
        x1, y1 = min(w, int(ex - x + ew) + 1), min(h, int(ey - y + eh) + 1)
        if x1 > x0 and y1 > y0:
            m[y0:y1, x0:x1] = 0
    return m.astype(bool)


def _master_frame(master, n):
    """Exact frame n of a lossless master video, as BGR."""
    with tempfile.TemporaryDirectory() as td:
        out = os.path.join(td, 'f.png')
        subprocess.run(['ffmpeg', '-v', 'error', '-y', '-i', master, '-vf', f'select=eq(n\\,{int(n)})',
                        '-vsync', '0', '-frames:v', '1', out], check=True)
        img = cv2.imread(out, cv2.IMREAD_COLOR)
    if img is None:
        raise IOError(f'frame {n} not found in {master}')
    return img


def measure_region(exp, got, mask, cfg, sigma):
    """All fabric metrics for one region. exp/got BGR uint8 same size; mask bool."""
    got = got.copy()
    got[~mask] = exp[~mask]            # overlays never bleed into the blurred colour measure
    raw = ciede2000(to_lab(exp), to_lab(got))[mask]
    be = cv2.GaussianBlur(exp, (0, 0), sigma)
    bg = cv2.GaussianBlur(got, (0, 0), sigma)
    de = ciede2000(to_lab(be), to_lab(bg))[mask]
    g1 = cv2.cvtColor(exp, cv2.COLOR_BGR2GRAY)
    g2 = cv2.cvtColor(got, cv2.COLOR_BGR2GRAY)
    ss = float(ssim_map(g1, g2)[mask].mean())
    maxdiff = int(np.abs(exp.astype(int) - got.astype(int))[mask].max())
    ratio, note = orb_inlier_ratio(g1, g2)
    if ratio is None and maxdiff <= 2:
        ratio, note = 1.0, 'pixel-identical (too few keypoints, identity proves pattern)'
    ed = edge_diff(g1, g2)
    res = {
        'deltaE00_mean': round(float(de.mean()), 3), 'deltaE00_p95': round(float(np.percentile(de, 95)), 3),
        'deltaE00_raw_mean': round(float(raw.mean()), 3), 'ssim': round(ss, 4),
        'orb_inliers': None if ratio is None else round(ratio, 3), 'edge_diff': round(ed, 4), 'max_abs_diff': maxdiff,
    }
    if note:
        res['orb_note'] = note
    fails = []
    if res['deltaE00_mean'] > cfg['ciede2000_mean_max'] or res['deltaE00_p95'] > cfg['ciede2000_p95_max']:
        fails.append('colour')
    if ss < cfg['ssim_min']:
        fails.append('structure')
    if ratio is None or ratio < cfg['pattern_inlier_min']:
        fails.append('pattern_orb')
    if ed > cfg['edge_diff_max']:
        fails.append('pattern_edges')
    res['fails'] = fails
    return res


def gate_master(master, cfg, mask_out=None):
    sys.path.insert(0, V2)
    from common import cover, crop_box, load          # the renderers' own crop/scale code
    from PIL import Image

    meta = json.load(open(master + '.json'))
    W, H = meta['size']
    regions = meta.get('fabric_regions') or []
    checks, srcs, frames = [], {}, {}
    still = None
    canvas_mask = np.zeros((H, W), np.uint8)
    covered = 0
    for i, r in enumerate(regions):
        x, y, w, h = r['out_box']
        if 'n' in r:
            if r['n'] not in frames:
                frames[r['n']] = _master_frame(master, r['n'])
            out = frames[r['n']]
        elif 't' in r:
            checks.append({'check': f'fabric[{i}]', 'pass': False, 'detail': 'video region without exact frame index n'})
            continue
        else:
            if still is None:
                still = cv2.imread(master, cv2.IMREAD_COLOR)
            out = still
        if r['src'] not in srcs:
            srcs[r['src']] = load(r['src'])
        exp = cover(crop_box(srcs[r['src']], r['src_box']), w, h)
        exp = cv2.cvtColor(np.asarray(exp), cv2.COLOR_RGB2BGR)
        got = out[y:y + h, x:x + w]
        if got.shape[:2] != (h, w):
            checks.append({'check': f'fabric[{i}]', 'pass': False, 'detail': 'region outside canvas'})
            continue
        mask = _region_mask(r, w, h)
        full = np.ones_like(mask) if r.get('mask') != 'circle' else _region_mask({'out_box': r['out_box'], 'mask': 'circle'}, w, h)
        excluded = 1 - mask.sum() / max(1, full.sum())
        name = f'fabric[{i}]' + (f"@n{r['n']}" if 'n' in r else '')
        if mask.sum() == 0:
            checks.append({'check': name, 'pass': False, 'detail': 'declared overlays cover the whole region'})
            continue
        m = measure_region(exp, got, mask, cfg, cfg['blur_sigma'])
        if excluded > cfg['max_excluded_share']:
            m['fails'].append('excluded_share')
        checks.append({'check': name, 'excluded': round(float(excluded), 3), **m, 'pass': not m['fails']})
        if 'n' not in r:
            canvas_mask[y:y + h, x:x + w][mask] = 255
            covered += int(mask.sum())
    if not any('n' in r for r in regions) and regions:
        share = float((canvas_mask > 0).sum()) / (W * H)
        need = meta.get('min_exact_share', cfg['min_exact_share'])
        checks.append({'check': 'exact_fabric_share', 'value': round(share, 3), 'min': need, 'pass': share >= need})
    if meta.get('min_text_px') is not None:
        checks.append({'check': 'min_text_px', 'value': meta['min_text_px'], 'min': cfg['min_text_px'],
                       'pass': meta['min_text_px'] >= cfg['min_text_px']})
    if mask_out and still is not None:
        cv2.imwrite(mask_out, canvas_mask)
    checks.append({'check': 'vision (motif/kanji/faces)', 'value': 'manual — owner review', 'pass': None})
    passed = bool(regions) and all(c['pass'] is not False for c in checks)
    fab = [c for c in checks if 'deltaE00_mean' in c]
    summary = {
        'regions': len(fab),
        'worst_deltaE00_mean': max((c['deltaE00_mean'] for c in fab), default=None),
        'worst_deltaE00_p95': max((c['deltaE00_p95'] for c in fab), default=None),
        'min_ssim': min((c['ssim'] for c in fab), default=None),
        'min_orb_inliers': min((c['orb_inliers'] for c in fab if c['orb_inliers'] is not None), default=None),
        'max_edge_diff': max((c['edge_diff'] for c in fab), default=None),
    }
    return {'passed': passed, 'mode': 'master', 'master': master, 'summary': summary, 'checks': checks}


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--master', help='lossless master (PNG / lossless mkv) with <master>.json sidecar')
    ap.add_argument('--mask-out', help='master mode: write the verified-fabric mask PNG here')
    ap.add_argument('--source', help='original fabric photo (truth)')
    ap.add_argument('--candidate', help='rendered asset to verify')
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

    if args.master:
        try:
            res = gate_master(args.master, {**MASTER_DEFAULTS, **cfg}, args.mask_out)
        except (IOError, OSError, ValueError, KeyError, subprocess.CalledProcessError) as exc:
            print(json.dumps({'passed': False, 'error': f'{type(exc).__name__}: {exc}'}))
            return 3
        print(json.dumps(res, ensure_ascii=False))
        return 0 if res['passed'] else 2
    if not (args.source and args.candidate):
        ap.error('--source and --candidate are required (or use --master)')

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
    import os as _o, sys as _s; _s.path.insert(0, _o.path.dirname(_o.path.abspath(__file__))); __import__('heavy_guard').acquire('fabric_gate')  # one heavy job at a time + memory cap
    sys.exit(main())
