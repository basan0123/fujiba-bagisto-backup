#!/usr/bin/env python3
"""
Facebook post photo for one product.

    post_photo.py <out.jpg> <CODE> <src1> [src2 ...]

Picks the source that is most "fabric only" (fewest flat white/grey pixels from
labels, packaging and background), crops to 4:5 trimming the edges, resizes to
1440x1800, applies a restrained enhancement (no regeneration, so the print is
never altered), stamps the FUJIBA wordmark with the product code below it, and
prints JSON {"src":..., "colour":...} on stdout. HEIC sources go through
ImageMagick first.
"""
import colorsys
import json
import os
import subprocess
import sys
import tempfile

from PIL import Image, ImageDraw, ImageEnhance, ImageFilter, ImageFont, ImageOps

W, H = 1440, 1800
SERIF = '/usr/share/fonts/truetype/liberation/LiberationSerif-Bold.ttf'
SANS = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'
GOLD = (217, 184, 102)


def load(path):
    if path.lower().endswith(('.heic', '.heif')):
        tmp = tempfile.mktemp(suffix='.jpg')
        subprocess.run(['convert', path + '[0]', '-quality', '95', tmp], check=True)
        img = Image.open(tmp)
        img.load()
        os.unlink(tmp)
    else:
        img = Image.open(path)
    return ImageOps.exif_transpose(img).convert('RGB')


def non_fabric_ratio(img):
    """Share of near-white / flat grey pixels — labels, bags, backdrop."""
    small = img.resize((80, 100))
    bad = 0
    for r, g, b in small.getdata():
        h, s, v = colorsys.rgb_to_hsv(r / 255, g / 255, b / 255)
        if (v > 0.82 and s < 0.12) or (s < 0.06 and 0.35 < v < 0.9):
            bad += 1
    return bad / (80 * 100)


def crop_45(img):
    w, h = img.size
    m = 0.04
    img = img.crop((int(w * m), int(h * m), int(w * (1 - m)), int(h * (1 - m))))
    w, h = img.size
    if w / h > 0.8:
        nw = int(h * 0.8)
        x = (w - nw) // 2
        img = img.crop((x, 0, x + nw, h))
    else:
        nh = int(w / 0.8)
        y = (h - nh) // 2
        img = img.crop((0, y, w, y + nh))
    return img.resize((W, H), Image.LANCZOS)


def enhance(img):
    img = Image.blend(img, ImageOps.autocontrast(img, cutoff=0.5), 0.35)
    img = ImageEnhance.Color(img).enhance(1.05)
    return img.filter(ImageFilter.UnsharpMask(radius=1.4, percent=55, threshold=3))


COLOURS = [
    ('black', None), ('white', None), ('grey', None),
    ('red', (345, 15)), ('orange', (15, 38)), ('gold', (38, 65)),
    ('green', (65, 170)), ('blue', (170, 250)), ('purple', (250, 300)), ('pink', (300, 345)),
]


def colour_of(img):
    small = img.resize((60, 75)).quantize(colors=5).convert('RGB')
    counts = {}
    for r, g, b in small.getdata():
        h, s, v = colorsys.rgb_to_hsv(r / 255, g / 255, b / 255)
        hd = h * 360
        if v < 0.22:
            name = 'black'
        elif s < 0.15:
            name = 'white' if v > 0.75 else 'grey'
        else:
            name = 'red' if (hd >= 345 or hd < 15) else next(n for n, rng in COLOURS[4:] if rng[0] <= hd < rng[1])
            if name == 'blue' and v < 0.45:
                name = 'navy'
            if name in ('orange', 'gold') and v < 0.5:
                name = 'brown'
        counts[name] = counts.get(name, 0) + 1
    # Flat grey/white is mostly backdrop, bag or label — use the strongest
    # real colour unless the fabric itself is plainly white or grey.
    chroma = {k: v for k, v in counts.items() if k not in ('grey', 'white')}
    if chroma and sum(chroma.values()) >= 0.25 * sum(counts.values()):
        counts = chroma
    return max(counts, key=counts.get)


MYAN = '/usr/share/fonts/truetype/padauk/Padauk-Bold.ttf'


def _font_for(text, size):
    # Burmese needs Padauk; Latin/digits look better in DejaVu.
    if any('\u1000' <= ch <= '\u109f' for ch in text):
        return ImageFont.truetype(MYAN, size)
    return ImageFont.truetype(SANS, size)


def stamp(img, code, name=None):
    """Top: FUJIBA KIMONO wordmark band. Bottom: product name band."""
    d = ImageDraw.Draw(img, 'RGBA')
    navy = (20, 27, 51)

    # top band (fades out downward)
    top = 190
    for i in range(top):
        a = int(215 * (1 - (i / top) ** 2))
        d.line((0, i, W, i), fill=navy + (a,))
    f1 = ImageFont.truetype(SERIF, 92)
    f2 = ImageFont.truetype(SERIF, 26)
    d.text((W / 2, 22), 'FUJIBA', font=f1, fill=GOLD, anchor='ma')
    d.text((W / 2, 124), 'K  I  M  O  N  O', font=f2, fill=GOLD, anchor='ma')

    # bottom band with the product name
    label = (name or code).strip()
    size = 64
    f3 = _font_for(label, size)
    while d.textlength(label, font=f3) > W - 120 and size > 30:
        size -= 4
        f3 = _font_for(label, size)
    bh = 170
    for i in range(bh):
        a = int(225 * ((i / bh) ** 0.6))
        d.line((0, H - bh + i, W, H - bh + i), fill=navy + (a,))
    d.line((W / 2 - 220, H - 128, W / 2 + 220, H - 128), fill=GOLD + (220,), width=2)
    d.text((W / 2, H - 64), label, font=f3, fill=(255, 255, 255), anchor='mm')
    return img


def main():
    out, code, srcs = sys.argv[1], sys.argv[2], sys.argv[3:]
    best = min(srcs, key=lambda p: non_fabric_ratio(load(p)))
    img = enhance(crop_45(load(best)))
    colour = colour_of(img)
    stamp(img, code, os.environ.get('POST_NAME'))
    os.makedirs(os.path.dirname(out), exist_ok=True)
    img.save(out, 'JPEG', quality=90, optimize=True, progressive=True)
    print(json.dumps({'src': best, 'colour': colour}))


if __name__ == '__main__':
    main()
