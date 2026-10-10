#!/usr/bin/env python3
"""
Content Engine v2 story reels, 1080x1920 @30fps. Frames are rendered in PIL
(ffmpeg drawtext cannot shape Burmese/Devanagari) and piped to ffmpeg.
IMAGE LOCK: fabric frames are crops/scales of our photos only; frames that show
fabric unaltered are registered in <out>.json (exact frame index n + the
overlay rects drawn on that frame) for bin/intel/fabric_gate.py --master.

Output is a LOSSLESS RGB master (.mkv, libx264rgb -qp 0); the delivery MP4 is
derived by deliver.py only after the gate passes.

    reel.py <manifest.json> <story> <master.mkv>
Stories: dragon_story (15 s, pattern story) · gift_to_mum (30 s, gift story)
"""
import json
import math
import random
import subprocess
import sys

import numpy as np
from PIL import Image, ImageDraw, ImageFilter

from common import CREAM, GOLD, INK, crop_box, cover, darken, draw_text, fit, gradient, line_h, load, save_sidecar

W, H, FPS = 1080, 1920, 30
regions, texts = [], []
OVER = []          # text/graphic overlay rects drawn in the frame being rendered


def hexrgb(h):
    h = h.lstrip('#')
    return tuple(int(h[i:i + 2], 16) for i in (0, 2, 4))


def ease(x):
    x = min(max(x, 0.0), 1.0)
    return x * x * (3 - 2 * x)


class Reel:
    def __init__(self):
        self.scenes = []          # (dur, fn, transition, tdur)

    def add(self, dur, fn, trans='cut', tdur=0.0):
        self.scenes.append((dur, fn, trans, tdur))

    @property
    def total(self):
        return sum(s[0] for s in self.scenes)

    def frame(self, i):
        OVER.clear()
        before = len(regions)
        img = self._frame(i)
        for r in regions[before:]:       # fabric regions registered in this frame: exact frame no. + overlays
            r['n'] = i
            r['exclude'] = [list(o) for o in OVER]
        return img

    def _frame(self, i):
        t = i / FPS
        start = 0.0
        for k, (dur, fn, trans, tdur) in enumerate(self.scenes):
            if t < start + dur or k == len(self.scenes) - 1:
                lt = t - start
                img = fn(lt, dur, i)
                if k and lt < tdur:
                    pdur, pfn = self.scenes[k - 1][0], self.scenes[k - 1][1]
                    prev = pfn(pdur - 1 / FPS, pdur, None)
                    img = transition(prev, img, ease(lt / tdur), trans)
                return img
            start += dur


def transition(a, b, p, kind):
    if kind == 'fade':
        return Image.blend(a, b, p)
    if kind == 'dip':
        black = Image.new('RGB', a.size)
        return Image.blend(a, black, p * 2) if p < 0.5 else Image.blend(black, b, (p - 0.5) * 2)
    if kind in ('push', 'whip'):
        off = int(W * p)
        out = Image.new('RGB', (W, H))
        out.paste(a, (-off, 0))
        out.paste(b, (W - off, 0))
        if kind == 'whip':
            k = int(40 * math.sin(p * math.pi)) + 1
            out = out.filter(ImageFilter.BoxBlur(k)) if k > 2 else out
        return out
    return b


