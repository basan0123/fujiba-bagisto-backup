"""Fujiba V3 creative families (PREVIEW ONLY) — renderer registry.

Every family implements render(ctx, out_png) -> sidecar dict. The master PNG is lossless;
its <out>.json sidecar declares every fabric region exactly as bin/intel/fabric_gate.py
--master rebuilds it (cover(crop_box(src, src_box), w, h) pasted at out_box). Fabric pixels
are only ever real product-photo pixels; no colour change, filter or AI. Text comes from
ctx (product_flat via bin/v3/ctx.php): our public code + own price, never supplier codes.
"""
import os
import sys

import numpy as np
from PIL import Image, ImageDraw

HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, os.path.join(os.path.dirname(HERE), 'v2'))
from common import CREAM, GOLD, INK, cover, crop_box, draw_text, load, save_master, save_sidecar  # noqa: E402

W, H = 1080, 1350
MUTED = (110, 104, 96)


class Family:
    id = None
    name = None
    enabled = True

    def render(self, ctx, out):
        raise NotImplementedError

    # ---- shared helpers -------------------------------------------------
    def begin(self, bg):
        self.canvas = Image.new('RGB', (W, H), bg)
        self.d = ImageDraw.Draw(self.canvas)
        self.regions, self.texts, self.unchecked = [], [], []
        self._src = {}

    def src(self, path):
        if path not in self._src:
            self._src[path] = load(path)
        return self._src[path]

    def fabric(self, path, src_box, out_box):
        """Paste real pixels exactly the way the gate rebuilds them."""
        x, y, w, h = out_box
        self.canvas.paste(cover(crop_box(self.src(path), src_box), w, h), (x, y))
        self.regions.append({'src': path, 'src_box': list(map(int, src_box)), 'out_box': [x, y, w, h]})

    def text(self, xy, s, size, fill=INK, kind=None, anchor='l'):
        draw_text(self.d, xy, s, size, fill, kind, anchor)
        self.texts.append({'text': s, 'size': size})

    def price_line(self, p):
        return f"{p['name']}  ·  ¥{int(p['price']):,}"

    def material_size(self, p, y, x=60, size=30):
        m = p.get('material') or {}
        if m.get('en') == 'Hosonuno (100% cotton)':
            self.text((x, y), 'Hosonuno (100% cotton) · 綿100%', size, MUTED, 'ja')
            y += size + 16
        sz = p.get('size') or {}
        if sz.get('width_cm') or sz.get('length_m'):
            parts = []
            if sz.get('width_cm'):
                parts.append(f"width {sz['width_cm']:g} cm")
            if sz.get('length_m'):
                parts.append(f"{sz['length_m']:g} m")
            self.text((x, y), ' · '.join(parts), size, MUTED, 'sans')
        else:
            self.text((x, y), 'size not confirmed', 26, MUTED, 'sans')
        return y

    def finish(self, out, ctx_p, extra=None):
        save_master(self.canvas, out)
        side = {'family': self.id, 'family_name': self.name, 'size': [W, H], 'product_id': ctx_p['product_id'],
                'name': ctx_p['name'], 'price': ctx_p['price'], 'fabric_regions': self.regions,
                'unchecked_regions': self.unchecked, 'texts': self.texts,
                'min_text_px': min((t['size'] for t in self.texts), default=None), 'preview_only': True}
        side.update(extra or {})
        save_sidecar(out, side)
        return side


# ---- crop selection (deterministic) ------------------------------------
def detail_boxes(img, n, size, avoid=()):
    """Top-n non-overlapping square boxes by motif detail + gold-ish highlight share."""
    a = np.asarray(img.convert('RGB')).astype(np.float32)
    g = a.mean(2)
    edge = np.abs(np.diff(g, axis=0))[:, :-1] + np.abs(np.diff(g, axis=1))[:-1, :]
    r, gg, b = a[..., 0], a[..., 1], a[..., 2]
    gold = ((r > 150) & (gg > 120) & (b < gg * 0.8) & (r > b + 50)).astype(np.float32)
    step = size // 3
    cands = []
    for y in range(0, img.height - size, step):
        for x in range(0, img.width - size, step):
            s = edge[y:y + size, x:x + size].mean() / 40 + gold[y:y + size, x:x + size].mean() * 4
            cands.append((s, x, y))
    cands.sort(reverse=True)
    out = list(avoid)
    picked = []
    for s, x, y in cands:
        if all(abs(x - ox) >= size or abs(y - oy) >= size for ox, oy, *_ in out):
            picked.append((x, y, size, size))
            out.append((x, y))
        if len(picked) == n:
            break
    return picked


# ---- families ----------------------------------------------------------
class Closeup(Family):
    """3: zoom crops of real motifs / gold detail."""
    id, name = 3, 'closeup'

    def render(self, ctx, out):
        p = ctx
        self.begin(CREAM)
        img = self.src(p['image'])
        boxes = detail_boxes(img, 4, min(img.width, img.height) // 5)
        self.text((60, 70), p['name'], 66, INK, 'serif')
        self.text((W - 60, 82), f"¥{int(p['price']):,}", 54, INK, 'serif', 'r')
        self.d.line((60, 170, W - 60, 170), fill=GOLD, width=3)
        self.text((60, 192), 'Detail · close-up of the real fabric', 28, MUTED, 'sans')
        cs, gap, x0, y0 = 440, 30, 85, 240
        for i, b in enumerate(boxes):
            x, y = x0 + (i % 2) * (cs + gap), y0 + (i // 2) * (cs + gap)
            self.d.rectangle((x - 4, y - 4, x + cs + 3, y + cs + 3), fill=(232, 222, 200))
            self.fabric(p['image'], b, (x, y, cs, cs))
        zoom = cs / boxes[0][2]
        self.text((60, y0 + 2 * cs + gap + 24), f'crops shown at ~{zoom:.1f}× photo scale', 26, MUTED, 'sans')
        self.material_size(p, y0 + 2 * cs + gap + 70)
        return self.finish(out, p, {'crop_boxes': boxes})


class NeedsModel(Family):
    enabled = False

    def render(self, ctx, out):
        raise NotImplementedError('needs model images')


class Worn(NeedsModel):
    id, name = 1, 'worn'


class Garment(NeedsModel):
    id, name = 2, 'garment'


REGISTRY = {f.id: f for f in (Worn, Garment, Closeup)}
ORDER = [1, 2, 3, 4, 5, 6]


def enabled_ids():
    return {i for i, f in REGISTRY.items() if f.enabled}


def get(fid):
    return REGISTRY[int(fid)]()
