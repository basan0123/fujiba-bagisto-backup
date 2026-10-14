"""Fujiba V3 production-concept families 9-11 (PREVIEW ONLY, FREE: no AI, no paid API).

  9  editorial   – premium Japanese fashion editorial: asymmetric magazine layout, one large
                   real-fabric crop bleeding off the top-left, one inset detail, serif title,
                   generous whitespace.
  10 traditional – Myanmar htamein / Nepali lungi styling: tall real-fabric panels side by side
                   with a waist-band strip (a separate crop of the same photo), Burmese or
                   Nepali headline. No model, no warp: layout only *implies* wear and says so.
  11 cinematic   – dark fabric-detail campaign: letterboxed film-strip of real motif crops.

Rules shared with families.py: every fabric pixel is a declared region rebuilt exactly by
bin/intel/fabric_gate.py --master (cover(crop_box(src, box), w, h)); uniform scale only (never
stretched, mirrored, recoloured or redrawn); regions never overlap; text never sits on fabric.
Brand mark "Fujiba Elegance Emporium" in Mali (OFL). Name + price come from ctx (product_flat).
"""
import os
import sys

from PIL import ImageFont

import families as F
from fam_collection import BRAND, BRAND_FONTS, BRAND_RGB

from common import GOLD, INK, shadow  # noqa: E402  (path set up by families)

W, H = F.W, F.H
MALI = BRAND_FONTS[1][1]
MAX_ZOOM = 2.0          # crops are never enlarged more than 2x photo scale


def mali(size):
    return ImageFont.truetype(MALI, size)


def box_for(img, aspect, frac, cx=0.5, cy=0.5):
    """Largest-ish box of given aspect (w/h) covering `frac` of the photo's short side, centred at (cx, cy)."""
    if aspect >= 1:
        w = int(min(img.width, img.height * aspect) * frac)
        h = int(w / aspect)
    else:
        h = int(min(img.height, img.width / aspect) * frac)
        w = int(h * aspect)
    x = int(min(max(0, cx * img.width - w / 2), img.width - w))
    y = int(min(max(0, cy * img.height - h / 2), img.height - h))
    return (x, y, w, h)


def min_src(out_w, out_h, aspect_box):
    return aspect_box[2] * MAX_ZOOM >= out_w and aspect_box[3] * MAX_ZOOM >= out_h


class _Prod(F.Family):
    enabled = False     # never picked up by batch.py / nightly cron; run via run_production.py only

    def brand(self, xy, size, fill=BRAND_RGB, anchor='l'):
        f = mali(size)
        x, y = xy
        if anchor == 'r':
            x -= f.getlength(BRAND)
        elif anchor == 'c':
            x -= f.getlength(BRAND) / 2
        self.d.text((x, y), BRAND, font=f, fill=fill)
        self.texts.append({'text': BRAND, 'size': size, 'font': 'Mali SemiBold'})

    def detail(self, img, n, size):
        return F.detail_boxes(img, n, size)

    def zoom_note(self, boxes, outs):
        return round(max(o[2] / b[2] for b, o in zip(boxes, outs)), 2)