def text_block(img, lines, t, y, style='rise', start=0.0, colour=CREAM, size=54, panel=True):
    """lines: [(text, size_or_None)], animated in from `start`."""
    p = ease((t - start) / 0.45)
    if p <= 0:
        return img
    layer = Image.new('RGBA', img.size, (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    sizes = [fit(txt, W - 180, s or size) for txt, s in lines]
    hs = [line_h(txt, sz) for (txt, _), sz in zip(lines, sizes)]      # real height: Burmese stacks are tall
    total = sum(hs) + 10 * (len(lines) - 1)
    dy = int((1 - p) * 40) if style == 'rise' else 0
    OVER.append((60, y - 28 + dy, W - 120, total + 62))
    if panel:
        d.rounded_rectangle((60, y - 28 + dy, W - 60, y + total + 34 + dy), 28, fill=(8, 8, 12, int(165 * p)))
    yy = y + dy
    for (txt, _), s, hh in zip(lines, sizes, hs):
        texts.append({'text': txt, 'size': s})
        draw_text(d, (W / 2, yy), txt, s, colour + (int(255 * p),), None, 'c')
        yy += hh + 10
    return Image.alpha_composite(img.convert('RGBA'), layer).convert('RGB')


def blurred_bg(src, ground):
    bg = cover(src, W, H).filter(ImageFilter.GaussianBlur(28))
    return Image.blend(bg, Image.new('RGB', (W, H), darken(ground, 0.5)), 0.45)


def zoom_box(img_box, focus, p):
    """Interpolate a crop box from `focus` (p=0) to the full close-up box (p=1), keeping 9:16."""
    bx, by, bw, bh = img_box
    fx, fy, fw, fh = focus
    # make both boxes 9:16 inside the image box
    def aspect(x, y, w, h):
        tw = min(w, h * 9 / 16)
        th = tw * 16 / 9
        return [x + (w - tw) / 2, y + (h - th) / 2, tw, th]
    a = aspect(fx - fw * 0.15, fy - fh * 0.6, fw * 1.3, fh * 2.2)
    b = aspect(bx, by, bw, bh)
    a[0] = min(max(a[0], bx), bx + bw - a[2]); a[1] = min(max(a[1], by), by + bh - a[3])
    return [int(round(u + (v - u) * p)) for u, v in zip(a, b)]


def fabric_frame(src, box, i, register):
    fr = cover(crop_box(src, box), W, H)
    if register and i is not None:
        regions.append({'t': round((i + 0.5) / FPS, 3), 'src': register, 'src_box': box, 'out_box': [0, 0, W, H]})
    return fr


def end_card(m, ground, story_line):
    def fn(t, dur, i):
        img = gradient(W, H, darken(ground, 0.6), (8, 8, 12))
        d = ImageDraw.Draw(img)
        a = ease(t / 0.6)
        g = tuple(int(c * a) for c in GOLD)
        draw_text(d, (W / 2, 560), 'FUJIBA', 150, g, 'serif', 'c')
        draw_text(d, (W / 2, 740), 'K I M O N O   F A B R I C', 40, g, 'serif', 'c')
        draw_text(d, (W / 2, 860), story_line, fit(story_line, W - 160, 44), CREAM, None, 'c')
        texts.append({'text': story_line, 'size': 44})
        bw = 760
        d.rounded_rectangle(((W - bw) / 2, 1080, (W + bw) / 2, 1200), 60, fill=(0, 132, 255))
        draw_text(d, (W / 2, 1110), 'Chat on Messenger to order', 46, (255, 255, 255), 'serif', 'c')
        draw_text(d, (W / 2, 1240), 'Messenger မှ မှာယူနိုင်ပါသည်', 40, CREAM, 'my', 'c')
        draw_text(d, (W / 2, 1310), 'Messenger मा अर्डर गर्नुहोस्', 38, CREAM, 'ne', 'c')
        texts.extend([{'text': 'cta', 'size': 38}])
        return img
    return fn


# ---------------- stories ----------------
def dragon_story(m):
    p = m['products'][0]
    src = load(p['image'])
    ground = hexrgb(p['ground'])
    bg = blurred_bg(crop_box(src, p['box']), ground)
    r = Reel()
    combo = {'motion': 'zoom-to-motif → pull-back', 'transition': ['whip', 'push', 'dip'], 'text_anim': 'rise',
             'music': 'none (no licensed library yet)', 'pace': 'fast', 'duration': 15}

    def hook(t, dur, i):          # 0–3 s: extreme close-up on the dragon head, slow push in
        box = zoom_box(p['box'], p['focus'], 0.0)
        cx, cy, cw, ch = box
        k = 1 - 0.08 * ease(t / dur)
        nb = [int(cx + cw * (1 - k) / 2), int(cy + ch * (1 - k) / 2), int(cw * k), int(ch * k)]
        img = fabric_frame(src, nb, i, p['image'] if i is not None and i in (15,) else None)
        return text_block(img, [('Why a dragon?', 76), ('နဂါးက ဘာကို ကိုယ်စားပြုလဲ?', 50)], t, 1380, 'rise', 0.3)

    def pull(t, dur, i):          # 3–6.5 s: pull back from motif to the whole print
        box = zoom_box(p['box'], p['focus'], ease(t / dur))
        return fabric_frame(src, box, i, p['image'] if i is not None and abs(t - dur * 0.98) < 1 / FPS else None)

    def meaning(t, dur, i):       # 6.5–10 s: the story (grade-B meaning only)
        img = bg.copy()
        inset = cover(crop_box(src, p['focus']), 620, 620)
        img.paste(inset, (230, 300))
        if i is not None and abs(t - 1.5) < 0.5 / FPS:
            regions.append({'t': round((i + 0.5) / FPS, 3), 'src': p['image'], 'src_box': p['focus'], 'out_box': [230, 300, 620, 620]})
        d = ImageDraw.Draw(img)
        d.rectangle((222, 292, 858, 928), outline=GOLD, width=6)
        img = text_block(img, [('In Japan, the dragon stands for', 46), ('power and protection.', 58)], t, 1030, 'rise', 0.2)
        return text_block(img, [('ဂျပန်မှာ နဂါးဟာ ခွန်အားနဲ့ ကာကွယ်မှုကို ကိုယ်စားပြုပါတယ်', 40)], t, 1300, 'rise', 1.2, panel=True)

    sets = m['set']
    srcs = [load(s['image']) for s in sets]

    def colours(t, dur, i):       # 10–12.5 s: 4 colourways, push every 0.62 s
        n = len(sets)
        seg = dur / n
        k = min(int(t / seg), n - 1)
        lt = t - k * seg
        s = sets[k]
        cur = cover(crop_box(srcs[k], s['box']), W, H)
        if k and lt < 0.22:
            prev = cover(crop_box(srcs[k - 1], sets[k - 1]['box']), W, H)
            cur = transition(prev, cur, ease(lt / 0.22), 'push')
        elif i is not None and abs(lt - seg * 0.6) < 0.5 / FPS:
            regions.append({'t': round((i + 0.5) / FPS, 3), 'src': s['image'], 'src_box': s['box'], 'out_box': [0, 0, W, H]})
        return text_block(cur, [(f"{s['letter']} · {s['colour']}", 64)], 1.0, 1600, 'fade', 0, panel=True)

    r.add(3.0, hook)
    r.add(3.5, pull, 'whip', 0.35)
    r.add(3.5, meaning, 'push', 0.4)
    r.add(2.5, colours, 'push', 0.3)
    # only catalog-derived facts: colourway count comes from the in-stock set (no origin/material claims)
    r.add(2.5, end_card(m, ground, f'{len(sets)} colours'), 'dip', 0.5)
    return r, combo


def gift_to_mum(m):
    p = m['products'][0]
    src = load(p['image'])
    ground = hexrgb(p['ground'])
    stack_img = load(m['stack_image'])
    rnd = random.Random(7)
    dots = [(rnd.random() * W, rnd.random() * H, rnd.uniform(6, 26), rnd.uniform(0.2, 0.7)) for _ in range(40)]
    combo = {'motion': 'bokeh drift → fabric unfold → parallax → stack ken-burns → gift box', 'transition': ['fade', 'dip', 'whip', 'fade', 'dip'],
             'text_anim': 'fade (slow)', 'music': 'none (no licensed library yet)', 'pace': 'calm', 'duration': 30}

    def night(t, dur, i):         # 0–4 s: hook — Tokyo, night, thinking of Mum
        img = gradient(W, H, (14, 18, 38), (4, 4, 10)).convert('RGBA')
        layer = Image.new('RGBA', img.size)
        d = ImageDraw.Draw(layer)
        for x, y, r, a in dots:
            yy = (y - t * 30 * a) % H
            d.ellipse((x - r, yy - r, x + r, yy + r), fill=(255, 200, 140, int(70 * a)))
        img = Image.alpha_composite(img, layer.filter(ImageFilter.GaussianBlur(6))).convert('RGB')
        img = text_block(img, [('Tokyo, 9 PM.', 80)], t, 700, 'fade', 0.3, panel=False)
        return text_block(img, [('Missing Mum back home…', 54), ('အမေ့ကို သတိရနေတယ်…', 50), ('आमाको याद आयो…', 46)], t, 900, 'fade', 1.4, panel=False)

    def unfold(t, dur, i):        # 4–9 s: fabric unfolds top→bottom (exact pixels where revealed)
        full = cover(crop_box(src, p['box']), W, H)
        img = gradient(W, H, (14, 18, 38), (4, 4, 10))
        reveal = int(H * ease(t / (dur * 0.75)))
        if reveal > 0:
            img.paste(full.crop((0, 0, W, reveal)), (0, 0))
            if reveal < H:      # soft fold shadow at the edge being unfolded
                sh = Image.new('L', (W, 80), 0)
                ImageDraw.Draw(sh).rectangle((0, 0, W, 80), fill=120)
                img.paste(Image.new('RGB', (W, 80), (0, 0, 0)), (0, max(0, reveal - 60)), sh.filter(ImageFilter.GaussianBlur(20)))
        if reveal >= H and i is not None and abs(t - dur * 0.9) < 0.5 / FPS:
            regions.append({'t': round((i + 0.5) / FPS, 3), 'src': p['image'], 'src_box': p['box'], 'out_box': [0, 0, W, H]})
        return text_block(img, [('The red of celebration.', 56), ('Cranes for a long, happy life.', 50)], t, 1450, 'fade', 2.6)

    def parallax(t, dur, i):      # 9–15 s: slow diagonal pan over the cranes + meaning in MM
        bx, by, bw, bh = p['box']
        cw, ch = int(bw * 0.62), int(bw * 0.62 * 16 / 9)
        ch = min(ch, bh)
        cw = int(ch * 9 / 16)
        x = int(bx + (bw - cw) * ease(t / dur))
        y = int(by + (bh - ch) * (1 - ease(t / dur)) * 0.5)
        img = fabric_frame(src, [x, y, cw, ch], i, p['image'] if i is not None and abs(t - 1.0) < 0.5 / FPS else None)
        return text_block(img, [('ဂျပန်မှာ ကြိုးကြာငှက်ဟာ', 46), ('အသက်ရှည်ခြင်းနဲ့ ကံကောင်းခြင်း', 46), ('ကို ကိုယ်စားပြုပါတယ်', 46)], t, 1380, 'fade', 0.6)

    stack_bg = cover(stack_img, W, H).filter(ImageFilter.GaussianBlur(30))
    stack_bg = Image.blend(stack_bg, Image.new('RGB', (W, H), (20, 14, 12)), 0.35)

    def stack(t, dur, i):         # 15–21 s: the finished stack card shown WHOLE (never cropped: it carries text), gentle scale-in
        z = 0.92 + 0.06 * ease(t / dur)
        cw = int(W * z)
        ch = int(stack_img.height * cw / stack_img.width)
        card = stack_img.resize((cw, ch), Image.LANCZOS)
        img = stack_bg.copy()
        y0 = 150
        img.paste(card, ((W - cw) // 2, y0))
        return text_block(img, [('Folded. Wrapped. Sent home.', 54), ('ခေါက်၊ ထုပ်ပိုး၊ အိမ်ကို ပို့မယ်', 46)], t, y0 + ch + 70, 'fade', 0.5)

    def giftbox(t, dur, i):       # 21–26 s: kraft box top view, fabric folded inside (exact pixels)
        img = gradient(W, H, (238, 228, 210), (214, 200, 176))
        d = ImageDraw.Draw(img)
        bx0, by0, bw_, bh_ = 140, 420, 800, 760
        d.rectangle((bx0 - 30, by0 - 30, bx0 + bw_ + 30, by0 + bh_ + 30), fill=(176, 132, 86))
        d.rectangle((bx0 - 10, by0 - 10, bx0 + bw_ + 10, by0 + bh_ + 10), fill=(150, 108, 66))
        piece = cover(crop_box(src, p['focus']), bw_, bh_)
        lift = int(30 * (1 - ease(t / 1.2)))
        img.paste(piece, (bx0, by0 + lift))
        if i is not None and abs(t - 2.5) < 0.5 / FPS:
            regions.append({'t': round((i + 0.5) / FPS, 3), 'src': p['image'], 'src_box': p['focus'], 'out_box': [bx0, by0, bw_, bh_]})
        d = ImageDraw.Draw(img)
        d.rectangle((bx0 + bw_ // 2 - 26, by0 - 30, bx0 + bw_ // 2 + 26, by0 + bh_ + 30), fill=GOLD)   # ribbon
        OVER.append((bx0 + bw_ // 2 - 26, by0 - 30, 52, bh_ + 60))
        OVER.append((bx0 + bw_ // 2 - 92, by0 - 112, 184, 164))
        d.ellipse((bx0 + bw_ // 2 - 70, by0 - 90, bx0 + bw_ // 2 + 70, by0 + 30), outline=GOLD, width=22)
        return text_block(img, [('From Japan, to Mum.', 64), ('ဂျပန်ကနေ အမေ့ဆီကို', 50)], t, 1340, 'fade', 0.8, colour=CREAM)

    r = Reel()
    r.add(4.0, night)
    r.add(5.0, unfold, 'fade', 0.5)
    r.add(6.0, parallax, 'dip', 0.6)
    r.add(6.0, stack, 'whip', 0.35)
    r.add(5.0, giftbox, 'fade', 0.6)
    r.add(4.0, end_card(m, ground, 'A gift Mum will keep forever.'), 'dip', 0.6)
    return r, combo


def main():
    m = json.load(open(sys.argv[1]))
    story, out = sys.argv[2], sys.argv[3]
    reel, combo = {'dragon_story': dragon_story, 'gift_to_mum': gift_to_mum}[story](m)
    n = int(round(reel.total * FPS))
    if not out.lower().endswith('.mkv'):
        sys.exit(f'master must be .mkv (got {out}); MP4 is derived by deliver.py after the gate')
    ff = subprocess.Popen(['ffmpeg', '-v', 'error', '-y', '-f', 'rawvideo', '-pix_fmt', 'rgb24', '-s', f'{W}x{H}', '-r', str(FPS), '-i', '-',
                           '-c:v', 'libx264rgb', '-qp', '0', '-preset', 'veryfast', '-pix_fmt', 'rgb24', out], stdin=subprocess.PIPE)
    for i in range(n):
        ff.stdin.write(reel.frame(i).tobytes())
    ff.stdin.close()
    if ff.wait():
        sys.exit('ffmpeg failed')
    save_sidecar(out, {'template': story, 'size': [W, H], 'fps': FPS, 'frames': n, 'combo': combo, 'fabric_regions': regions,
                       'min_text_px': min((t['size'] for t in texts), default=None)})
    print(out)


if __name__ == '__main__':
    main()
