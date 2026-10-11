#!/usr/bin/env python3
"""
M — Product photography intelligence.

Everything here is MEASURED locally (cv2 + numpy). No AI call, no network, so
it is a fact rather than an inference. Vision-model checks (watermark text,
packaging) are reported as null + 'needs_vision' rather than guessed.

    photo_audit.py <image> [<image> ...] [--json]

Per image prints: width, height, sharpness (variance of Laplacian), exposure,
colour cast, fabric visibility, background quality, perspective, a 0-1 quality
score, and whether a reshoot is recommended plus why.

Thresholds mirror config/fujiba_intel.php photo_audit; override with
--config-json so PHP stays the single source of truth.
"""
import argparse
import hashlib
import json
import os
import sys

import cv2
import numpy as np

DEFAULTS = {
    'min_long_side': 1600,
    'min_sharpness': 100.0,
    'min_fabric_visibility': 0.60,
}


def phash(gray):
    """64-bit perceptual hash — the cache key for 'has this photo changed'."""
    small = cv2.resize(gray, (32, 32), interpolation=cv2.INTER_AREA).astype(np.float32)
    dct = cv2.dct(small)[:8, :8]
    med = np.median(dct[1:])
    bits = (dct > med).flatten()
    return ''.join('1' if b else '0' for b in bits)


def sha256(path):
    h = hashlib.sha256()
    with open(path, 'rb') as fh:
        for chunk in iter(lambda: fh.read(65536), b''):
            h.update(chunk)
    return h.hexdigest()


def fabric_visibility(bgr):
    """
    Share of the frame that looks like fabric rather than backdrop, label or
    packaging. Flat near-white / flat grey regions are treated as non-fabric,
    matching the heuristic bin/post_photo.py already uses.
    """
    hsv = cv2.cvtColor(bgr, cv2.COLOR_BGR2HSV)
    h, s, v = hsv[..., 0], hsv[..., 1] / 255.0, hsv[..., 2] / 255.0

    flat_white = (v > 0.82) & (s < 0.12)
    flat_grey = (s < 0.06) & (v > 0.35) & (v < 0.90)

    # Very low local variance is backdrop even when it is not white.
    gray = cv2.cvtColor(bgr, cv2.COLOR_BGR2GRAY).astype(np.float32)
    local_var = cv2.GaussianBlur(gray * gray, (21, 21), 0) - cv2.GaussianBlur(gray, (21, 21), 0) ** 2
    flat = local_var < 25.0

    non_fabric = flat_white | flat_grey | flat
    return float(1.0 - non_fabric.mean())


def colour_cast(bgr):
    """0 = neutral, 1 = heavy cast. Mean a*/b* distance from neutral in Lab."""
    lab = cv2.cvtColor(bgr, cv2.COLOR_BGR2LAB).astype(np.float32)
    a = lab[..., 1].mean() - 128.0
    b = lab[..., 2].mean() - 128.0
    return float(min(1.0, np.sqrt(a * a + b * b) / 40.0))


def exposure(gray):
    """Share of pixels clipped at either end; lower is better."""
    g = gray.astype(np.float32) / 255.0
    return float(((g < 0.02) | (g > 0.98)).mean())


