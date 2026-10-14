#!/usr/bin/env python3
"""Composite a real fabric crop into a green placeholder panel of a generated scene.

  scene_composite.py --scene raw.png --source src.jpg --crop x,y,w,h --outdir DIR --name NAME

Fabric pixels are only resampled (INTER_AREA downscale + Lanczos perspective warp) (no tint/shade/blur).
Writes NAME_composite.png, NAME_expected.png, NAME_quadmask.png, NAME_inv_*.png
and prints JSON (quad, checks). Exit 0 = composite produced, 3 = rejected.
"""
import argparse, json, os, sys
import cv2
import numpy as np

HSV_LO = np.array([40, 120, 80]); HSV_HI = np.array([90, 255, 255])


def green_mask(img):
    return cv2.inRange(cv2.cvtColor(img, cv2.COLOR_BGR2HSV), HSV_LO, HSV_HI)


def order_quad(p):
    p = p.reshape(4, 2).astype(np.float32)
    s = p.sum(1); d = np.diff(p, axis=1).ravel()
    return np.array([p[s.argmin()], p[d.argmin()], p[s.argmax()], p[d.argmax()]], np.float32)  # tl,tr,br,bl


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--scene', required=True); ap.add_argument('--source', required=True)
    ap.add_argument('--crop', required=True, help='max fabric-safe rect x,y,w,h in source px')
    ap.add_argument('--outdir', required=True); ap.add_argument('--name', required=True)
    a = ap.parse_args()
    scene = cv2.imread(a.scene); src = cv2.imread(a.source)
    H, W = scene.shape[:2]
    out = {'scene_size': [W, H]}

    def reject(why):
        out['rejected'] = why; print(json.dumps(out)); sys.exit(3)

    g = green_mask(scene)
    g = cv2.morphologyEx(g, cv2.MORPH_OPEN, np.ones((5, 5), np.uint8))
    cs, _ = cv2.findContours(g, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    if not cs:
        reject('no green panel found')
    c = max(cs, key=cv2.contourArea)
    area = cv2.contourArea(c) / (W * H); out['panel_area_frac'] = round(area, 3)
    if area < 0.15:
        reject(f'green panel too small ({area:.2f})')
    approx = cv2.approxPolyDP(c, 0.02 * cv2.arcLength(c, True), True)
    if len(approx) != 4 or not cv2.isContourConvex(approx):
        reject(f'panel not a clean convex quad ({len(approx)} vertices)')
    hull_fill = cv2.contourArea(c) / max(cv2.contourArea(approx), 1)
    out['quad_fill'] = round(hull_fill, 3)
    if hull_fill < 0.95:
        reject(f'green region not filling quad ({hull_fill:.2f}) - occluded/irregular')
    q = order_quad(approx); out['quad'] = q.round(1).tolist()
    pts = q.astype(np.int32)
    if (pts[:, 0] <= 1).any() or (pts[:, 1] <= 1).any() or (pts[:, 0] >= W - 2).any() or (pts[:, 1] >= H - 2).any():
        reject('panel touches image border (edges not fully visible)')

    qw = (np.linalg.norm(q[1] - q[0]) + np.linalg.norm(q[2] - q[3])) / 2
    qh = (np.linalg.norm(q[3] - q[0]) + np.linalg.norm(q[2] - q[1])) / 2
    asp = qw / qh
    cx, cy, cw, ch = map(int, a.crop.split(','))
    if cw / ch > asp:
        nw, nh = int(ch * asp), ch
    else:
        nw, nh = cw, int(cw / asp)
    x0, y0 = cx + (cw - nw) // 2, cy + (ch - nh) // 2
    crop = src[y0:y0 + nh, x0:x0 + nw]
    out['crop_rect'] = [x0, y0, nw, nh]
    # pre-downscale (INTER_AREA) to effective resolution so the warp does not alias
    ew, eh = max(int(round(qw)), 8), max(int(round(qh)), 8)
    out['effective_res'] = [ew, eh]
    small = cv2.resize(crop, (ew, eh), interpolation=cv2.INTER_AREA)
    rect = np.array([[0, 0], [ew - 1, 0], [ew - 1, eh - 1], [0, eh - 1]], np.float32)
    M = cv2.getPerspectiveTransform(rect, q)
    warped = cv2.warpPerspective(small, M, (W, H), flags=cv2.INTER_LANCZOS4, borderMode=cv2.BORDER_REPLICATE)
    qm = np.zeros((H, W), np.uint8); cv2.fillConvexPoly(qm, pts, 255)
    qm = cv2.dilate(qm, np.ones((5, 5), np.uint8))  # 2px each side over green fringe
    comp = scene.copy(); comp[qm > 0] = warped[qm > 0]
    spill = int(np.count_nonzero(green_mask(comp)[qm == 0]))
    out['green_spill_px_outside'] = spill
    out['green_px_inside_fabric'] = int(np.count_nonzero(green_mask(comp)[qm > 0]))
    p = lambda s: os.path.join(a.outdir, f'{a.name}_{s}.png')
    cv2.imwrite(p('composite'), comp); cv2.imwrite(p('expected'), warped); cv2.imwrite(p('quadmask'), qm)
    # independent inverse: composite quad -> rect at effective res, vs source crop downscaled
    Minv = cv2.getPerspectiveTransform(q, rect)
    inv = cv2.warpPerspective(comp, Minv, (ew, eh), flags=cv2.INTER_LANCZOS4)
    m = np.zeros((eh, ew), np.uint8); m[3:-3, 3:-3] = 255  # skip 3px warp-edge band
    cv2.imwrite(p('inv_candidate'), inv); cv2.imwrite(p('inv_source'), small); cv2.imwrite(p('inv_mask'), m)
    out['files'] = {k: p(k) for k in ('composite', 'expected', 'quadmask', 'inv_candidate', 'inv_source', 'inv_mask')}
    if spill > 0:
        reject(f'green spill outside fabric: {spill}px')
    print(json.dumps(out)); return 0


if __name__ == '__main__':
    sys.exit(main())
