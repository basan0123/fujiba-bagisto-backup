#!/usr/bin/env python3
"""
Content Engine v2 still images. IMAGE LOCK: fabric pixels are only cropped,
scaled (never upscaled > 1.25x), placed, perspective-warped or fold-shaded —
never redrawn. Every unaltered placement is listed in <out>.json
`fabric_regions` (+ the overlays drawn on top, `exclude`) so
bin/intel/fabric_gate.py --master can verify it against the source.

Output is a LOSSLESS PNG master; the JPEG is derived by deliver.py only after
the gate passes.

    compose.py <manifest.json> <template H|J|B|T> <master.png>

  Text on the image may only carry catalog data (product_flat name / price)
  or static CTA copy. Fabric drawn without a gate-registered region (J strips,
  perspective top face) is listed in `unchecked_regions`; any asset with
  unchecked fabric regions is test_eligible=false.
"""
import json
import math
import sys

import numpy as np
from PIL import Image, ImageDraw, ImageFilter

from common import (CREAM, GOLD, INK, crop_box, cover, cta_bar, darken, draw_text, fit, gradient, line_h, load,
                    save_master, save_sidecar, shadow, text_w)

W, H = 1080, 1350
regions = []
texts = []
unchecked = []      # fabric drawn but NOT verifiable by the gate (warped / shaded)


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


def catalog_text(d, tx, y, p, max_w):
    """Public product name + own price (both from product_flat). Returns next y."""
    size = fit(p['name'], max_w, 64)
    T(d, (tx, y), p['name'], size, CREAM, 'serif')
    y += line_h(p['name'], size, 'serif') + 14
    price = f"¥{int(p['price']):,}"
    T(d, (tx, y), price, 56, GOLD, 'serif')
    return y + line_h(price, 56, 'serif') + 8


MY_DIGITS = '\u1040\u1041\u1042\u1043\u1044\u1045\u1046\u1047\u1048\u1049'   # Burmese ၀-၉
NE_DIGITS = '\u0966\u0967\u0968\u0969\u096a\u096b\u096c\u096d\u096e\u096f'   # Devanagari ०-९


def local_digits(n, digits):
    return ''.join(digits[int(c)] for c in str(int(n)))


def lum(rgb):
    return 0.299 * rgb[0] + 0.587 * rgb[1] + 0.114 * rgb[2]


def cta_accent(g):
    """ORDER pill colour derived from the ground, kept legible (white text) on the white CTA bar."""
    lv = lum(g)
    return tuple(min(255, int(c * 1.8)) for c in g) if lv < 60 else darken(g, max(0.2, 70 / max(lv, 1)))


def measured_ground(path):
    """Median colour of a product photo — a measurement for the card background only (fabric untouched)."""
    a = np.asarray(load(path).reduce(8)).reshape(-1, 3)
    return tuple(int(v) for v in np.median(a, axis=0))


