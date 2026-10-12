#!/usr/bin/env python3
"""WS-L independent identity check of a twin render against the ORIGINAL product photo(s).

    twin_check.py --candidate out.png [--exclude x,y,w,h ...] --photo P.jpg [--photo P2.jpg]
                  [--tile-json tile.png.json] --out check.json

Why: fabric_gate --master rebuilds the expected pixels from the same render/tile, so it only
proves the composition is untouched (circular). Identity needs an untouched photo:
  1. fabric_match.py (ORB + RANSAC inliers, colour/texture/frequency families) candidate vs photo;
  2. dE00 of matched regions: SIFT + RANSAC homography candidate -> photo, warp, CIEDE2000
     (sigma 1.5, the gate's blur) on the overlap inside the photo's fabric mask;
  3. held-out prediction (photo = tile source): predict every pixel of the source region from
     the tile by periodic extension and compare; pixels OUTSIDE the cut tile were never used.
"""
import argparse
import json
import os
import subprocess
import sys

import cv2
import numpy as np

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import twin_common as T  # noqa: E402
from twin_audit import fabric_mask  # noqa: E402

HERE = os.path.dirname(os.path.abspath(__file__))


def fabric_match(ref, cand):
    r = subprocess.run(['python3', os.path.join(HERE, 'fabric_match.py'), '--reference', ref, '--candidate', cand],
                       capture_output=True, text=True, timeout=600)
    try:
        return json.loads(r.stdout.strip().splitlines()[-1])
    except Exception:  # noqa: BLE001
        return {'error': (r.stdout + r.stderr)[-400:]}


def register(photo, cand, cand_valid):
    """SIFT + ratio test + RANSAC homography candidate -> photo. Returns H, stats."""
    sift = cv2.SIFT_create(6000)
    gp = cv2.cvtColor(photo, cv2.COLOR_BGR2GRAY)
    gc = cv2.cvtColor(cand, cv2.COLOR_BGR2GRAY)
    kp, dp = sift.detectAndCompute(gp, None)
    kc, dc = sift.detectAndCompute(gc, cand_valid.astype(np.uint8) * 255)
    if dp is None or dc is None or len(kp) < 10 or len(kc) < 10:
        return None, {'matches': 0, 'reason': 'too few keypoints'}
    m = cv2.BFMatcher().knnMatch(dc, dp, k=2)
    good = [a for a, b in (x for x in m if len(x) == 2) if a.distance < 0.8 * b.distance]
    if len(good) < 12:
        return None, {'matches': len(good), 'reason': 'too few distinctive matches'}
    src = np.float32([kc[g.queryIdx].pt for g in good]).reshape(-1, 1, 2)
    dst = np.float32([kp[g.trainIdx].pt for g in good]).reshape(-1, 1, 2)
    H, inl = cv2.findHomography(src, dst, cv2.RANSAC, 4.0)
    if H is None:
        return None, {'matches': len(good), 'reason': 'no consistent homography'}
    return H, {'matches': len(good), 'inliers': int(inl.sum()), 'inlier_ratio': round(float(inl.sum()) / len(good), 3)}


def de_matched(photo, cand, H, cand_valid):
    h, w = photo.shape[:2]
    cw = cv2.warpPerspective(cand, H, (w, h), flags=cv2.INTER_LINEAR)
    vw = cv2.warpPerspective(cand_valid.astype(np.uint8), H, (w, h), flags=cv2.INTER_NEAREST)
    vw = cv2.erode(vw, np.ones((7, 7), np.uint8))
    fab, _, _, _ = fabric_mask(T.lab32(photo), photo)
    m = (vw > 0) & (fab > 0)
    if m.sum() < 2000:
        return {'overlap_px': int(m.sum()), 'reason': 'overlap too small'}
    d = T.de00(photo, cw)[m]
    c = T.ncc(T.lab32(photo)[..., 0], T.lab32(cw)[..., 0], m)
    return {'overlap_px': int(m.sum()), 'overlap_share_of_photo': round(float(m.mean()), 3),
            'de00_mean': round(float(d.mean()), 3), 'de00_median': round(float(np.median(d)), 3),
            'de00_p95': round(float(np.percentile(d, 95)), 3), 'ncc_L': None if c is None else round(c, 4)}


