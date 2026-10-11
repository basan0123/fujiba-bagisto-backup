#!/usr/bin/env python3
"""
O/P — fabric matching from partial views.

Answers "is this the same fabric?" from as little as a crop, using several
INDEPENDENT signals. Colour alone may never decide: the spec is explicit, and
in practice many kimono prints share a ground colour while sharing nothing
else, so a colour-only verdict would be confidently wrong.

    fabric_match.py --reference r.jpg --candidate c.jpg [--json]

Signals:
  colour      Lab histogram intersection                     (weak alone)
  motif/geom  ORB keypoints + RANSAC homography inlier ratio  (strong)
  texture     LBP-like gradient-orientation histogram         (medium)
  frequency   log-magnitude FFT radial profile correlation    (medium)
  edges       Canny density ratio                             (weak)

Verdict requires agreement across signal FAMILIES, not one high score:
  exact_match     geometry ≥ .55 AND ≥3 families agree AND colour ≥ .55
  probable_match  geometry ≥ .35 AND ≥2 families agree
  similar_pattern geometry ≥ .20 OR (texture+frequency strong)
  similar_style   colour ≥ .75 only — explicitly NOT a product claim
  unrelated       nothing agrees
Everything also reports matched_region_percentage and contradicting_features.
"""
import argparse
import json
import sys

import cv2
import numpy as np


def load(path, max_side=900):
    img = cv2.imread(path, cv2.IMREAD_COLOR)
    if img is None:
        return None
    h, w = img.shape[:2]
    if max(h, w) > max_side:
        s = max_side / max(h, w)
        img = cv2.resize(img, (int(w * s), int(h * s)), interpolation=cv2.INTER_AREA)
    return img


def colour_similarity(a, b):
    """Histogram intersection in Lab. Deliberately never decisive on its own."""
    ha = cv2.calcHist([cv2.cvtColor(a, cv2.COLOR_BGR2LAB)], [0, 1, 2], None,
                      [8, 8, 8], [0, 256] * 3)
    hb = cv2.calcHist([cv2.cvtColor(b, cv2.COLOR_BGR2LAB)], [0, 1, 2], None,
                      [8, 8, 8], [0, 256] * 3)
    cv2.normalize(ha, ha)
    cv2.normalize(hb, hb)
    return float(cv2.compareHist(ha, hb, cv2.HISTCMP_INTERSECT) /
                 max(1e-9, min(ha.sum(), hb.sum())))


def geometry_similarity(a, b):
    """
    ORB + RANSAC. Returns (inlier_ratio, inliers, matches, covered_fraction).
    covered_fraction estimates how much of the candidate the matched region
    spans — the "how much of the fabric did we actually see" number.
    """
    ga = cv2.cvtColor(a, cv2.COLOR_BGR2GRAY)
    gb = cv2.cvtColor(b, cv2.COLOR_BGR2GRAY)

    orb = cv2.ORB_create(nfeatures=3000)
    k1, d1 = orb.detectAndCompute(ga, None)
    k2, d2 = orb.detectAndCompute(gb, None)

    if d1 is None or d2 is None or len(k1) < 10 or len(k2) < 10:
        return 0.0, 0, 0, 0.0

    bf = cv2.BFMatcher(cv2.NORM_HAMMING, crossCheck=True)
    matches = bf.match(d1, d2)
    if len(matches) < 10:
        return 0.0, 0, len(matches), 0.0

    src = np.float32([k1[m.queryIdx].pt for m in matches]).reshape(-1, 1, 2)
    dst = np.float32([k2[m.trainIdx].pt for m in matches]).reshape(-1, 1, 2)

    _, mask = cv2.findHomography(src, dst, cv2.RANSAC, 6.0)
    if mask is None:
        return 0.0, 0, len(matches), 0.0

    inliers = int(mask.sum())
    ratio = inliers / len(matches)

    covered = 0.0
    if inliers >= 4:
        pts = dst[mask.ravel() == 1].reshape(-1, 2)
        x0, y0 = pts.min(axis=0)
        x1, y1 = pts.max(axis=0)
        covered = float(((x1 - x0) * (y1 - y0)) / (b.shape[0] * b.shape[1]))

    return float(ratio), inliers, len(matches), min(1.0, covered)


def texture_similarity(a, b):
    """Gradient-orientation histogram — weave and stroke character."""
    def hist(img):
        g = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
        gx = cv2.Sobel(g, cv2.CV_32F, 1, 0, ksize=3)
        gy = cv2.Sobel(g, cv2.CV_32F, 0, 1, ksize=3)
        mag, ang = cv2.cartToPolar(gx, gy, angleInDegrees=True)
        h, _ = np.histogram(ang, bins=36, range=(0, 360), weights=mag)
        return h / max(1e-9, h.sum())

    ha, hb = hist(a), hist(b)
    return float(1.0 - 0.5 * np.abs(ha - hb).sum())


