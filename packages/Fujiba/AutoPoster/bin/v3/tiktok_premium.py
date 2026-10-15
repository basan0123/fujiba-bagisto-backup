#!/usr/bin/env python3
"""Premium 9:16 TikTok clip for ONE product (PREVIEW ONLY: no API, no posting, no queue rows,
no DB writes, nothing copied to public/).

    nice -n 10 python3 tiktok_premium.py --src <photo> --sha256 <hex> --out <dir> \
        --code FV-73-B --price 1595 [--material 'Hosonuno (100% cotton) · 綿100%'] [--stem name]

1080x1920 30 fps 9 s, no audio. Fabric = real photo pixels only: every frame is ONE Lanczos
resample (PIL resize(box=...)) of the original photo, crop/scale/translate only, no grading,
no filters, no interpolation between frames. Motion:
  0.0-0.8 s hook  : tight on the most salient crane region (red crown/beak x edge density),
                    cubic ease-out pull-back to native 1.0x
  0.8-9.0 s drift : one smootherstep pan across cranes + gold waves, ending slightly
                    below 1.0x (downscale only)
  6.0-6.5 s       : navy end-card band rises (declared overlay), text fades in (text layer only)
Sub-pixel motion with an exact gate: at every gated frame (every 0.5 s + first/last) the
sampled region is snapped to the region that common.cover(common.crop_box(photo, int_box))
samples, so fabric_gate --master (unchanged) re-derives those frames from the photo with the
renderers' own code; the snapping residual (<0.5 src px) is spread smoothly between them.
"""
import argparse
import hashlib
import json
import math
import os
import subprocess
import sys
import time

HERE = os.path.dirname(os.path.abspath(__file__))
BIN = os.path.dirname(HERE)
sys.path.insert(0, os.path.join(BIN, 'intel'))
sys.path.insert(0, os.path.join(BIN, 'v2'))
for _k in ('OMP_NUM_THREADS', 'OPENBLAS_NUM_THREADS', 'MKL_NUM_THREADS'):
    os.environ.setdefault(_k, '2')
__import__('heavy_guard').acquire('v3-tiktok-premium')

import cv2  # noqa: E402
import numpy as np  # noqa: E402
from PIL import Image, ImageDraw, ImageFont  # noqa: E402

from common import cover, crop_box, load, save_sidecar  # noqa: E402

W, H, FPS, SEC = 1080, 1920, 30, 9
N = FPS * SEC
TOP = 190                       # brand band height (outside fabric)
FAB = [0, TOP, W, H - TOP]      # fabric out_box
FW, FH = FAB[2], FAB[3]
BAND_Y = 1180                   # end-card band top when fully up
HOOK_N = int(0.8 * FPS)
CARD_T0, CARD_T1 = 6.0, 6.5
HOOK_W = 760                    # source px width of the first frame (~1.42x)
END_W = 1180                    # source px width of the last frame (~0.92x, downscale)
NAVY = (18, 22, 52)
GOLD = (222, 186, 108)
CREAM = (250, 243, 228)
MUTED = (196, 190, 176)
FD = os.path.join(BIN, 'v2', 'fonts')
FONTS = {
    'brand': os.path.join(FD, 'mali-latin-600-normal.woff'),
    'sans': os.path.join(FD, 'quicksand-latin-700-normal.woff'),
    'ja': '/usr/share/fonts/opentype/ipafont-gothic/ipag.ttf',
}
SAFE = {'right_px': 120, 'bottom_px': 280}


def sha256(p):
    h = hashlib.sha256()
    with open(p, 'rb') as f:
        for b in iter(lambda: f.read(1 << 20), b''):
            h.update(b)
    return h.hexdigest()


def F(kind, size):
    return ImageFont.truetype(FONTS[kind], size, layout_engine=ImageFont.Layout.RAQM)


