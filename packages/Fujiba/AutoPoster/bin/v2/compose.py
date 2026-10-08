#!/usr/bin/env python3
"""
Content Engine v2 still images. IMAGE LOCK: fabric pixels are only cropped,
scaled (never upscaled > 1.25x), placed, perspective-warped or fold-shaded —
never redrawn. Every unaltered placement is listed in <out>.json
`fabric_regions` so gate.py can verify it against the source.

    compose.py <manifest.json> <template H|J|B> <out.jpg>
"""
import json
import math
import sys

import numpy as np
from PIL import Image, ImageDraw, ImageFilter

from common import (CREAM, GOLD, INK, crop_box, cover, cta_bar, darken, draw_text, fit, gradient, line_h, load,
                    save_sidecar, shadow)

W, H = 1080, 1350
regions = []
texts = []


def hexrgb(h):
    h = h.lstrip('#')
    return tuple(int(h[i:i + 2], 16) for i in (0, 2, 4))


def place(canvas, src_path, src_box, out_box, img=None):
    """Exact placement (scale only) — recorded for the gate."""
    img = img or load(src_path)
    x, y, w, h = out_box
    piece = cover(crop_box(img, src_box), w, h)
    canvas.paste(piece, (x, y))
    regions.append({'src': src_path, 'src_box': src_box, 'out_box': out_box, 'mode': 'cover'})
    return piece


def T(d, xy, text, size, fill, kind=None, anchor='l'):
    texts.append({'text': text, 'size': size})
    return draw_text(d, xy, text, size, fill, kind, anchor)