def frequency_similarity(a, b):
    """Radial FFT profile — how often the motif repeats."""
    def profile(img):
        g = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY).astype(np.float32)
        g = cv2.resize(g, (256, 256), interpolation=cv2.INTER_AREA)
        f = np.fft.fftshift(np.abs(np.fft.fft2(g)))
        f = np.log1p(f)
        cy, cx = 128, 128
        y, x = np.indices(f.shape)
        r = np.sqrt((y - cy) ** 2 + (x - cx) ** 2).astype(int)
        prof = np.bincount(r.ravel(), f.ravel()) / np.maximum(1, np.bincount(r.ravel()))
        prof = prof[:100]
        return prof / max(1e-9, np.linalg.norm(prof))

    pa, pb = profile(a), profile(b)
    return float(max(0.0, np.dot(pa, pb)))


def edge_similarity(a, b):
    da = (cv2.Canny(cv2.cvtColor(a, cv2.COLOR_BGR2GRAY), 100, 200) > 0).mean()
    db = (cv2.Canny(cv2.cvtColor(b, cv2.COLOR_BGR2GRAY), 100, 200) > 0).mean()
    return float(1.0 - abs(da - db) / max(da, db, 1e-9))


def classify(sig):
    """Verdict requires AGREEMENT ACROSS FAMILIES — never colour alone."""
    colour, geom, tex, freq, edge = (sig['colour_similarity'], sig['geometry_similarity'],
                                     sig['texture_similarity'], sig['frequency_similarity'],
                                     sig['edge_similarity'])

    families = {
        'colour': colour >= 0.55,
        'geometry': geom >= 0.35,
        'texture': tex >= 0.60,
        'frequency': freq >= 0.90,
        'edges': edge >= 0.70,
    }
    agree = sum(1 for v in families.values() if v)

    matched = [k for k, v in families.items() if v]
    contradicting = [k for k, v in families.items() if not v]

    # Geometry is the only signal that can distinguish "this print" from "a
    # print that looks a bit like it", so it gates every same-product verdict.
    # Below GEOM_FLOOR the keypoint agreement is indistinguishable from noise
    # (measured: unrelated fabrics score 0.01-0.02), and no amount of colour
    # or texture agreement is allowed to substitute for it.
    GEOM_FLOOR = 0.05

    if geom >= 0.55 and agree >= 3 and colour >= 0.55:
        verdict, conf = 'exact_match', min(0.99, 0.5 + geom / 2)
    elif geom >= 0.35 and agree >= 2:
        verdict, conf = 'probable_match', min(0.85, 0.3 + geom / 2)
    elif geom >= 0.20:
        verdict, conf = 'similar_pattern', 0.45
    elif geom >= GEOM_FLOOR:
        # Some real keypoint agreement, but too little to claim the pattern.
        # Typical of a very small crop: honest answer is "cannot tell".
        verdict, conf = 'unknown', round(0.15 + geom, 3)
    elif tex >= 0.70 and freq >= 0.95 and colour >= 0.55:
        # No geometric evidence at all; at best a family resemblance.
        verdict, conf = 'similar_style', 0.3
    elif colour >= 0.75:
        # Colour agreement alone is a styling observation, never a claim that
        # this is the same product.
        verdict, conf = 'similar_style', 0.25
    else:
        verdict, conf = 'unrelated', 0.1

    return verdict, round(conf, 3), matched, contradicting


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--reference', required=True)
    ap.add_argument('--candidate', required=True)
    args = ap.parse_args()

    a, b = load(args.reference), load(args.candidate)
    if a is None or b is None:
        print(json.dumps({'error': 'could not read reference or candidate'}))
        return 3

    geom, inliers, matches, covered = geometry_similarity(a, b)

    sig = {
        'colour_similarity': round(colour_similarity(a, b), 4),
        'geometry_similarity': round(geom, 4),
        'texture_similarity': round(texture_similarity(a, b), 4),
        'frequency_similarity': round(frequency_similarity(a, b), 4),
        'edge_similarity': round(edge_similarity(a, b), 4),
    }

    verdict, conf, matched, contradicting = classify(sig)

    print(json.dumps({
        **sig,
        'match_type': verdict,
        'match_confidence': conf,
        'matched_region_percentage': round(covered * 100, 2),
        'matched_features': matched,
        'contradicting_features': contradicting,
        'orb_inliers': inliers,
        'orb_matches': matches,
        # Stated outright so no caller can mistake this for a same-product claim.
        'note': 'a visual match is not proof of the same product; colour alone never decides',
    }, ensure_ascii=False))
    return 0


if __name__ == '__main__':
    import os as _o, sys as _s; _s.path.insert(0, _o.path.dirname(_o.path.abspath(__file__))); __import__('heavy_guard').acquire('fabric_match')  # one heavy job at a time + memory cap
    sys.exit(main())