# ---------- H: colour set (3-6 colourways of one print) ----------
def colour_set(m, out):
    items = m['set']
    n = len(items)
    assert 3 <= n <= 6, f'H needs 3-6 colourways (got {n})'
    g0 = m['products'][0].get('ground')
    ground = hexrgb(g0) if g0 else measured_ground(items[0]['image'])
    top_rgb = darken(ground, min(0.9, 70 / max(lum(ground), 1)))     # dark enough for cream/gold text
    cv = gradient(W, H, top_rgb, (10, 10, 16))
    d = ImageDraw.Draw(cv)
    # title: series code + ACTUAL tile count (never a hardcoded number)
    title = f"V2 TEST · {m['series']}" if m.get('series') else m['set_title']
    title_ja = f'同じ柄・{n}色' if m.get('series') else m['set_title_ja']
    T(d, (W / 2, 40), 'F U J I B A   K I M O N O', 28, GOLD, 'serif', 'c')
    T(d, (W / 2, 82), title, fit(title, W - 120, 58, 'serif', 40), CREAM, 'serif', 'c')
    T(d, (W / 2, 160), title_ja, 34, (230, 220, 200), 'ja', 'c')

    cols = 2 if n == 4 else 3
    rows = -(-n // cols)
    portrait = rows == 1              # one row: show each WHOLE photo (its own aspect, no crop)
    big = 6 if portrait else 0        # a single row leaves room: larger, more legible text

    # bottom text block, measured first so the grid can be sized to leave room for it
    lines = []
    mat = m.get('material')            # only passed when EVERY tile's catalog description states it
    if mat and mat.get('en'):
        lines.append((mat['en'] + (f"  ·  {mat['ja']}" if mat.get('ja') else ''), 30 + big // 3, 'serif', (240, 226, 190)))
    lines += [(f'အရောင် {local_digits(n, MY_DIGITS)} မျိုး ၊ ကြိုက်တဲ့အရောင် ရွေးပါ', 34 + big // 2, 'my', CREAM),
              (f'{local_digits(n, NE_DIGITS)} रङ — आफ्नो मनपर्ने रङ छान्नुहोस्', 32 + big // 2, 'ne', (235, 225, 205))]
    lines = [(t, fit(t, W - 80, s, k, 28), k, c) for t, s, k, c in lines]
    block_h = sum(line_h(t, s, k) + 4 for t, s, k, _ in lines)
    label_size = 32 + big
    side, gap, row_gap, label_h, pad = (36, 28, 24, 66, 22) if portrait else (48, 32, 24, 58, 18)
    top, limit = 222, H - 124
    tw_max = (W - 2 * side - (cols - 1) * gap) // cols
    th_max = (limit - top - block_h - pad - rows * label_h - (rows - 1) * row_gap) // rows
    if portrait:
        bx, by, bw, bh = items[0]['box']
        ar = bw / bh
        tw = min(tw_max, int(th_max * ar))
        th = round(tw / ar)
    else:
        tw = th = min(tw_max, th_max)
    grid_h = rows * (th + label_h) + (rows - 1) * row_gap
    y0 = top + max(0, (limit - top - grid_h - pad - block_h) // 2)     # centre grid + text block
    pos = []
    for i in range(n):
        r, c = divmod(i, cols)
        in_row = min(cols, n - r * cols)
        pos.append(((W - in_row * tw - (in_row - 1) * gap) // 2 + c * (tw + gap), y0 + r * (th + label_h + row_gap)))
    # ALL shadows + frames first: a neighbour's blurred shadow must never land on placed fabric
    for x, y in pos:
        shadow(cv, (x, y, x + tw, y + th), 18, (0, 10), 140)
    d = ImageDraw.Draw(cv)
    for x, y in pos:
        d.rectangle((x - 7, y - 7, x + tw + 7, y + th + 7), fill=GOLD)
    for it, (x, y) in zip(items, pos):
        bx, by, bw, bh = it['box']
        if portrait:                  # centred crop to the tile aspect (whole photo when aspects match)
            cw, ch = (min(bw, round(bh * tw / th)), min(bh, round(bw * th / tw)))
        else:                         # square centre crop of the photo: no stretching
            cw = ch = min(bw, bh)
        place(cv, it['image'], [bx + (bw - cw) // 2, by + (bh - ch) // 2, cw, ch], [x, y, tw, th])
        d = ImageDraw.Draw(cv)
        # tile label = OUR public code + that product's own price (product_flat); legacy: letter · colour
        label = f"{it['name']} · ¥{int(it['price']):,}" if 'price' in it else f"{it['letter']} · {it['colour']}"
        size = fit(label, tw + gap - 10, label_size, 'serif', 28)
        assert text_w(label, size, 'serif') <= tw + gap - 10, f'H tile label too wide: {label}'
        T(d, (x + tw / 2, y + th + 14), label, size, CREAM, 'serif', 'c')
    d = ImageDraw.Draw(cv)
    y = y0 + grid_h + pad
    for txt, size, kind, col in lines:
        T(d, (W / 2, y), txt, size, col, kind, 'c')
        y += line_h(txt, size, kind) + 4
    assert y <= H - 124, f'H text overflows into CTA bar (y={y})'
    cta_bar(cv, H - 120, 120, cta_accent(ground))
    save_master(cv, out)


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
        unchecked.append({'src': p['image'], 'kind': 'fold_band_strip', 'out_box': [x, y, sw, band]})
    # top face of the top piece, in perspective
    top = ps[-1]
    face = cover(crop_box(imgs[-1], top['box']), 900, 600)
    ytop = ybase - len(ps) * band
    quad = [(x0 + 40, ytop - 250), (x0 + sw - 40, ytop - 250), (x0 + sw + 8, ytop + 4), (x0 - 6, ytop + 4)]
    warped, mask = perspective(face, quad, cv.size)
    cv.paste(warped, (0, 0), mask)
    unchecked.append({'src': top['image'], 'kind': 'perspective_top_face', 'quad': quad})
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
        T(d, (sx + s + 14, y + s / 2 - 18), f"{p['name']} · ¥{int(p['price']):,}", 28, INK, 'serif')
    d = ImageDraw.Draw(cv)
    y = 1060
    # no material/size claims: they are not catalog attributes. Price is per swatch above.
    for txt, size, kind, col in [('အမေ့အတွက် ဂျပန်လက်ဆောင် ၊ ခေါက်ပြီးသား အဆင်သင့်', 28, 'my', (60, 50, 50)),
                                 ('आमाको लागि जापानबाट उपहार', 28, 'ne', (60, 50, 50))]:
        T(d, (60, y), txt, size, col, kind)
        y += line_h(txt, size, kind) + 6
    assert y <= H - 124, f'J text overflows into CTA bar (y={y})'
    cta_bar(cv, H - 120, 120, (150, 30, 40))
    save_master(cv, out)


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
                            [zx - R - 64, zy - R - 64, 2 * R + 128, 2 * R + 128]] + [   # disc + gold rim + drop shadow; leader line
        [int(cx_bg + (zx - cx_bg) * u) - 8, int(cy_bg + (zy - cy_bg) * u) - 8, 16, 16] for u in [i / 60 for i in range(61)]]
    # register the whole disc exactly as rendered; gate masks the corners outside the circle
    regions.append({'src': p['image'], 'src_box': p['focus'], 'out_box': [zx - R, zy - R, 2 * R, 2 * R], 'mode': 'cover', 'mask': 'circle'})
    d = ImageDraw.Draw(cv)
    tx = 56
    # AI-written knowledge (m['story']) is unverified and is never printed — catalog name/price only
    y = catalog_text(d, tx, bgh + 30, p, zx - R - tx - 20)
    assert y <= H - 124, f'B text overflows into CTA bar (y={y})'
    cta_bar(cv, H - 120, 120, darken(hexrgb(p['ground']), 1.8))
    save_master(cv, out)


# ---------- T: V2 test card (B layout, catalog data only) ----------
def test_card(m, out):
    p = m['products'][0]
    img = load(p['image'])
    bx, by, bw, bh = p['box']
    cv = Image.new('RGB', (W, H))
    bgh = 790
    place(cv, p['image'], p['box'], [0, 0, W, bgh], img)
    bg_region = regions[-1]
    s = max(W / bw, bgh / bh)
    ox, oy = (bw * s - W) / 2, (bh * s - bgh) / 2
    fx, fy, fw, fh = p['focus']
    cx_bg, cy_bg = (fx + fw / 2 - bx) * s - ox, (fy + fh / 2 - by) * s - oy
    panel = gradient(W, H - bgh, darken(hexrgb(p['ground']), 0.55), (12, 10, 14))
    cv.paste(panel, (0, bgh))
    R = 230
    zx, zy = W - R - 50, bgh - 120
    zoom = cover(crop_box(img, p['focus']), 2 * R, 2 * R)
    mask = Image.new('L', (2 * R, 2 * R), 0)
    ImageDraw.Draw(mask).ellipse((0, 0, 2 * R, 2 * R), fill=255)
    shadow(cv, (zx - R, zy - R, zx + R, zy + R), 24, (0, 12), 160)
    d = ImageDraw.Draw(cv)
    # focus ring only when the focus is a real sub-area of the background (not the whole photo)
    pc = m.get('style') == 'price_code'       # optional on-image code badge + big price
    ring = fw * s < W * 0.8 and fh * s < bgh * 0.8 and not pc   # price_code drops the ring to keep overlay share low
    excl = [[zx - R - 64, zy - R - 64, 2 * R + 128, 2 * R + 128]]          # disc + gold rim + drop shadow
    if ring:
        rx, ry = fw * s / 2 + 6, fh * s / 2 + 6
        d.ellipse((cx_bg - fw * s / 2, cy_bg - fh * s / 2, cx_bg + fw * s / 2, cy_bg + fh * s / 2), outline=GOLD, width=5)
        ang = math.atan2(zy - cy_bg, zx - cx_bg)
        rr = max(fw, fh) * s / 2
        d.line([(cx_bg + rr * math.cos(ang), cy_bg + rr * math.sin(ang)), (zx - (R + 10) * math.cos(ang), zy - (R + 10) * math.sin(ang))], fill=GOLD, width=4)
        excl += [[int(cx_bg - rx), int(cy_bg - ry), int(2 * rx), int(2 * ry)]] + [
            [int(cx_bg + (zx - cx_bg) * u) - 8, int(cy_bg + (zy - cy_bg) * u) - 8, 16, 16] for u in [i / 60 for i in range(61)]]
    d.ellipse((zx - R - 10, zy - R - 10, zx + R + 10, zy + R + 10), fill=GOLD)
    cv.paste(zoom, (zx - R, zy - R), mask)
    # "V2 TEST" label, top-left on the background — declared overlay
    lab = 'V2 TEST'
    lw = int(text_w(lab, 34, 'sans')) + 40
    d.rounded_rectangle((32, 32, 32 + lw, 32 + 62), 12, fill=(200, 20, 30))
    T(d, (32 + lw / 2, 44), lab, 34, (255, 255, 255), 'sans', 'c')
    excl.append([28, 28, lw + 8, 70])
    if pc:
        # code badge: OUR public product_flat name only (never supplier codes)
        code = p['name']
        cw = int(text_w(code, 52, 'sans')) + 48
        bx0, by0 = 32, 110
        d.rounded_rectangle((bx0, by0, bx0 + cw, by0 + 84), 14, fill=(255, 255, 255))
        T(d, (bx0 + cw / 2, by0 + 14), code, 52, INK, 'sans', 'c')
        excl.append([bx0 - 4, by0 - 4, cw + 8, 92])
        # big price, bottom-left of the photo, on a dark pill for legibility
        price = f"¥{int(p['price']):,}"
        pw = int(text_w(price, 96, 'serif')) + 56
        px0, py0 = 32, bgh - 170
        d.rounded_rectangle((px0, py0, px0 + pw, py0 + 136), 20, fill=(14, 12, 18))
        T(d, (px0 + pw / 2, py0 + 10), price, 96, GOLD, 'serif', 'c')
        excl.append([px0 - 4, py0 - 4, pw + 8, 144])
    # overlay share self-check (gate rejects > max_excluded_share 0.5); keep margin
    em = np.zeros((bgh, W), bool)
    for ex, ey, ew, eh in excl:
        em[max(0, ey):max(0, ey + eh), max(0, ex):max(0, ex + ew)] = True
    share = float(em.mean())
    assert share <= 0.45, f'T overlays cover {share:.2f} of the photo (> 0.45)'
    bg_region['exclude'] = excl
    regions.append({'src': p['image'], 'src_box': p['focus'], 'out_box': [zx - R, zy - R, 2 * R, 2 * R], 'mode': 'cover', 'mask': 'circle'})
    d = ImageDraw.Draw(cv)
    y = catalog_text(d, 56, bgh + 140, p, zx - R - 56 - 20)
    assert y <= H - 124, f'T text overflows into CTA bar (y={y})'
    cta_bar(cv, H - 120, 120, cta_accent(hexrgb(p['ground'])))   # ORDER pill must stay legible on the white bar
    save_master(cv, out)


def main():
    m = json.load(open(sys.argv[1]))
    tpl, out = sys.argv[2], sys.argv[3]
    {'H': colour_set, 'J': stack, 'B': motif, 'T': test_card}[tpl](m, out)
    save_sidecar(out, {'template': tpl, 'size': [W, H], 'fabric_regions': regions,
                       'min_exact_share': {'J': 0.04}.get(tpl, 0.15),
                       'min_text_px': min((t['size'] for t in texts), default=None), 'texts': texts,
                       'unchecked_regions': unchecked, 'test_eligible': not unchecked})
    print(out)


if __name__ == '__main__':
    import os as _o, sys as _s; _s.path.insert(0, _o.path.join(_o.path.dirname(_o.path.abspath(__file__)), '..', 'intel')); __import__('heavy_guard').acquire('compose')  # one heavy job at a time + memory cap
    main()
