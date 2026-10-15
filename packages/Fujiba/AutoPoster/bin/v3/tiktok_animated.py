#!/usr/bin/env python3
"""Animated, beat-cut 9:16 TikTok clip for ONE product (PREVIEW ONLY: no API, no posting, no
queue rows, no DB writes, nothing copied to public/). Built on tiktok_premium.py's exact-frame
gate method.

    nice -n 10 python3 tiktok_animated.py --src <photo> --sha256 <hex> --out <dir> \
        --code FV-73-B --price 1595 --material-latin 'Hosonuno (100% cotton)' --material-ja '綿100%'

1080x1920, 30 fps, 13 s (390 frames), silent. Cuts on a 120 BPM grid (beat = 15 frames):
  0-1 s   hook      : punch-in on the crane head; "FV-73-B" slides in (text layer + pill overlay)
  1-5 s   close-ups : 4 real motif crops (wing, gold waves, cloud lattice, crane), each with a
                      slow drift; transitions are 6-frame mask wipes ENDING on the beat
                      (bar wipes L->R, T->B, R->L, iris) - both sides are real crops
  5-6 s   pull-back : out box shrinks to the framed layout while the source region grows to
                      the full photo (aspect kept equal -> never stretched)
  6-9 s   reveal    : gold frame draws around the photo (graphic outside the fabric),
                      price pops with an overshoot scale bounce (text layer)
  9-13 s  end card  : photo shrinks to a thumbnail, brand (Mali) clip-reveal, CTA, material
Fabric = real photo pixels only: every frame is ONE Lanczos resample of the original photo
(crop/scale/translate), no grading/filters/interpolation. At every gated frame (every 0.5 s +
each shot's first/last full frame) the region is snapped to common.cover(common.crop_box()) so
fabric_gate --master (unchanged) re-derives it; snapping residual spread smoothly in-shot.
Overlays drawn on fabric at gated frames are declared as `exclude` rects.
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
ROOT = os.path.abspath(os.path.join(BIN, '..', '..', '..', '..'))
sys.path.insert(0, os.path.join(BIN, 'intel'))
sys.path.insert(0, os.path.join(BIN, 'v2'))
for _k in ('OMP_NUM_THREADS', 'OPENBLAS_NUM_THREADS', 'MKL_NUM_THREADS'):
    os.environ.setdefault(_k, '2')
__import__('heavy_guard').acquire('v3-tiktok-animated')

import numpy as np  # noqa: E402
from PIL import Image, ImageDraw, ImageFont  # noqa: E402

from common import cover, crop_box, load, save_sidecar  # noqa: E402

W, H, FPS = 1080, 1920, 30
BEAT = FPS // 2                 # 120 BPM
N = 13 * FPS
WIPE = 6
NAVY = (18, 22, 52)
GOLD = (222, 186, 108)
CREAM = (250, 243, 228)
MUTED = (196, 190, 176)
CX = 480                        # text/layout centre (safe zone x < 960)
FULL = (0, 0, W, H)
FB = (90, 240, 780, 1040)       # framed full photo (3:4)
TB = (270, 160, 420, 560)       # end-card thumbnail (3:4)
FD = os.path.join(BIN, 'v2', 'fonts')
_JA = os.path.join(ROOT, 'public', 'fonts', 'NotoSansJP-Bold.ttf')
FONTS = {
    'brand': os.path.join(FD, 'mali-latin-600-normal.woff'),
    'sans': os.path.join(FD, 'quicksand-latin-700-normal.woff'),
    'ja': _JA if os.path.exists(_JA) else '/usr/share/fonts/opentype/ipafont-gothic/ipagp.ttf',
}
SAFE_X, SAFE_Y = 960, 1640

# motif centres in source px (1500x2000 FV-73-B photo, picked by eye on a 100 px grid)
MOTIFS = {
    'crane_head': (640, 800),
    'wing': (1060, 300),
    'waves': (1190, 1290),
    'lattice': (330, 1330),
    'crane2': (1060, 1640),
}


def sha256(p):
    h = hashlib.sha256()
    with open(p, 'rb') as f:
        for b in iter(lambda: f.read(1 << 20), b''):
            h.update(b)
    return h.hexdigest()


_FC = {}


def F(kind, size):
    k = (kind, size)
    if k not in _FC:
        _FC[k] = ImageFont.truetype(FONTS[kind], size, layout_engine=ImageFont.Layout.RAQM)
    return _FC[k]


def clamp01(u):
    return min(max(u, 0.0), 1.0)


def ease_out_cubic(t):
    return 1 - (1 - t) ** 3


def ease_in_out(t):
    return 0.5 - 0.5 * math.cos(math.pi * clamp01(t))


def ease_out_back(t, s=1.7):
    t = clamp01(t) - 1
    return 1 + (s + 1) * t ** 3 + s * t ** 2


def lerp(a, b, u):
    return a + (b - a) * u


def lerpv(a, b, u):
    return [lerp(x, y, u) for x, y in zip(a, b)]


# ---- shots ---------------------------------------------------------------
class Shot:
    """Frames [f0, f1] (f0 may start inside the incoming wipe). full0 = first fully visible frame.
    region(i) -> (cx, cy, w) in source px; obox(i) -> int out box."""

    def __init__(self, name, f0, full0, f1, region, obox, iw, ih, last_full=None):
        self.name, self.f0, self.full0, self.f1 = name, f0, full0, f1
        self.last_full = f1 if last_full is None else last_full   # last frame not covered by a wipe
        self._region, self._obox, self.iw, self.ih = region, obox, iw, ih

    def obox(self, i):
        return [int(round(v)) for v in self._obox(i)]

    def smooth(self, i):
        """Float source rect (x, y, w, h) with h = w * oh/ow exactly (never stretched)."""
        cx, cy, w = self._region(i)
        ob = self.obox(i)
        h = w * ob[3] / ob[2]
        if h > self.ih:
            w, h = w * self.ih / h, self.ih
        if w > self.iw:
            h, w = h * self.iw / w, self.iw
        x = min(max(cx - w / 2, 0), self.iw - w)
        y = min(max(cy - h / 2, 0), self.ih - h)
        return [x, y, w, h]


def cover_region(box, ow, oh):
    """Source rect that common.cover(common.crop_box(img, box), ow, oh) samples."""
    x, y, w, h = box
    s = max(ow / w, oh / h)
    rw, rh = max(ow, round(w * s)), max(oh, round(h * s))
    ox, oy = (rw - ow) // 2, (rh - oh) // 2
    kx, ky = w / rw, h / rh
    return [x + ox * kx, y + oy * ky, ow * kx, oh * ky]


def snap(sm, iw, ih):
    box = [int(round(v)) for v in sm]
    box[2], box[3] = min(box[2], iw), min(box[3], ih)
    box[0] = min(max(box[0], 0), iw - box[2])
    box[1] = min(max(box[1], 0), ih - box[3])
    return box


def build_shots(iw, ih):
    def still(c, w0, w1, dx=0, dy=0, f0=0, f1=1):
        def r(i):
            u = ease_in_out((i - f0) / max(1, f1 - f0))
            return (c[0] + dx * u, c[1] + dy * u, lerp(w0, w1, u))
        return r

    fixed = lambda b: (lambda i: b)  # noqa: E731
    shots = []
    # hook 0-1 s: punch-in 1000 -> 740 px wide on the crane head (ease-out, settles on beat 2)
    def hook_r(i):
        u = ease_out_cubic(clamp01(i / 26))
        c = MOTIFS['crane_head']
        return (c[0], c[1], lerp(1000, 740, u))
    shots.append(Shot('hook_crane_head', 0, 0, 29, hook_r, fixed(FULL), iw, ih, last_full=30 - WIPE - 1))
    # close-ups 1-5 s, each enters with a wipe ending on its beat
    plan = [('wing', 30, (-40, 30), 770, 730), ('waves', 60, (50, -40), 780, 740),
            ('lattice', 90, (60, 0), 770, 735), ('crane2', 120, (-50, -30), 790, 760)]
    for k, (name, b, d, w0, w1) in enumerate(plan):
        f1 = plan[k + 1][1] - 1 if k + 1 < len(plan) else 149
        lf = plan[k + 1][1] - WIPE - 1 if k + 1 < len(plan) else 149
        shots.append(Shot(name, b - WIPE, b, f1, still(MOTIFS[name], w0, w1, d[0], d[1], b - WIPE, f1),
                          fixed(FULL), iw, ih, last_full=lf))
    end_c2 = shots[-1]._region(149)
    # pull-back 5-6 s (frames 150-179) + reveal hold to 9 s (180-269) + end card shrink 9.0-9.5 s
    full_c = (iw / 2, ih / 2)

    def pb_region(i):
        if i <= 180:
            u = ease_in_out((i - 150) / 30)
            return (lerp(end_c2[0], full_c[0], u), lerp(end_c2[1], full_c[1], u), lerp(end_c2[2], iw, u))
        if i <= 270:                       # slow push 1500 -> 1420 towards the centre crane
            u = ease_in_out((i - 180) / 90)
            return (lerp(full_c[0], 760, u), lerp(full_c[1], 920, u), lerp(iw, 1420, u))
        u = ease_in_out((i - 270) / 15)    # back to the full photo for the thumbnail
        return (lerp(760, full_c[0], u), lerp(920, full_c[1], u), lerp(1420, iw, u))

    def pb_obox(i):
        if i <= 180:
            return lerpv(FULL, FB, ease_in_out((i - 150) / 30))
        if i <= 270:
            return FB
        return lerpv(FB, TB, ease_in_out((i - 270) / 15))

    shots.append(Shot('pullback_reveal_endcard', 150, 150, N - 1, pb_region, pb_obox, iw, ih))
    return shots


def wipe_mask(kind, u, ob):
    """Mask (L, out-box size) of the INCOMING shot at progress u in (0,1)."""
    w, h = ob[2], ob[3]
    m = Image.new('L', (w, h), 0)
    d = ImageDraw.Draw(m)
    if kind == 'lr':
        d.rectangle((0, 0, int(w * u), h), fill=255)
    elif kind == 'tb':
        d.rectangle((0, 0, w, int(h * u)), fill=255)
    elif kind == 'rl':
        d.rectangle((int(w * (1 - u)), 0, w, h), fill=255)
    else:                                    # iris from centre
        r = u * math.hypot(w, h) / 2
        d.ellipse((w / 2 - r, h / 2 - r, w / 2 + r, h / 2 + r), fill=255)
    return m


def wipe_edge(d, kind, u, ob):
    w, h = ob[2], ob[3]
    if kind == 'lr':
        x = int(w * u); d.rectangle((x - 3, 0, x + 3, h), fill=GOLD)
    elif kind == 'tb':
        y = int(h * u); d.rectangle((0, y - 3, w, y + 3), fill=GOLD)
    elif kind == 'rl':
        x = int(w * (1 - u)); d.rectangle((x - 3, 0, x + 3, h), fill=GOLD)
    else:
        r = u * math.hypot(w, h) / 2
        d.ellipse((w / 2 - r, h / 2 - r, w / 2 + r, h / 2 + r), outline=GOLD, width=6)


WIPES = ['lr', 'tb', 'rl', 'iris']


# ---- exact regions -------------------------------------------------------
def shot_gated(s):
    g = {s.full0, s.last_full} | {n for n in range(s.full0, s.last_full + 1) if n % BEAT == 0}
    if s.f1 == N - 1:
        g.add(N - 1)
    return sorted(g)


def shot_regions(s):
    g = shot_gated(s)
    boxes, res = {}, {}
    for n in g:
        sm = s.smooth(n)
        ob = s.obox(n)
        boxes[n] = snap(sm, s.iw, s.ih)
        ex = cover_region(boxes[n], ob[2], ob[3])
        res[n] = [e - v for e, v in zip(ex, sm)]
    regs = {}
    for i in range(s.f0, s.f1 + 1):
        lo = [n for n in g if n <= i]
        hi = [n for n in g if n >= i]
        a = lo[-1] if lo else g[0]
        b = hi[0] if hi else g[-1]
        u = 0.0 if a == b else ease_in_out((i - a) / (b - a))
        r = lerpv(res[a], res[b], u)
        x, y, w, h = s.smooth(i)
        rw, rh = w + r[2], h + r[3]
        ob = s.obox(i)
        # keep the exact out aspect between gated frames (no stretch): average scale
        sc = math.sqrt((rw * rh) / (ob[2] * ob[3]))
        rw, rh = ob[2] * sc, ob[3] * sc
        if i in boxes:
            regs[i] = None
            continue
        rx = min(max(x + r[0], 0.0), s.iw - rw)
        ry = min(max(y + r[1], 0.0), s.ih - rh)
        regs[i] = [rx, ry, rw, rh]
    return regs, boxes


def fabric(src, s, i, regs, boxes):
    ob = s.obox(i)
    if i in boxes:
        return cover(crop_box(src, boxes[i]), ob[2], ob[3])
    x, y, w, h = regs[i]
    return src.resize((ob[2], ob[3]), Image.LANCZOS, box=(x, y, x + w, y + h))


# ---- text / graphics -----------------------------------------------------
def tbbox(kind, size, xy, text, anchor):
    d = ImageDraw.Draw(Image.new('RGB', (4, 4)))
    return list(d.textbbox(xy, text, font=F(kind, size), anchor=anchor))


def draw_runs(img, runs, xy, anchor_center=True, fill=MUTED, alpha=1.0):
    """Mixed-font line: runs = [(kind, size, text)], shared baseline. Returns bbox."""
    d = ImageDraw.Draw(img)
    widths = [d.textlength(t, font=F(k, s)) for k, s, t in runs]
    total = sum(widths)
    x = xy[0] - total / 2 if anchor_center else xy[0]
    x0 = x
    top, bot = 1e9, -1e9
    for (k, s, t), wd in zip(runs, widths):
        bb = d.textbbox((x, xy[1]), t, font=F(k, s), anchor='ls')
        top, bot = min(top, bb[1]), max(bot, bb[3])
        d.text((x, xy[1]), t, font=F(k, s), anchor='ls', fill=(*fill, int(255 * alpha)))
        x += wd
    return [int(x0), int(top), int(math.ceil(x)), int(bot)]


def scaled_text(layer, kind, size, text, center, scale, fill, alpha):
    """Text rendered at a scale (pop/bounce) around its centre. Returns bbox."""
    sz = max(8, int(round(size * scale)))
    d = ImageDraw.Draw(layer)
    bb = d.textbbox(center, text, font=F(kind, sz), anchor='mm')
    d.text(center, text, font=F(kind, sz), anchor='mm', fill=(*fill, int(255 * alpha)))
    return list(bb)


def frame_rect(ob, gap=16):
    return (ob[0] - gap, ob[1] - gap, ob[0] + ob[2] + gap, ob[1] + ob[3] + gap)


def draw_frame(d, rect, u, width=4):
    """Gold frame drawn progressively along its perimeter (u 0..1), starting top-left."""
    x0, y0, x1, y1 = rect
    segs = [((x0, y0), (x1, y0)), ((x1, y0), (x1, y1)), ((x1, y1), (x0, y1)), ((x0, y1), (x0, y0))]
    lens = [abs(b[0] - a[0]) + abs(b[1] - a[1]) for a, b in segs]
    left = u * sum(lens)
    for (a, b), L in zip(segs, lens):
        if left <= 0:
            break
        k = min(1.0, left / L)
        e = (a[0] + (b[0] - a[0]) * k, a[1] + (b[1] - a[1]) * k)
        d.line((a, e), fill=GOLD, width=width)
        left -= L
    # corner ticks once complete
    if u >= 1:
        for cx, cy in ((x0, y0), (x1, y0), (x1, y1), (x0, y1)):
            d.rectangle((cx - 7, cy - 7, cx + 7, cy + 7), outline=GOLD, width=2)


def overlays(fr, t, i, a, ob_main):
    """All text/graphic layers. Returns list of bboxes drawn (for exclude + safe-zone audit)."""
    layer = Image.new('RGBA', (W, H), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    drawn = []
    # hook: "FV-73-B" slides in from the left on a navy pill (0.2-0.6 s), gold rule grows
    if t < 1.0 - 1e-9:
        u = ease_out_back((t - 0.2) / 0.4)
        if t >= 0.2:
            x = lerp(-640, 90, u)
            tb = tbbox('sans', 120, (x + 36, 1420), a.code, 'ls')
            pill = (tb[0] - 36, tb[1] - 34, tb[2] + 36, tb[3] + 34)
            d.rounded_rectangle(pill, 24, fill=(*NAVY, 225))
            d.text((x + 36, 1420), a.code, font=F('sans', 120), anchor='ls', fill=(*CREAM, 255))
            g = clamp01((t - 0.45) / 0.35)
            if g > 0:
                d.rectangle((pill[0] + 36, pill[3] - 22, pill[0] + 36 + int((pill[2] - pill[0] - 72) * g), pill[3] - 16), fill=GOLD)
            drawn.append(('hook_code', [int(v) for v in pill], 120, 'sans', a.code))
    # close-ups: one small honest tag 1.5-4.8 s, slides up + fades
    if 1.5 <= t < 4.8:
        u = ease_out_cubic(clamp01((t - 1.5) / 0.3)) * (1 - clamp01((t - 4.6) / 0.2))
        y = int(1500 + 40 * (1 - u))
        txt = 'Real fabric · real photo'
        tb = tbbox('sans', 44, (90 + 28, y), txt, 'ls')
        pill = (tb[0] - 28, tb[1] - 22, tb[2] + 28, tb[3] + 22)
        d.rounded_rectangle(pill, 20, fill=(*NAVY, int(225 * u)))
        d.text((90 + 28, y), txt, font=F('sans', 44), anchor='ls', fill=(*CREAM, int(255 * u)))
        drawn.append(('tag', [int(v) for v in pill], 44, 'sans', txt))
    # reveal: gold frame draws 6.0-7.0 s, code 6.5 s, price pop 7.0 s (beat), fade out 9.0-9.25
    if 6.0 <= t:
        out = 1 - clamp01((t - 9.0) / 0.25)
        draw_frame(d, frame_rect(ob_main), clamp01((t - 6.0) / 1.0) if t < 9.0 else 1.0)
        drawn.append(('frame', list(frame_rect(ob_main)), None, None, None))
        if out > 0:
            fy = ob_main[1] + ob_main[3] + 16
            if t >= 6.5:
                ua = ease_out_cubic(clamp01((t - 6.5) / 0.35)) * out
                cy = fy + 70 + int(24 * (1 - ua))
                bb = scaled_text(layer, 'sans', 56, a.code, (CX, cy), 1.0, CREAM, ua)
                drawn.append(('code', bb, 56, 'sans', a.code))
            if t >= 7.0:
                sc = ease_out_back((t - 7.0) / 0.45, 2.4)
                bb = scaled_text(layer, 'sans', 150, a.price_s, (CX, fy + 210), max(0.05, sc), GOLD, out * clamp01((t - 7.0) / 0.12))
                drawn.append(('price', bb, 150, 'sans', a.price_s))
    # end card 9.5-13 s
    if t >= 9.5:
        ty = TB[1] + TB[3] + 16
        # brand: clip-reveal left->right 9.5-10.3, then gold underline grows 10.0-10.5
        bsz = 68
        btxt = 'Fujiba Elegance Emporium'
        by = ty + 120
        bb = tbbox('brand', bsz, (CX, by), btxt, 'mm')
        u = ease_in_out((t - 9.5) / 0.8)
        if u > 0:
            bl = Image.new('RGBA', (W, H), (0, 0, 0, 0))
            ImageDraw.Draw(bl).text((CX, by), btxt, font=F('brand', bsz), anchor='mm', fill=(*GOLD, 255))
            clip = Image.new('L', (W, H), 0)
            ImageDraw.Draw(clip).rectangle((0, 0, int(bb[0] + (bb[2] - bb[0]) * u), H), fill=255)
            layer.paste(bl, (0, 0), Image.composite(bl, Image.new('RGBA', (W, H), (0, 0, 0, 0)), clip).split()[3])
            drawn.append(('brand', bb, bsz, 'brand', btxt))
        g = ease_out_cubic(clamp01((t - 10.0) / 0.5))
        if g > 0:
            hw = (bb[2] - bb[0]) / 2 * g
            d.rectangle((CX - hw, bb[3] + 18, CX + hw, bb[3] + 21), fill=GOLD)
        rows = [(10.5, 'sans', 54, a.code + '  ·  ' + a.price_s, CREAM, by + 150),
                (11.0, 'sans', 56, 'Order on Messenger', CREAM, by + 300),
                (11.25, 'sans', 46, 'm.me/' + a.page, GOLD, by + 380)]
        for t0, k, sz, txt, col, y in rows:
            ua = ease_out_cubic(clamp01((t - t0) / 0.35))
            if ua > 0:
                bb2 = scaled_text(layer, k, sz, txt, (CX, y + int(30 * (1 - ua))), 1.0, col, ua)
                drawn.append(('cta', bb2, sz, k, txt))
        ua = ease_out_cubic(clamp01((t - 11.75) / 0.4))
        if ua > 0:
            runs = [('sans', 38, a.mat_latin + '  ·  '), ('ja', 36, a.mat_ja)]
            bb3 = draw_runs(layer, runs, (CX, by + 500 + int(20 * (1 - ua))), fill=MUTED, alpha=ua)
            drawn.append(('material', bb3, 36, 'sans+ja', a.mat_latin + '  ·  ' + a.mat_ja))
    fr.paste(layer, (0, 0), layer)
    return drawn


# ---- main ----------------------------------------------------------------
def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--src', required=True)
    ap.add_argument('--sha256', required=True)
    ap.add_argument('--out', required=True)
    ap.add_argument('--code', required=True)
    ap.add_argument('--price', type=int, required=True)
    ap.add_argument('--material-latin', default='Hosonuno (100% cotton)')
    ap.add_argument('--material-ja', default='綿100%')
    ap.add_argument('--page', default='103196179075087')
    ap.add_argument('--stem', default='')
    a = ap.parse_args()
    a.price_s = f'¥{a.price:,}'
    a.mat_latin, a.mat_ja = a.material_latin, a.material_ja
    if sha256(a.src) != a.sha256:
        raise SystemExit('source sha256 mismatch')
    os.makedirs(a.out, exist_ok=True)
    stem = a.stem or f'{a.code}_anim'
    src = load(a.src)
    iw, ih = src.size
    shots = build_shots(iw, ih)
    exact = [shot_regions(s) for s in shots]
    master = os.path.join(a.out, stem + '.master.mkv')
    ff = subprocess.Popen(['ffmpeg', '-v', 'error', '-y', '-f', 'rawvideo', '-pix_fmt', 'rgb24', '-s', f'{W}x{H}', '-r', str(FPS),
                           '-i', '-', '-threads', '2', '-c:v', 'libx264rgb', '-qp', '0', '-preset', 'veryfast', '-pix_fmt', 'rgb24', master],
                          stdin=subprocess.PIPE)
    gate_regions, texts, cuts = [], {}, []
    for k, s in enumerate(shots):
        cuts.append({'shot': s.name, 'first_full_frame': s.full0, 'last_frame': s.f1, 'enter': WIPES[k - 1] if 0 < k <= 4 else ('cut' if k else 'start'),
                     'wipe_frames': [s.f0, s.full0 - 1] if s.f0 < s.full0 else None, 'last_full_frame': s.last_full, 'gated': shot_gated(s)})
    for i in range(N):
        t = i / FPS
        fr = Image.new('RGB', (W, H), NAVY)
        live = [(k, s) for k, s in enumerate(shots) if s.f0 <= i <= s.f1]
        k, s = [x for x in live if x[1].full0 <= i][-1]
        regs, boxes = exact[k]
        ob = s.obox(i)
        fr.paste(fabric(src, s, i, regs, boxes), (ob[0], ob[1]))
        # incoming wipe on top
        for k2, s2 in live:
            if s2.f0 <= i < s2.full0:
                u = ease_in_out((i - s2.f0 + 1) / (WIPE + 1))
                ob2 = s2.obox(i)
                fab2 = fabric(src, s2, i, *exact[k2])
                kind = WIPES[k2 - 1]
                fr.paste(fab2, (ob2[0], ob2[1]), wipe_mask(kind, u, ob2))
                edge = Image.new('RGBA', (ob2[2], ob2[3]), (0, 0, 0, 0))
                wipe_edge(ImageDraw.Draw(edge), kind, u, ob2)
                fr.paste(edge, (ob2[0], ob2[1]), edge)
        drawn = overlays(fr, t, i, a, ob)
        for name, bb, sz, kind, txt in drawn:
            key = (name, txt)
            if txt is not None and key not in texts:
                texts[key] = {'text': txt, 'size': sz, 'font': kind, 'first_t': round(t, 3), 'bbox_union': list(bb)}
            elif txt is not None:
                u0 = texts[key]['bbox_union']
                texts[key]['bbox_union'] = [min(u0[0], bb[0]), min(u0[1], bb[1]), max(u0[2], bb[2]), max(u0[3], bb[3])]
        if i in boxes:
            ex = []
            for name, bb, *_ in drawn:
                if name == 'frame':
                    continue                     # frame is drawn outside the fabric box
                ix0, iy0 = max(bb[0], ob[0]), max(bb[1], ob[1])
                ix1, iy1 = min(bb[2], ob[0] + ob[2]), min(bb[3], ob[1] + ob[3])
                if ix1 > ix0 and iy1 > iy0:
                    ex.append([ix0 - 2, iy0 - 2, ix1 - ix0 + 4, iy1 - iy0 + 4])
            gate_regions.append({'n': i, 'shot': s.name, 'src': a.src, 'src_box': boxes[i], 'out_box': ob, 'exclude': ex})
        ff.stdin.write(fr.tobytes())
    ff.stdin.close()
    if ff.wait():
        raise RuntimeError('ffmpeg failed')
    for (name, _), v in texts.items():
        b = v['bbox_union']
        if name == 'hook_code':          # slides in from off-screen; audit the settled position
            b = v['bbox_union'] = [90, b[1], b[2], b[3]]
        v['in_safe_zone'] = b[0] >= 0 and b[1] >= 0 and b[2] < SAFE_X and b[3] < SAFE_Y
    save_sidecar(master, {'template': 'v3_tiktok_animated', 'size': [W, H], 'fps': FPS, 'frames': N, 'fabric_regions': gate_regions,
                          'texts': list(texts.values()), 'min_text_px': min(v['size'] for v in texts.values()), 'preview_only': True,
                          'beat_grid': {'bpm': 120, 'beat_frames': BEAT, 'cuts_on_frames': [30, 60, 90, 120, 150, 270],
                                        'wipe_frames_before_beat': WIPE},
                          'shots': cuts, 'fonts': {k: os.path.basename(v) for k, v in FONTS.items()},
                          'resample': 'PIL Lanczos per frame from source; gated frames = common.cover(common.crop_box())'})
    print(json.dumps({'master': master, 'frames': N, 'gated': len(gate_regions)}))


if __name__ == '__main__':
    t0 = time.time()
    main()
    print(f'render {time.time() - t0:.1f}s', file=sys.stderr)
