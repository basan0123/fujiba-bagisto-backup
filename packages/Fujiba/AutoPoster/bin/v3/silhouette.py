#!/usr/bin/env python3
"""
Silhouette test — real fabric on an AI-generated plain-grey skirt.

  silhouette.py --model grey_skirt.jpg --source fabric.webp --outdir DIR --name NAME

The AI image (Nano Banana Pro) only supplies the model + a PLAIN mid-grey skirt.
No AI ever sees or touches the fabric. This script:
  1. segments the grey skirt (neutral-chroma + luminance threshold, largest
     region, morphology, hole fill, per-row extents median-smoothed so hands
     do not kink the outline);
  2. takes a flat rectangle crop of the real photo (watermark-free; checked
     with wm_detect beforehand) and maps it onto the skirt with a deterministic
     per-row mesh warp: rows map linearly top→bottom (straight vertical fall),
     columns follow a mild cylinder (arcsine, ±A°) between the left/right edge;
  3. multiplies by a DECLARED shading layer derived from the grey-cloth
     luminance (shade = 1 + K·(L/median − 1)); pixels where |shade−1| > TOL are
     written to a shadow mask for fabric_gate --shadow;
  4. writes: fabric layer (unshaded), composite (shaded, labelled copy too),
     expected-warp PNG, skirt mask, shadow mask, unwarped flat + its masks,
     source crop and a side-by-side.
Gating is done by the caller with intel/fabric_gate.py (unchanged).
"""
import argparse
import json
import os

import cv2
import numpy as np
from PIL import Image, ImageDraw, ImageFont

A_DEG = 55.0      # cylinder half-angle visible from the front
K_SHADE = 0.85    # shading strength
TOL = 0.06        # |shade-1| above this is declared shadow
FONT_MY = '/usr/share/fonts/truetype/padauk/Padauk-Bold.ttf'
FONT_EN = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'