def held_out(photo_path, tile_json):
    """Periodic prediction of the tile's source region; split inside-tile vs held-out pixels."""
    sc = json.load(open(tile_json))
    tile = cv2.imread(tile_json[:-5], cv2.IMREAD_COLOR)
    raw = T.load_bgr(photo_path)
    if raw is None or tile is None:
        return {'reason': 'unreadable'}
    img, _ = T.resize_max(raw, T.WORK_MAX_SIDE)
    x0, y0, rw, rh = sc['region_px']
    crop = img[y0:y0 + rh, x0:x0 + rw]
    if sc.get('H'):
        Hm = np.array(sc['H'], np.float64)
        crop = cv2.warpPerspective(crop, Hm, (crop.shape[1], crop.shape[0]), flags=cv2.INTER_LINEAR)
    o = np.array(sc['origin'], np.float64)
    u, w = np.array(sc['u'], np.float64), np.array(sc['w'], np.float64)
    nu, nw = sc['nu'], sc['nw']
    A = np.stack([u / nu, w / nw], 1)                 # tile px (i,j) -> crop px offset
    Ai = np.linalg.inv(A)
    yy, xx = np.mgrid[0:crop.shape[0], 0:crop.shape[1]].astype(np.float64)
    rel = np.stack([xx - o[0], yy - o[1]], -1) @ Ai.T
    i, j = rel[..., 0], rel[..., 1]
    inside_tile = (i >= 0) & (i < nu) & (j >= 0) & (j < nw)
    pad = np.pad(tile, ((2, 2), (2, 2), (0, 0)), mode='wrap')
    mx = (np.mod(i, nu) + 2).astype(np.float32)
    my = (np.mod(j, nw) + 2).astype(np.float32)
    pred = cv2.remap(pad, mx, my, interpolation=cv2.INTER_LINEAR, borderMode=cv2.BORDER_WRAP)
    d = T.de00(crop, pred)
    fab, _, _, _ = fabric_mask(T.lab32(crop), crop)
    valid = (fab > 0) & (crop.max(axis=2) > 0)
    res = {'note': 'photo region predicted from the tile by periodic extension (no new pixels)'}
    for name, m in (('inside_tile', inside_tile & valid), ('held_out', ~inside_tile & valid)):
        if m.sum() < 500:
            res[name] = {'px': int(m.sum())}
            continue
        dv = d[m]
        res[name] = {'px': int(m.sum()), 'de00_mean': round(float(dv.mean()), 3), 'de00_median': round(float(np.median(dv)), 3),
                     'de00_p95': round(float(np.percentile(dv, 95)), 3), 'share_de_gt_10': round(float((dv > 10).mean()), 4),
                     'ncc_L': (lambda c: None if c is None else round(c, 4))(T.ncc(T.lab32(crop)[..., 0], T.lab32(pred)[..., 0], m))}
    if sc.get('flatten'):
        res['caveat'] = 'tile was illumination-flattened; raw photo illumination differs from the flattened tile'
    return res


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--candidate', required=True)
    ap.add_argument('--exclude', action='append', default=[], help='x,y,w,h overlay rect in candidate px')
    ap.add_argument('--photo', action='append', required=True)
    ap.add_argument('--photo-kind', action='append', default=[])
    ap.add_argument('--tile-json')
    ap.add_argument('--out', required=True)
    args = ap.parse_args()
    T.guard('twin_check')
    cand_full = T.load_bgr(args.candidate)
    cand, s = T.resize_max(cand_full, 1400)
    valid = np.ones(cand.shape[:2], bool)
    for e in args.exclude:
        x, y, w, h = [int(round(float(v) * s)) for v in e.split(',')]
        valid[max(0, y):y + h, max(0, x):x + w] = False
    tmp = args.out + '.cand.png'
    c2 = cand.copy()
    c2[~valid] = 0
    cv2.imwrite(tmp, c2)
    out = {'candidate': args.candidate, 'checks': []}
    for k, p in enumerate(args.photo):
        photo_full = T.load_bgr(p)
        photo, _ = T.resize_max(photo_full, 1400)
        rec = {'photo_kind': args.photo_kind[k] if k < len(args.photo_kind) else None,
               'photo': os.path.basename(p) if 'supplier' not in (args.photo_kind[k] if k < len(args.photo_kind) else '') else 'supplier_ref (internal)'}
        rec['fabric_match'] = {kk: v for kk, v in fabric_match(p, tmp).items()
                               if kk in ('match_type', 'match_confidence', 'geometry_similarity', 'orb_inliers', 'orb_matches',
                                         'colour_similarity', 'texture_similarity', 'frequency_similarity', 'matched_region_percentage', 'error')}
        H, st = register(photo, cand, valid)
        rec['registration'] = st
        if H is not None:
            rec['matched_regions'] = de_matched(photo, cand, H, valid)
        out['checks'].append(rec)
    if args.tile_json:
        src = json.load(open(args.tile_json)).get('source_path_internal')
        if src and os.path.exists(src):
            out['held_out'] = held_out(src, args.tile_json)
    os.remove(tmp)
    with open(args.out, 'w') as f:
        json.dump(out, f, indent=1)
    print(json.dumps(out))
    return 0


if __name__ == '__main__':
    sys.exit(main())