# ---------- H: colour set ----------
def colour_set(m, out):
    items = m['set']
    ground = hexrgb(m['products'][0]['ground'])
    cv = gradient(W, H, darken(ground, 0.9), (10, 10, 16))
    d = ImageDraw.Draw(cv)
    T(d, (W / 2, 40), 'F U J I B A   K I M O N O', 28, GOLD, 'serif', 'c')
    T(d, (W / 2, 82), m['set_title'], 58, CREAM, 'serif', 'c')
    T(d, (W / 2, 160), m['set_title_ja'], 34, (230, 220, 200), 'ja', 'c')
    tile, gap = 430, 40
    x0 = (W - 2 * tile - gap) // 2
    for i, it in enumerate(items[:4]):
        x, y = x0 + (i % 2) * (tile + gap), 230 + (i // 2) * (tile + 74)
        shadow(cv, (x, y, x + tile, y + tile), 18, (0, 10), 140)
        d = ImageDraw.Draw(cv)
        d.rectangle((x - 8, y - 8, x + tile + 8, y + tile + 8), fill=GOLD)
        # square centre crop of the close-up: no stretching
        bx, by, bw, bh = it['box']
        s = min(bw, bh)
        place(cv, it['image'], [bx + (bw - s) // 2, by + (bh - s) // 2, s, s], [x, y, tile, tile])
        d = ImageDraw.Draw(cv)
        T(d, (x + tile / 2, y + tile + 16), f"{it['letter']} · {it['colour']}", 32, CREAM, 'serif', 'c')
    d = ImageDraw.Draw(cv)
    y = 230 + 2 * (tile + 74) + 6
    T(d, (W / 2, y), 'အရောင် ၄ မျိုး ၊ ကြိုက်တဲ့အရောင် ရွေးပါ', 34, CREAM, 'my', 'c')
    T(d, (W / 2, y + 56), '४ रङ — आफ्नो मनपर्ने रङ छान्नुहोस्', 32, (235, 225, 205), 'ne', 'c')
    cta_bar(cv, H - 120, 120, darken(ground, 1.6) if sum(ground) < 200 else ground)
    cv.save(out, quality=95, subsampling=0)   # 4:4:4 — 4:2:0 smears fine gold lines (ΔE≈5)


# ---------- J: folded stack ----------
def perspective(img, quad, size):
    """Warp img onto quad [(x,y)*4 TL,TR,BR,BL] inside an RGBA canvas of `size`."""
    w, h = img.size
    src = [(0, 0), (w, 0), (w, h), (0, h)]
    A, b = [], []
    for (X, Y), (x, y) in zip(quad, src):
        A += [[X, Y, 1, 0, 0, 0, -x * X, -x * Y], [0, 0, 0, X, Y, 1, -y * X, -y * Y]]
        b += [x, y]
    coef = np.linalg.solve(np.array(A, float), np.array(b, float))
    mask = Image.new('L', size, 0)
    ImageDraw.Draw(mask).polygon(quad, fill=255)
    warped = img.transform(size, Image.PERSPECTIVE, tuple(coef), Image.BICUBIC)
    return warped, mask


def fold_band(piece):
    """Shade a strip like the rounded front edge of folded cloth (multiplicative, hue preserved)."""
    a = np.asarray(piece).astype(np.float32)
    h = a.shape[0]
    t = np.linspace(0, 1, h)
    prof = 0.62 + 0.38 * np.sin(np.clip(t * 1.15, 0, 1) * math.pi) ** 0.6   # dark crease top/bottom, lit middle
    prof[-int(h * 0.12):] *= np.linspace(1, 0.72, int(h * 0.12))
    return Image.fromarray(np.clip(a * prof[:, None, None], 0, 255).astype(np.uint8))


def stack(m, out):
    ps = m['products'][:5]
    cv = gradient(W, H, CREAM, (232, 222, 202))
    d = ImageDraw.Draw(cv)
    T(d, (60, 50), 'F U J I B A   K I M O N O', 26, darken(GOLD, 0.8), 'serif')
    T(d, (60, 92), 'Folded & ready to gift', 56, INK, 'serif')
    T(d, (60, 166), '贈り物にそのまま。日本の着物生地', 32, (90, 80, 70), 'ja')
    sw, band = 640, 96
    x0, ybase = 70, 1040
    imgs = [load(p['image']) for p in ps]
    for i, (p, img) in enumerate(zip(ps, imgs)):          # bottom layer first
        y = ybase - (i + 1) * band
        x = x0 + (8 if i % 2 else -6)
        bx, by, bw, bh = p['box']
        strip = cover(crop_box(img, [bx, by + bh // 3, bw, bh // 3]), sw, band)
        shadow(cv, (x, y + 6, x + sw, y + band + 4), 10, (0, 6), 120)
        cv.paste(fold_band(strip), (x, y))
    # top face of the top piece, in perspective
    top = ps[-1]
    face = cover(crop_box(imgs[-1], top['box']), 900, 600)
    ytop = ybase - len(ps) * band
    quad = [(x0 + 40, ytop - 250), (x0 + sw - 40, ytop - 250), (x0 + sw + 8, ytop + 4), (x0 - 6, ytop + 4)]
    warped, mask = perspective(face, quad, cv.size)
    cv.paste(warped, (0, 0), mask)
    d = ImageDraw.Draw(cv)
    # exact swatches = what the customer actually gets, and what the gate checks
    sx, s = 760, 130
    for i, (p, img) in enumerate(zip(reversed(ps), reversed(imgs))):
        y = 250 + i * (s + 30)
        shadow(cv, (sx, y, sx + s, y + s), 10, (0, 6), 90)
        d = ImageDraw.Draw(cv)
        d.rectangle((sx - 5, y - 5, sx + s + 5, y + s + 5), fill=GOLD)
        bx, by, bw, bh = p['box']
        q = min(bw, bh) // 2
        place(cv, p['image'], [bx + (bw - q) // 2, by + (bh - q) // 2, q, q], [sx, y, s, s], img)
        d = ImageDraw.Draw(cv)
        T(d, (sx + s + 14, y + s / 2 - 18), p['name'], 28, INK, 'serif')
    d = ImageDraw.Draw(cv)
    y = 1060
    for txt, size, kind, col in [(f"Cotton 100% · 110 cm × 2 m · ¥{m['price']:,} each", 32, 'serif', INK),
                                 ('အမေ့အတွက် ဂျပန်လက်ဆောင် ၊ ခေါက်ပြီးသား အဆင်သင့်', 28, 'my', (60, 50, 50)),
                                 ('आमाको लागि जापानबाट उपहार', 28, 'ne', (60, 50, 50))]:
        T(d, (60, y), txt, size, col, kind)
        y += line_h(txt, size, kind) + 6
    assert y <= H - 124, f'J text overflows into CTA bar (y={y})'
    cta_bar(cv, H - 120, 120, (150, 30, 40))
    cv.save(out, quality=95, subsampling=0)   # 4:4:4 — 4:2:0 smears fine gold lines (ΔE≈5)


# ---------- B: pattern story / motif zoom ----------
def motif(m, out):
    p = m['products'][0]
    img = load(p['image'])
    bx, by, bw, bh = p['box']
    cv = Image.new('RGB', (W, H))
    bgh = 790
    place(cv, p['image'], p['box'], [0, 0, W, bgh], img)
    bg_region = regions[-1]
    # where the focus sits in the placed background (cover maths)
    s = max(W / bw, bgh / bh)
    ox, oy = (bw * s - W) / 2, (bh * s - bgh) / 2
    fx, fy, fw, fh = p['focus']
    cx_bg, cy_bg = (fx + fw / 2 - bx) * s - ox, (fy + fh / 2 - by) * s - oy
    # lower panel
    panel = gradient(W, H - bgh, darken(hexrgb(p['ground']), 0.55), (12, 10, 14))
    cv.paste(panel, (0, bgh))
    d = ImageDraw.Draw(cv)
    d.ellipse((cx_bg - fw * s / 2, cy_bg - fh * s / 2, cx_bg + fw * s / 2, cy_bg + fh * s / 2), outline=GOLD, width=5)
    # zoom circle (exact pixels of the focus box)
    R = 230
    zx, zy = W - R - 50, bgh - 120
    zoom = cover(crop_box(img, p['focus']), 2 * R, 2 * R)
    mask = Image.new('L', (2 * R, 2 * R), 0)
    ImageDraw.Draw(mask).ellipse((0, 0, 2 * R, 2 * R), fill=255)
    shadow(cv, (zx - R, zy - R, zx + R, zy + R), 24, (0, 12), 160)
    d = ImageDraw.Draw(cv)
    ang = math.atan2(zy - cy_bg, zx - cx_bg)
    rr = max(fw, fh) * s / 2
    d.line([(cx_bg + rr * math.cos(ang), cy_bg + rr * math.sin(ang)), (zx - (R + 10) * math.cos(ang), zy - (R + 10) * math.sin(ang))], fill=GOLD, width=4)
    d.ellipse((zx - R - 10, zy - R - 10, zx + R + 10, zy + R + 10), fill=GOLD)
    cv.paste(zoom, (zx - R, zy - R), mask)
    # declared overlays on the background (ring, leader line, zoom disc) — the gate skips these pixels
    rx, ry = fw * s / 2 + 6, fh * s / 2 + 6
    bg_region['exclude'] = [[int(cx_bg - rx), int(cy_bg - ry), int(2 * rx), int(2 * ry)],
                            [zx - R - 40, zy - R - 40, 2 * R + 80, 2 * R + 80]] + [
        [int(cx_bg + (zx - cx_bg) * u) - 8, int(cy_bg + (zy - cy_bg) * u) - 8, 16, 16] for u in [i / 60 for i in range(61)]]
    # register the whole disc exactly as rendered; gate masks the corners outside the circle
    regions.append({'src': p['image'], 'src_box': p['focus'], 'out_box': [zx - R, zy - R, 2 * R, 2 * R], 'mode': 'cover', 'mask': 'circle'})
    d = ImageDraw.Draw(cv)
    tx = 56
    T(d, (tx, bgh + 20), 'DID YOU KNOW?', 30, GOLD, 'serif')
    T(d, (tx, bgh + 62), m['story']['title'], 64, CREAM, 'serif')
    y = bgh + 150
    for line in m['story']['lines']:
        size = fit(line, zx - R - tx - 20, 30)
        T(d, (tx, y), line, size, (240, 232, 215))
        y += line_h(line, size) + 4
    assert y <= H - 124, f'B story text overflows into CTA bar (y={y})'
    cta_bar(cv, H - 120, 120, darken(hexrgb(p['ground']), 1.8))
    cv.save(out, quality=95, subsampling=0)   # 4:4:4 — 4:2:0 smears fine gold lines (ΔE≈5)


def main():
    m = json.load(open(sys.argv[1]))
    tpl, out = sys.argv[2], sys.argv[3]
    {'H': colour_set, 'J': stack, 'B': motif}[tpl](m, out)
    save_sidecar(out, {'template': tpl, 'size': [W, H], 'fabric_regions': regions,
                       'min_exact_share': {'J': 0.04}.get(tpl, 0.15),
                       'min_text_px': min((t['size'] for t in texts), default=None), 'texts': texts})
    print(out)


if __name__ == '__main__':
    main()
