"""Shared helpers for Content Engine v2 renderers. Fabric pixels always come from our own photos."""
import json
import os
import re

import numpy as np
from PIL import Image, ImageDraw, ImageFilter, ImageFont, ImageOps

HERE = os.path.dirname(os.path.abspath(__file__))
F = os.path.join(HERE, 'fonts')
FONT = {
    'serif': os.path.join(F, 'playfair-display-latin-700-normal.woff'),
    'script': os.path.join(F, 'great-vibes-latin-400-normal.woff'),
    'my': os.path.join(F, 'noto-sans-myanmar-myanmar-700-normal.woff'),
    'ne': os.path.join(F, 'noto-sans-devanagari-devanagari-700-normal.woff'),
    'ja': '/usr/share/fonts/opentype/ipafont-gothic/ipag.ttf',
    'sans': '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
}
GOLD = (222, 186, 108)
CREAM = (250, 243, 228)
INK = (24, 22, 30)


def font(kind, size):
    return ImageFont.truetype(FONT[kind], size, layout_engine=ImageFont.Layout.RAQM)


def script_of(text):
    if re.search('[က-႟]', text):
        return 'my'
    if re.search('[ऀ-ॿ]', text):
        return 'ne'
    if re.search('[぀-ヿ一-鿿]', text):
        return 'ja'
    return 'serif'


SCRIPT_RE = [('my', '[\u1000-\u109F\uAA60-\uAA7F]'), ('ne', '[\u0900-\u097F]'),
             ('ja', '[\u3000-\u30FF\u4E00-\u9FFF\uFF00-\uFFEF]')]


def _cls(ch):
    for k, rx in SCRIPT_RE:
        if re.match(rx, ch):
            return k
    return None          # Latin / digits / punctuation / space


def runs(text, base):
    """Split into script runs, each drawn with a font that has the glyphs.
    Latin inside Burmese/Nepali/Japanese lines uses DejaVu Sans; otherwise the base Latin font."""
    latin = base if base in ('serif', 'script', 'sans') else 'sans'
    out = []
    for ch in text:
        k = _cls(ch)
        if k is None and ch == ' ' and out:
            k = out[-1][1]                       # spaces stick to the current run
        k = k or latin
        if out and out[-1][1] == k:
            out[-1][0] += ch
        else:
            out.append([ch, k])
    return [(s, k) for s, k in out]


def _size(k, size):
    return int(size * 0.85) if k == 'sans' else size


def text_w(text, size, kind=None):
    kind = kind or script_of(text)
    return sum(font(k, _size(k, size)).getlength(s) for s, k in runs(text, kind))


def line_h(text, size, kind=None):
    """Real ink height of a mixed line (Burmese stacks are tall) — use for vertical layout."""
    kind = kind or script_of(text)
    asc = max(font(k, _size(k, size)).getmetrics()[0] for _, k in runs(text, kind))
    dsc = max(font(k, _size(k, size)).getmetrics()[1] for _, k in runs(text, kind))
    return asc + dsc


def draw_text(d, xy, text, size, fill, kind=None, anchor='l'):
    """Mixed-script text on ONE shared baseline. xy = (x, top). anchor l|c|r on x. Returns width."""
    kind = kind or script_of(text)
    rs = runs(text, kind)
    w = text_w(text, size, kind)
    x, y = xy
    x = x - w / 2 if anchor == 'c' else x - w if anchor == 'r' else x
    base = y + max(font(k, _size(k, size)).getmetrics()[0] for _, k in rs)
    for s, k in rs:
        f = font(k, _size(k, size))
        d.text((x, base), s, font=f, fill=fill, anchor='ls')
        x += f.getlength(s)
    return w


def fit(text, max_w, size, kind=None, min_size=26):
    while size > min_size and text_w(text, size, kind) > max_w:
        size -= 2
    return size


def load(path):
    return ImageOps.exif_transpose(Image.open(path)).convert('RGB')


def crop_box(img, box):
    x, y, w, h = box
    return img.crop((x, y, x + w, y + h))


def cover(img, w, h):
    """Scale-to-cover + centre crop (fabric is never stretched)."""
    s = max(w / img.width, h / img.height)
    im = img.resize((max(w, round(img.width * s)), max(h, round(img.height * s))), Image.LANCZOS)
    x, y = (im.width - w) // 2, (im.height - h) // 2
    return im.crop((x, y, x + w, y + h))


def shadow(canvas, box, radius=24, offset=(0, 14), opacity=110):
    x0, y0, x1, y1 = box
    sh = Image.new('L', canvas.size, 0)
    ImageDraw.Draw(sh).rounded_rectangle((x0 + offset[0], y0 + offset[1], x1 + offset[0], y1 + offset[1]), 12, fill=opacity)
    sh = sh.filter(ImageFilter.GaussianBlur(radius))
    canvas.paste(Image.new('RGB', canvas.size, (0, 0, 0)), (0, 0), sh)


def gradient(w, h, top, bottom):
    t = np.linspace(0, 1, h)[:, None, None]
    arr = (np.array(top)[None, None, :] * (1 - t) + np.array(bottom)[None, None, :] * t).repeat(w, 1)
    return Image.fromarray(arr.astype(np.uint8))


def darken(rgb, f):
    return tuple(int(c * f) for c in rgb)


def cta_bar(canvas, y, h, accent):
    """White Messenger CTA bar (EN + Burmese) — organic posts can't carry a real button."""
    d = ImageDraw.Draw(canvas)
    W = canvas.width
    d.rectangle((0, y, W, y + h), fill=(255, 255, 255))
    cx, cy = 70, y + h // 2
    d.ellipse((cx - 34, cy - 34, cx + 34, cy + 34), fill=(0, 132, 255))
    d.polygon([(cx - 14, cy + 22), (cx - 20, cy + 40), (cx + 2, cy + 28)], fill=(0, 132, 255))
    d.line([(cx - 16, cy + 6), (cx - 4, cy - 8), (cx + 4, cy + 2), (cx + 16, cy - 10)], fill='white', width=6)
    draw_text(d, (124, y + 14), 'Chat on Messenger to order', 34, INK, 'serif')
    draw_text(d, (124, y + 58), 'Messenger မှ မှာယူနိုင်ပါသည်', 26, (70, 70, 80), 'my')
    pw = 170
    d.rounded_rectangle((W - pw - 40, cy - 30, W - 40, cy + 30), 30, fill=accent)
    draw_text(d, (W - 40 - pw / 2, cy - 20), 'ORDER', 32, (255, 255, 255), 'serif', 'c')


def save_sidecar(out, data):
    with open(out + '.json', 'w') as f:
        json.dump(data, f, ensure_ascii=False, indent=1)