def background_quality(bgr):
    """Border uniformity — a clean sweep scores high, a cluttered room low."""
    h, w = bgr.shape[:2]
    band = max(4, min(h, w) // 20)
    edges = np.concatenate([
        bgr[:band].reshape(-1, 3), bgr[-band:].reshape(-1, 3),
        bgr[:, :band].reshape(-1, 3), bgr[:, -band:].reshape(-1, 3),
    ])
    return float(max(0.0, 1.0 - edges.std(axis=0).mean() / 60.0))


def perspective_quality(gray):
    """
    Flat-lay fabric should show mostly axis-aligned structure. Strong skew in
    the dominant line angles suggests an awkward angled shot.
    """
    edges = cv2.Canny(gray, 80, 200)
    lines = cv2.HoughLines(edges, 1, np.pi / 180, threshold=120)
    if lines is None or len(lines) < 4:
        return 0.6  # not enough structure to judge — neutral, not a free pass

    angles = np.array([l[0][1] for l in lines])
    dev = np.minimum.reduce([
        np.abs(angles - 0), np.abs(angles - np.pi / 2), np.abs(angles - np.pi),
    ])
    return float(max(0.0, 1.0 - (dev.mean() / (np.pi / 4))))


def audit(path, cfg):
    bgr = cv2.imread(path, cv2.IMREAD_COLOR)
    if bgr is None:
        return {'path': path, 'error': 'unreadable'}

    h, w = bgr.shape[:2]
    gray = cv2.cvtColor(bgr, cv2.COLOR_BGR2GRAY)

    sharp = float(cv2.Laplacian(gray, cv2.CV_64F).var())
    vis = fabric_visibility(bgr)
    cast = colour_cast(bgr)
    expo = exposure(gray)
    bg = background_quality(bgr)
    persp = perspective_quality(gray)
    long_side = max(h, w)

    # Weighted score; resolution and visibility matter most because they cap
    # what any downstream creative can do.
    score = float(np.clip(
        0.30 * min(1.0, long_side / cfg['min_long_side'])
        + 0.25 * min(1.0, sharp / (cfg['min_sharpness'] * 3))
        + 0.25 * vis
        + 0.10 * bg
        + 0.10 * (1.0 - cast)
        - 0.20 * expo,
        0.0, 1.0))

    reasons = []
    if long_side < cfg['min_long_side']:
        reasons.append(f'low resolution ({w}x{h}, long side < {cfg["min_long_side"]}px)')
    if sharp < cfg['min_sharpness']:
        reasons.append(f'soft/blurred (sharpness {sharp:.0f} < {cfg["min_sharpness"]:.0f})')
    if vis < cfg['min_fabric_visibility']:
        reasons.append(f'fabric covers only {vis * 100:.0f}% of frame')
    if cast > 0.5:
        reasons.append(f'strong colour cast ({cast:.2f})')
    if expo > 0.12:
        reasons.append(f'clipped exposure ({expo * 100:.0f}% of pixels)')

    setup = None
    if reasons:
        setup = ('flat lay on neutral ground, daylight ~5000K diffused, colour '
                 'checker in frame, long side ≥ 2000px, plus a 45° drape '
                 'close-up showing weave')

    return {
        'path': path,
        'image_hash': phash(gray),
        'image_sha256': sha256(path),
        'source_width': w,
        'source_height': h,
        'sharpness': round(sharp, 2),
        'exposure_clipped': round(expo, 4),
        'colour_quality': round(1.0 - cast, 4),
        'fabric_visibility': round(vis, 4),
        'background_quality': round(bg, 4),
        'perspective_quality': round(persp, 4),
        # Need a vision model; reported honestly rather than guessed.
        'watermark_detected': None,
        'packaging_obstruction': None,
        'needs_vision': ['watermark_detected', 'packaging_obstruction'],
        'photo_quality_score': round(score, 4),
        'reshoot_required': bool(reasons),
        'reshoot_reason': '; '.join(reasons) or None,
        'recommended_photo_setup': setup,
    }


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('images', nargs='+')
    ap.add_argument('--config-json')
    args = ap.parse_args()

    cfg = dict(DEFAULTS)
    if args.config_json:
        cfg.update(json.loads(args.config_json))

    out = [audit(p, cfg) for p in args.images if os.path.exists(p)]
    print(json.dumps(out, ensure_ascii=False))
    return 0


if __name__ == '__main__':
    import os as _o, sys as _s; _s.path.insert(0, _o.path.dirname(_o.path.abspath(__file__))); __import__('heavy_guard').acquire('photo_audit')  # one heavy job at a time + memory cap
    sys.exit(main())