# ---- motion --------------------------------------------------------------
def salient_center(src):
    """Crane head/wing: red crown/beak pixels weighted by local edge density."""
    k = 4
    sm = np.asarray(src.resize((src.width // k, src.height // k), Image.LANCZOS))
    hsv = cv2.cvtColor(sm, cv2.COLOR_RGB2HSV)
    red = (((hsv[..., 0] < 10) | (hsv[..., 0] > 170)) & (hsv[..., 1] > 110) & (hsv[..., 2] > 90)).astype(np.float32)
    edges = cv2.Canny(cv2.cvtColor(sm, cv2.COLOR_RGB2GRAY), 60, 160).astype(np.float32) / 255
    white = ((hsv[..., 1] < 40) & (hsv[..., 2] > 200)).astype(np.float32)
    r = 160 // k
    score = cv2.blur(edges, (2 * r + 1, 2 * r + 1)) * (0.3 + cv2.blur(white, (2 * r + 1, 2 * r + 1))) \
        * (cv2.GaussianBlur(red, (0, 0), 6) > 0.02)
    # keep the hook window inside the photo
    hw, hh = HOOK_W / k / 2, HOOK_W * FH / FW / k / 2
    m = np.zeros_like(score)
    m[int(hh):int(sm.shape[0] - hh), int(hw):int(sm.shape[1] - hw)] = 1
    y, x = np.unravel_index(np.argmax(score * m), score.shape)
    return float(x * k + k / 2), float(y * k + k / 2), float(score[y, x])


def clamp_region(cx, cy, w, iw, ih):
    h = w * FH / FW
    return [min(max(cx - w / 2, 0), iw - w), min(max(cy - h / 2, 0), ih - h), w]


def ease_out_cubic(t):
    return 1 - (1 - t) ** 3


def smootherstep(t):
    return t * t * t * (t * (6 * t - 15) + 10)


def path(iw, ih, hook_c):
    """Smooth sampled region (x, y, w) in source px for every frame; aspect fixed FW:FH."""
    A = clamp_region(hook_c[0], hook_c[1], HOOK_W, iw, ih)
    B = clamp_region(hook_c[0], hook_c[1], FW, iw, ih)            # native 1.0x
    # drift towards the far side of the photo (cranes + gold waves everywhere)
    tx = 0 if B[0] + FW / 2 > iw / 2 else iw
    ty = 0 if B[1] + FH / 2 > ih / 2 else ih
    D = clamp_region(tx, ty, END_W, iw, ih)
    out = []
    for i in range(N):
        t = i / FPS
        if i <= HOOK_N:
            e = ease_out_cubic(i / HOOK_N)
            out.append([a + (b - a) * e for a, b in zip(A, B)])
        else:
            e = smootherstep((i - HOOK_N) / (N - 1 - HOOK_N))
            out.append([a + (b - a) * e for a, b in zip(B, D)])
    return out, {'hook': A, 'native': B, 'end': D}


def cover_region(box):
    """Source region (x, y, w, h) that common.cover(common.crop_box(img, box), FW, FH) samples."""
    x, y, w, h = box
    s = max(FW / w, FH / h)
    rw, rh = max(FW, round(w * s)), max(FH, round(h * s))
    ox, oy = (rw - FW) // 2, (rh - FH) // 2
    kx, ky = w / rw, h / rh
    return [x + ox * kx, y + oy * ky, FW * kx, FH * ky]


def snap(reg, iw, ih):
    x, y, w = reg
    h = w * FH / FW
    box = [int(round(x)), int(round(y)), int(round(w)), int(round(h))]
    box[0] = min(max(box[0], 0), iw - box[2])
    box[1] = min(max(box[1], 0), ih - box[3])
    return box, cover_region(box)


def gated_frames():
    return sorted(set(list(range(0, N, FPS // 2)) + [N - 1]))


def regions_for_frames(smooth, iw, ih):
    """Exact 4-vector region per frame: smooth path + residual that is exact at gated frames."""
    g = gated_frames()
    boxes, res = {}, {}
    for n in g:
        x, y, w = smooth[n]
        box, ex = snap(smooth[n], iw, ih)
        boxes[n] = box
        res[n] = [ex[0] - x, ex[1] - y, ex[2] - w, ex[3] - w * FH / FW]
    out = []
    for i in range(N):
        a = max(n for n in g if n <= i)
        b = min(n for n in g if n >= i)
        u = 0.0 if a == b else (i - a) / (b - a)
        u = 0.5 - 0.5 * math.cos(math.pi * u)
        r = [ra + (rb - ra) * u for ra, rb in zip(res[a], res[b])]
        x, y, w = smooth[i]
        rw, rh = w + r[2], w * FH / FW + r[3]
        rx = min(max(x + r[0], 0.0), iw - rw)
        ry = min(max(y + r[1], 0.0), ih - rh)
        out.append([rx, ry, rw, rh])
    return out, boxes


# ---- text ----------------------------------------------------------------
def text_layer(code, price, material, page):
    """Return list of (draw_fn, t0, t1) text items; positions in canvas px."""
    items = []
    # top band (constant): brand + small code/price strip
    items.append(dict(kind='brand', size=46, text='Fujiba Elegance Emporium', xy=(W // 2 - 60, 30), anchor='mt', fill=GOLD, t0=0.0, dur=0.0))
    items.append(dict(kind='sans', size=40, text=f'{code}  ·  ¥{price:,}', xy=(W // 2 - 60, 108), anchor='mt', fill=CREAM, t0=0.8, dur=0.4))
    # end card band
    x = 72
    items += [
        dict(kind='sans', size=64, text=code, xy=(x, BAND_Y + 44), anchor='lt', fill=CREAM, t0=6.3, dur=0.5),
        dict(kind='sans', size=112, text=f'¥{price:,}', xy=(x, BAND_Y + 130), anchor='lt', fill=GOLD, t0=6.45, dur=0.5),
    ]
    if material:
        items.append(dict(kind='ja', size=36, text=material, xy=(x, BAND_Y + 272), anchor='lt', fill=MUTED, t0=6.6, dur=0.5))
    items += [
        dict(kind='sans', size=48, text='Order on Messenger', xy=(x, BAND_Y + 336), anchor='lt', fill=CREAM, t0=6.75, dur=0.5),
        dict(kind='sans', size=40, text=f'm.me/{page}', xy=(x, BAND_Y + 400), anchor='lt', fill=GOLD, t0=6.75, dur=0.5),
    ]
    return items


def item_bbox(it):
    d = ImageDraw.Draw(Image.new('RGB', (4, 4)))
    return d.textbbox(it['xy'], it['text'], font=F(it['kind'], it['size']), anchor=it['anchor'])


def alpha_at(it, t):
    if it['dur'] <= 0:
        return 1.0 if t >= it['t0'] else 0.0
    return min(max((t - it['t0']) / it['dur'], 0.0), 1.0)


def band_top(t):
    if t <= CARD_T0:
        return H
    u = min((t - CARD_T0) / (CARD_T1 - CARD_T0), 1.0)
    return int(round(H - (H - BAND_Y) * ease_out_cubic(u)))


def compose(fab, t, items):
    fr = Image.new('RGB', (W, H), NAVY)
    fr.paste(fab, (FAB[0], FAB[1]))
    d = ImageDraw.Draw(fr)
    by = band_top(t)
    if by < H:
        d.rectangle((0, by, W - 1, H - 1), fill=NAVY)
        d.line((72, by + 18, W - 192, by + 18), fill=GOLD, width=3)
    d.rectangle((0, TOP - 3, W - 1, TOP - 2), fill=GOLD)      # gold rule, fully inside the brand band
    layer = Image.new('RGBA', (W, H), (0, 0, 0, 0))
    ld = ImageDraw.Draw(layer)
    for it in items:
        a = alpha_at(it, t)
        if it['xy'][1] >= BAND_Y and by > it['xy'][1] - 10:
            a = 0.0                                     # never over fabric
        if a > 0:
            ld.text(it['xy'], it['text'], font=F(it['kind'], it['size']), anchor=it['anchor'], fill=(*it['fill'], int(round(255 * a))))
    fr.paste(layer, (0, 0), layer)
    return fr, by


# ---- main ----------------------------------------------------------------
def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--src', required=True)
    ap.add_argument('--sha256', required=True)
    ap.add_argument('--out', required=True)
    ap.add_argument('--code', required=True)
    ap.add_argument('--price', type=int, required=True)
    ap.add_argument('--material', default='')
    ap.add_argument('--page', default='103196179075087')
    ap.add_argument('--stem', default='')
    ap.add_argument('--keep-master', action='store_true')
    a = ap.parse_args()
    if sha256(a.src) != a.sha256:
        raise SystemExit('source sha256 mismatch')
    os.makedirs(a.out, exist_ok=True)
    stem = a.stem or f'{a.code}_tiktok_v2'
    src = load(a.src)
    iw, ih = src.size
    hx, hy, hs = salient_center(src)
    smooth, keys = path(iw, ih, (hx, hy))
    regs, boxes = regions_for_frames(smooth, iw, ih)
    items = text_layer(a.code, a.price, a.material, a.page)
    master = os.path.join(a.out, stem + '.master.mkv')
    ff = subprocess.Popen(['ffmpeg', '-v', 'error', '-y', '-f', 'rawvideo', '-pix_fmt', 'rgb24', '-s', f'{W}x{H}', '-r', str(FPS),
                           '-i', '-', '-threads', '2', '-c:v', 'libx264rgb', '-qp', '0', '-preset', 'veryfast', '-pix_fmt', 'rgb24', master],
                          stdin=subprocess.PIPE)
    gate_regions, g = [], set(gated_frames())
    for i in range(N):
        t = i / FPS
        x, y, w, h = regs[i]
        if i in g:
            fab = cover(crop_box(src, boxes[i]), FW, FH)          # identical code path to the gate
        else:
            fab = src.resize((FW, FH), Image.LANCZOS, box=(x, y, x + w, y + h))
        fr, by = compose(fab, t, items)
        if i in g:
            ex = [[0, by, W, H - by]] if by < H else []
            gate_regions.append({'n': i, 'src': a.src, 'src_box': boxes[i], 'out_box': FAB, 'exclude': ex})
        ff.stdin.write(fr.tobytes())
    ff.stdin.close()
    if ff.wait():
        raise RuntimeError('ffmpeg failed')
    texts = []
    for it in items:
        bb = item_bbox(it)
        texts.append({'text': it['text'], 'size': it['size'], 'font': os.path.basename(FONTS[it['kind']]), 'bbox': list(bb),
                      'in_safe_zone': bb[2] <= W - SAFE['right_px'] and bb[3] <= H - SAFE['bottom_px'] and bb[0] >= 0 and bb[1] >= 0,
                      'band': 'top' if bb[3] <= TOP else 'end_card', 'fade_in': [it['t0'], it['t0'] + it['dur']]})
    save_sidecar(master, {'template': 'v3_tiktok_premium', 'size': [W, H], 'fps': FPS, 'frames': N, 'fabric_regions': gate_regions,
                          'texts': texts, 'min_text_px': min(t['size'] for t in texts), 'preview_only': True,
                          'motion': {'hook_center': [round(hx), round(hy)], 'hook_score': round(hs, 4),
                                     'keys': {k: [round(v, 2) for v in r] for k, r in keys.items()},
                                     'hook_upscale': round(FW / HOOK_W, 3), 'end_scale': round(FW / END_W, 3),
                                     'ease': 'cubic ease-out 0-0.8 s, smootherstep 0.8-9 s', 'resample': 'PIL Lanczos per frame from source'}})
    print(json.dumps({'master': master, 'frames': N}))


if __name__ == '__main__':
    t0 = time.time()
    main()
    print(f'render {time.time() - t0:.1f}s', file=sys.stderr)