class Editorial(_Prod):
    id, name = 9, 'editorial'
    PAPER = (247, 244, 238)

    def render(self, ctx, out):
        p = ctx
        self.begin(self.PAPER)
        img = self.src(p['image'])
        big_out = (0, 0, 690, 1010)
        big = box_for(img, big_out[2] / big_out[3], 0.92, 0.45, 0.45)
        self.fabric(p['image'], big, big_out)
        det = self.detail(img, 1, min(img.width, img.height) // 4)[0]
        det_out = (770, 770, 250, 250)
        shadow(self.canvas, (det_out[0], det_out[1], det_out[0] + 250, det_out[1] + 250), 18, (0, 10), 70)
        self.d.rectangle((det_out[0] - 8, det_out[1] - 8, det_out[0] + 257, det_out[1] + 257), fill=(255, 255, 255))
        self.fabric(p['image'], det, det_out)
        # right column, editorial type
        self.text((750, 90), 'No.', 30, F.MUTED, 'serif')
        self.text((750, 132), p['name'], 58, INK, 'serif')
        self.d.line((750, 220, 1020, 220), fill=INK, width=2)
        self.text((750, 250), 'The', 46, INK, 'serif')
        self.text((750, 306), 'Print', 46, INK, 'serif')
        self.text((750, 362), 'Edit', 46, INK, 'serif')
        self.text((750, 440), '和柄コットン', 30, F.MUTED, 'ja')
        self.text((750, 700), 'detail ↓', 26, F.MUTED, 'sans')
        # foot
        self.text((60, 1080), 'Real fabric. Real photo.', 30, INK, 'serif')
        m = (p.get('material') or {}).get('en')
        self.text((60, 1132), m if m else 'material not confirmed', 26, F.MUTED, 'sans')
        self.text((60, 1240), f"{p['name']}  ·  ¥{int(p['price']):,}", 40, INK, 'serif')
        self.brand((W - 60, 1250), 34, anchor='r')
        return self.finish(out, p, {'crop_boxes': [list(big), list(det)],
                                    'max_zoom': self.zoom_note([big, det], [big_out, det_out])})


class Traditional(_Prod):
    """Headline language from ctx['audience']: 'my' (htamein) or 'ne' (lungi)."""
    id, name = 10, 'traditional'
    BG = (238, 228, 210)
    COPY = {
        'my': ('ထမီအတွက် ပိတ်စ', 'ချည်သား ၁၀၀% · Messenger မှ မှာယူပါ', 'Htamein styling layout'),
        'ne': ('लुंगीका लागि कपडा', '१००% कपास · Messenger मा अर्डर गर्नुहोस्', 'Lungi styling layout'),
    }

    def render(self, ctx, out):
        p = ctx
        lang = p.get('audience', 'my')
        head, sub, label = self.COPY[lang]
        if (p.get('material') or {}).get('en', '').find('100% cotton') < 0:
            sub = sub.split(' · ')[1]                      # no cotton claim unless catalog says so
        self.begin(self.BG)
        img = self.src(p['image'])
        self.text((60, 70), head, 64, INK, lang)
        self.text((60, 170), sub, 30, F.MUTED, lang)
        # waist band: a wide horizontal crop of the same photo (the tuck/fold zone)
        band_out = (120, 260, 840, 90)
        band = box_for(img, band_out[2] / band_out[3], 1.0, 0.5, 0.12)
        # two tall panels = front wrap + overlap panel, separated by a shadowed gap (no overlap)
        lp_out = (120, 362, 500, 820)
        rp_out = (632, 362, 328, 820)
        lp = box_for(img, lp_out[2] / lp_out[3], 0.95, 0.35, 0.55)
        rp = box_for(img, rp_out[2] / rp_out[3], 0.95, 0.80, 0.55)
        shadow(self.canvas, (120, 260, 960, 1182), 28, (0, 16), 90)
        self.d.rectangle((120, 350, 960, 362), fill=(70, 52, 40))   # tuck line, drawn, not fabric
        self.d.rectangle((620, 362, 632, 1182), fill=(70, 52, 40))  # overlap seam
        self.fabric(p['image'], band, band_out)
        self.fabric(p['image'], lp, lp_out)
        self.fabric(p['image'], rp, rp_out)
        # side annotations (on the background, never on fabric)
        self.text((970, 290), 'waist', 26, F.MUTED, 'sans')
        self.text((970, 640), 'overlap', 26, F.MUTED, 'sans')
        self.text((60, 1205), f"{p['name']}  ·  ¥{int(p['price']):,}", 40, INK, 'serif')
        self.text((60, 1262), f'{label} – flat fabric shown, not a worn photo', 26, F.MUTED, 'sans')
        self.brand((W - 60, 1214), 32, anchor='r')
        return self.finish(out, p, {'audience': lang, 'crop_boxes': [list(band), list(lp), list(rp)],
                                    'max_zoom': self.zoom_note([band, lp, rp], [band_out, lp_out, rp_out])})


class Cinematic(_Prod):
    id, name = 11, 'cinematic'
    BG = (12, 11, 14)

    def render(self, ctx, out):
        p = ctx
        self.begin(self.BG)
        img = self.src(p['image'])
        fw, fh, x0 = 920, 300, 80
        n = 3
        size = min(img.width, img.height) // 3
        sq = self.detail(img, n, size)
        frames, outs = [], []
        ys = [150, 490, 830]
        for i, (bx, by, s, _) in enumerate(sq):
            # widen each motif square to film aspect inside the photo
            h = int(s * 0.75)
            w = int(h * fw / fh)
            w = min(w, img.width)
            h = int(w * fh / fw)
            cx, cy = bx + s / 2, by + s / 2
            x = int(min(max(0, cx - w / 2), img.width - w))
            y = int(min(max(0, cy - h / 2), img.height - h))
            frames.append((x, y, w, h))
            outs.append((x0, ys[i], fw, fh))
        for i, (b, o) in enumerate(zip(frames, outs)):
            ox, oy = o[0], o[1]
            # sprocket holes on the black margin, outside fabric
            for k in range(8):
                hx = ox + 20 + k * 116
                self.d.rounded_rectangle((hx, oy - 30, hx + 34, oy - 12), 4, fill=(48, 46, 52))
                self.d.rounded_rectangle((hx, oy + fh + 12, hx + 34, oy + fh + 30), 4, fill=(48, 46, 52))
            self.fabric(p['image'], b, o)
        for i, o in enumerate(outs):
            self.text((24, o[1] + o[3] // 2 - 13), f'0{i + 1}', 26, (150, 140, 120), 'sans')
        self.text((80, 60), 'DETAIL', 52, GOLD, 'serif')
        self.text((1000, 76), p['name'], 34, (230, 222, 205), 'serif', 'r')
        self.text((80, 1200), f"¥{int(p['price']):,}", 44, (240, 232, 214), 'serif')
        self.text((80, 1262), 'Real photo crops · no filter', 26, (150, 140, 120), 'sans')
        self.brand((1000, 1210), 32, fill=(214, 160, 168), anchor='r')
        return self.finish(out, p, {'crop_boxes': [list(b) for b in frames],
                                    'max_zoom': self.zoom_note(frames, outs)})


for _f in (Editorial, Traditional, Cinematic):
    F.REGISTRY[_f.id] = _f