def skirt_mask(img, chroma_max=14.0):
    """Neutral-grey threshold (wall/floor colours sampled at the corners are
    excluded), largest region, then GrabCut refinement of the edges."""
    h, w = img.shape[:2]
    lab = cv2.cvtColor(img, cv2.COLOR_BGR2LAB).astype(np.float32)
    L, A, B = lab[..., 0] * 100 / 255, lab[..., 1] - 128, lab[..., 2] - 128
    chroma = np.hypot(A, B)
    m = (chroma < chroma_max) & (L > 12) & (L < 82)
    # wall samples only (floor corners can match shadowed cloth; floor leaks
    # are removed later by the edge fit)
    for (cy, cx) in [(h // 10, w // 12), (h // 10, w - w // 12), (h // 2, w // 14), (h // 2, w - w // 14)]:
        ref = lab[cy, cx]
        d = lab - ref
        d[..., 0] *= 0.3  # lighting varies on cloth; tint separates it from the wall
        m &= np.linalg.norm(d, axis=2) > 8
    m = cv2.morphologyEx(m.astype(np.uint8), cv2.MORPH_OPEN, np.ones((5, 5), np.uint8))
    n, labl, st, _ = cv2.connectedComponentsWithStats(m, 8)
    if n < 2:
        raise SystemExit('no skirt region')
    k = 1 + int(np.argmax(st[1:, cv2.CC_STAT_AREA]))
    m = (labl == k).astype(np.uint8)
    m = cv2.morphologyEx(m, cv2.MORPH_CLOSE, np.ones((25, 25), np.uint8))
    gc = np.full((h, w), cv2.GC_BGD, np.uint8)
    dil = cv2.dilate(m, np.ones((31, 31), np.uint8))
    gc[dil > 0] = cv2.GC_PR_BGD
    gc[m > 0] = cv2.GC_PR_FGD
    gc[cv2.erode(m, np.ones((31, 31), np.uint8)) > 0] = cv2.GC_FGD
    bg, fg = np.zeros((1, 65), np.float64), np.zeros((1, 65), np.float64)
    cv2.grabCut(img, gc, None, bg, fg, 4, cv2.GC_INIT_WITH_MASK)
    m = ((gc == cv2.GC_FGD) | (gc == cv2.GC_PR_FGD)).astype(np.uint8)
    n, labl, st, _ = cv2.connectedComponentsWithStats(m, 8)
    k = 1 + int(np.argmax(st[1:, cv2.CC_STAT_AREA]))
    m = (labl == k).astype(np.uint8)
    ff = m.copy()
    cv2.floodFill(ff, np.zeros((h + 2, w + 2), np.uint8), (0, 0), 1)
    m = m | (1 - ff)
    return m.astype(bool)


def row_extents(mask):
    ys = np.where(mask.any(1))[0]
    top, bot = int(ys.min()), int(ys.max())
    L = np.full(mask.shape[0], np.nan)
    R = np.full(mask.shape[0], np.nan)
    for y in range(top, bot + 1):
        xs = np.where(mask[y])[0]
        if xs.size:
            L[y], R[y] = xs.min(), xs.max()
    seg = slice(top, bot + 1)
    ys_all = np.arange(top, bot + 1)
    for arr in (L, R):
        v = arr[seg]
        ok = ~np.isnan(v)
        # straight vertical fall ⇒ each edge is ~a line; robust fit rejects
        # hands, missed lit patches and floor-shadow leaks
        keep = ok.copy()
        for _ in range(4):
            c = np.polyfit(ys_all[keep], v[keep], 2)
            res = np.abs(np.polyval(c, ys_all) - np.nan_to_num(v))
            keep = ok & (res < max(6.0, np.percentile(res[ok], 60)))
        arr[seg] = np.polyval(c, ys_all)
    return top, bot, L, R


def t_to_s(t, a):   # screen fraction → fabric fraction (cylinder)
    return 0.5 + np.arcsin(np.clip(2 * t - 1, -1, 1) * np.sin(a)) / (2 * a)


def s_to_t(s, a):
    return 0.5 + np.sin((2 * s - 1) * a) / (2 * np.sin(a))


def label(bgr, text_en='Image for reference', text_my='ပုံစံနမူနာသာ'):
    im = Image.fromarray(cv2.cvtColor(bgr, cv2.COLOR_BGR2RGB))
    d = ImageDraw.Draw(im)
    w, h = im.size
    bar = max(44, h // 22)
    d.rectangle([0, h - bar, w, h], fill=(20, 20, 20))
    fe = ImageFont.truetype(FONT_EN, int(bar * 0.42))
    fm = ImageFont.truetype(FONT_MY, int(bar * 0.48))
    d.text((14, h - bar + bar * 0.22), text_en + ' / ', font=fe, fill=(255, 255, 255))
    x = 14 + d.textlength(text_en + ' / ', font=fe)
    d.text((x, h - bar + bar * 0.08), text_my, font=fm, fill=(255, 255, 255))
    return cv2.cvtColor(np.array(im), cv2.COLOR_RGB2BGR)


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--model', required=True)
    ap.add_argument('--source', required=True)
    ap.add_argument('--outdir', required=True)
    ap.add_argument('--name', required=True)
    ap.add_argument('--chroma-max', type=float, default=14.0,
                    help='Lab chroma ceiling for the grey cloth (cool-lit grey needs ~22)')
    a = ap.parse_args()
    o = lambda s: os.path.join(a.outdir, f'{a.name}_{s}')
    ang = np.radians(A_DEG)

    img = cv2.imread(a.model)
    src = np.array(Image.open(a.source).convert('RGB'))[:, :, ::-1].copy()
    mask = skirt_mask(img, a.chroma_max)
    top, bot, L, R = row_extents(mask)
    H = bot - top + 1
    Wf = int(np.nanmedian(R[top:bot + 1] - L[top:bot + 1])) + 1
    # cylinder: fabric width visible = arc length → wider than the screen chord
    Wf = int(round(Wf * ang / np.sin(ang)))
    # flat crop of the real photo with aspect Wf:H, full source height, centred
    sh, sw = src.shape[:2]
    cw = int(round(sh * Wf / H))
    if cw > sw:
        ch, cw = int(round(sw * H / Wf)), sw
    else:
        ch = sh
    x0, y0 = (sw - cw) // 2, (sh - ch) // 2
    flat = cv2.resize(src[y0:y0 + ch, x0:x0 + cw], (Wf, H), interpolation=cv2.INTER_AREA)
    cv2.imwrite(o('source_crop.png'), flat)

    # forward map (output pixel → flat coords)
    hh, ww = mask.shape
    yy, xx = np.mgrid[0:hh, 0:ww].astype(np.float32)
    Lr, Rr = np.nan_to_num(L).astype(np.float32), np.nan_to_num(R).astype(np.float32)
    t = (xx - Lr[:, None]) / np.maximum(Rr - Lr, 1)[:, None]
    # whole band between the fitted edges, minus non-grey occluders (hands)
    labf = cv2.cvtColor(img, cv2.COLOR_BGR2LAB).astype(np.float32)
    occl = np.hypot(labf[..., 1] - 128, labf[..., 2] - 128) > a.chroma_max + 4
    occl = cv2.morphologyEx(occl.astype(np.uint8), cv2.MORPH_OPEN, np.ones((5, 5), np.uint8)) > 0
    inside = (t >= 0) & (t <= 1) & (yy >= top) & (yy <= bot) & ~occl
    mapx = (t_to_s(t, ang) * (Wf - 1)).astype(np.float32)
    mapy = (yy - top).astype(np.float32)
    warped = cv2.remap(flat, mapx, mapy, cv2.INTER_LINEAR, borderMode=cv2.BORDER_REFLECT)

    # declared shading layer from grey-cloth luminance
    lum = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY).astype(np.float32)
    lum = cv2.GaussianBlur(lum, (0, 0), 1.5)
    med = float(np.median(lum[inside]))
    shade = np.clip(1 + K_SHADE * (lum / med - 1), 0.45, 1.35)
    shadow = inside & (np.abs(shade - 1) > TOL)

    m3 = inside[:, :, None]
    fabric_layer = np.where(m3, warped, img)
    comp = np.where(m3, np.clip(warped.astype(np.float32) * shade[:, :, None], 0, 255).astype(np.uint8), img)
    # soften the cut edge by 1px blend (outside gate mask: erode gate mask by 2)
    gate_mask = cv2.erode(inside.astype(np.uint8), np.ones((5, 5), np.uint8)).astype(bool)
    edge = inside & ~gate_mask
    blur = cv2.GaussianBlur(comp, (3, 3), 0)
    comp[edge] = blur[edge]

    expected = np.where(m3, warped, 0).astype(np.uint8)
    cv2.imwrite(o('expected_warp.png'), expected)
    cv2.imwrite(o('fabric_layer.png'), fabric_layer)
    cv2.imwrite(o('composite.png'), comp)
    cv2.imwrite(o('mask.png'), gate_mask.astype(np.uint8) * 255)
    cv2.imwrite(o('shadow.png'), (shadow & gate_mask).astype(np.uint8) * 255)
    lab = label(comp)
    cv2.imwrite(o('composite_labelled.jpg'), lab, [cv2.IMWRITE_JPEG_QUALITY, 92])

    # inverse: unwarp composite back to the flat rectangle
    fy, fx = np.mgrid[0:H, 0:Wf].astype(np.float32)
    s = fx / max(Wf - 1, 1)
    oy = fy + top
    Lrow, Rrow = Lr[top:bot + 1][:, None], Rr[top:bot + 1][:, None]
    ox = (Lrow + s_to_t(s, ang) * (Rrow - Lrow)).astype(np.float32)
    unw = cv2.remap(comp, ox, oy, cv2.INTER_LINEAR)
    um = cv2.remap(gate_mask.astype(np.uint8) * 255, ox, oy, cv2.INTER_NEAREST)
    us = cv2.remap((shadow & gate_mask).astype(np.uint8) * 255, ox, oy, cv2.INTER_NEAREST)
    cv2.imwrite(o('unwarped.png'), unw)
    cv2.imwrite(o('unwarped_layer.png'), cv2.remap(fabric_layer, ox, oy, cv2.INTER_LINEAR))
    cv2.imwrite(o('unwarp_mask.png'), um)
    cv2.imwrite(o('unwarp_shadow.png'), us)

    # side-by-side: source crop | composite skirt (crop), same height
    ys, xs = np.where(inside)
    pad = 30
    cx0, cx1 = max(xs.min() - pad, 0), min(xs.max() + pad, ww)
    cy0, cy1 = max(ys.min() - pad, 0), min(ys.max() + pad, hh)
    right = lab[cy0:cy1, cx0:cx1]
    left = cv2.resize(flat, (int(flat.shape[1] * right.shape[0] / flat.shape[0]), right.shape[0]))
    gap = np.full((right.shape[0], 16, 3), 255, np.uint8)
    sbs = np.hstack([left, gap, right])
    cv2.imwrite(o('side_by_side.jpg'), sbs, [cv2.IMWRITE_JPEG_QUALITY, 90])

    meta = {'model_image': a.model, 'source': a.source, 'crop_xywh': [x0, y0, cw, ch],
            'flat_size': [Wf, H], 'skirt_rows': [top, bot], 'cylinder_half_angle_deg': A_DEG,
            'shade_k': K_SHADE, 'chroma_max': a.chroma_max, 'shadow_tol': TOL, 'skirt_px': int(inside.sum()),
            'gate_px': int(gate_mask.sum()), 'shadow_px': int((shadow & gate_mask).sum()),
            'is_ai_concept': True,
            'label': 'Image for reference / ပုံစံနမူနာသာ — model+garment AI concept, fabric = real pixels (deterministic warp)'}
    with open(o('meta.json'), 'w') as f:
        json.dump(meta, f, indent=1, ensure_ascii=False)
    print(json.dumps(meta, ensure_ascii=False))


if __name__ == '__main__':
    main()
