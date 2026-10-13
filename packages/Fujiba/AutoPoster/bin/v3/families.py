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
from common import CREAM, GOLD, INK, cover, crop_box, draw_text, gradient, load, save_master, save_sidecar, shadow  # noqa: E402

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


class Stack(Family):
    """5: same pattern, every in-stock colour, folded stack on a light gift background."""
    id, name = 5, 'stack'

    def render(self, ctx, out):
        p = ctx
        mem = sorted(p['series_members'], key=lambda m: m['product_id'] != p['product_id'])
        self.begin((246, 241, 233))
        self.canvas.paste(gradient(W, H, (251, 248, 242), (236, 228, 216)), (0, 0))
        self.text((60, 64), p['series'], 64, INK, 'serif')
        self.text((60, 150), f'{len(mem)} colours in stock · gift-ready folds', 30, MUTED, 'sans')
        self.d.line((60, 200, W - 60, 200), fill=GOLD, width=3)
        sw, top_h, band, gap = 680, 420, 190, 10
        x = 70
        total = top_h + gap + (len(mem) - 1) * (band + gap)
        y = 250
        shadow(self.canvas, (x, y, x + sw, y + total), radius=22, offset=(0, 16), opacity=70)
        for i, m in enumerate(mem):
            img = self.src(m['image'])
            h = top_h if i == 0 else band
            # band = a horizontal strip of the photo (as the folded edge would show it)
            sh = round(img.width * h / sw)
            sy = (img.height - sh) * (i + 1) // (len(mem) + 1)
            self.fabric(m['image'], (0, sy, img.width, sh), (x, y, sw, h))
            ty = y + h // 2 - 40
            self.text((x + sw + 40, ty), m['name'], 40, INK, 'serif')
            self.text((x + sw + 40, ty + 52), f"¥{int(m['price']):,}", 34, MUTED, 'serif')
            y += h + gap
        self.text((x, y + 30), 'approx. scale · real photo of each colour', 26, MUTED, 'sans')
        self.material_size(p, y + 80, x)
        return self.finish(out, p, {'members': [m['name'] for m in mem]})


def roll_warp(flat, R, tmax):
    """Orthographic horizontal cylinder: output row y (from roll centre) shows flat arc
    s = R*asin(y/R). Returns warped uint8 and the declared multiplicative shade per row."""
    import cv2
    L, A = flat.shape[1], flat.shape[0]
    hv = int(round(2 * R * np.sin(tmax)))
    yy = (np.arange(hv) + 0.5 - hv / 2) / R
    th = np.arcsin(np.clip(yy, -1, 1))
    sflat = (th + tmax) * R - 0.5                      # flat row coordinate
    mapy = np.repeat(sflat[:, None], L, 1).astype(np.float32)
    mapx = np.repeat(np.arange(L, dtype=np.float32)[None, :], hv, 0)
    warped = cv2.remap(flat, mapx, mapy, cv2.INTER_LANCZOS4, borderMode=cv2.BORDER_REFLECT)
    shade = (0.5 + 0.5 * np.cos(th)).astype(np.float32)
    out = np.clip(warped.astype(np.float32) * shade[:, None, None] + 0.5, 0, 255).astype(np.uint8)
    return out, shade, th


def roll_unwarp(rendered, shade, th, R, tmax, A):
    """Inverse of roll_warp via the known warp: divide the declared shade, then map each
    flat row s back to output row y = R*sin(s/R - tmax)."""
    import cv2
    hv, L = rendered.shape[:2]
    lin = np.clip(rendered.astype(np.float32) / shade[:, None, None], 0, 255)
    s = np.arange(A) + 0.5
    y = R * np.sin(s / R - tmax) + hv / 2 - 0.5
    mapy = np.repeat(y[:, None], L, 1).astype(np.float32)
    mapx = np.repeat(np.arange(L, dtype=np.float32)[None, :], A, 0)
    return np.clip(cv2.remap(lin, mapx, mapy, cv2.INTER_LANCZOS4, borderMode=cv2.BORDER_REFLECT) + 0.5, 0, 255).astype(np.uint8)


class Roll(Family):
    """4: deterministic cylinder roll of the real photo + flat unrolled sheet, plain studio bg.
    The roll body is warped (not gate-rebuildable) -> declared in unchecked_regions and
    verified by an inverse-warp check against the source crop with the gate's own metrics
    and thresholds; the flat sheet is a normal gated region."""
    id, name = 4, 'roll'
    R, TMAX, VERIFY = 210, np.radians(70), np.radians(45)

    def render(self, ctx, out):
        import cv2
        sys.path.insert(0, os.path.join(os.path.dirname(HERE), 'intel'))
        import fabric_gate as fg
        p = ctx
        self.begin((238, 236, 232))
        self.canvas.paste(gradient(W, H, (246, 245, 242), (222, 219, 214)), (0, 0))
        img = self.src(p['image'])
        L, x0, y0 = 880, 100, 300
        k = L / img.width
        A = int(round(2 * self.R * self.TMAX))
        sh_h = 1170 - y0 - int(round(2 * self.R * np.sin(self.TMAX)))
        a_src = round(A / k)
        roll_box = (0, 0, img.width, a_src)
        flat = np.asarray(cover(crop_box(img, roll_box), L, A))
        warped, shade, th = roll_warp(flat, self.R, self.TMAX)
        hv = warped.shape[0]
        # studio contact shadow + cardboard core ends (non-fabric)
        shadow(self.canvas, (x0, y0 + 20, x0 + L, y0 + hv + sh_h), radius=26, offset=(0, 18), opacity=60)
        for cx in (x0 - 4, x0 + L + 4):
            self.d.ellipse((cx - 26, y0 + hv / 2 - 46, cx + 26, y0 + hv / 2 + 46), fill=(196, 170, 128), outline=(160, 136, 100), width=3)
            self.d.ellipse((cx - 12, y0 + hv / 2 - 22, cx + 12, y0 + hv / 2 + 22), fill=(120, 100, 76))
        self.canvas.paste(Image.fromarray(warped), (x0, y0))
        # flat sheet hanging from the front of the roll: next rows of the same photo
        ins = 50
        sheet_src = (round(ins / k), a_src, round((L - 2 * ins) / k), round(sh_h / k))
        self.fabric(p['image'], sheet_src, (x0 + ins, y0 + hv, L - 2 * ins, sh_h))
        # inverse-warp verification on the declared band |theta| <= VERIFY
        back = roll_unwarp(np.asarray(self.canvas)[y0:y0 + hv, x0:x0 + L], shade, th, self.R, self.TMAX, A)
        b0, b1 = int(round((self.TMAX - self.VERIFY) * self.R)), int(round((self.TMAX + self.VERIFY) * self.R))
        exp = cv2.cvtColor(flat[b0:b1], cv2.COLOR_RGB2BGR)
        got = cv2.cvtColor(back[b0:b1], cv2.COLOR_RGB2BGR)
        cfg = {**fg.DEFAULTS, **fg.MASTER_DEFAULTS}
        m = fg.measure_region(exp, got, np.ones(exp.shape[:2], bool), cfg, cfg['blur_sigma'])
        m['pass'] = not m['fails']
        m['band_deg'] = round(float(np.degrees(self.VERIFY)), 1)
        base = os.path.splitext(out)[0]
        sbs = Image.new('RGB', (L * 2 + 20, b1 - b0), (255, 255, 255))
        sbs.paste(Image.fromarray(flat[b0:b1]), (0, 0))
        sbs.paste(Image.fromarray(back[b0:b1]), (L + 20, 0))
        sbs.save(base + '.roll-inverse.sbs.png')
        self.unchecked.append({'kind': 'roll_cylinder', 'src': p['image'], 'src_box': list(roll_box),
                               'out_box': [x0, y0, L, hv], 'warp': {'type': 'cylinder_orthographic', 'R': self.R,
                               'theta_max_deg': 70, 'flat_size': [L, A]},
                               'shade': '0.5 + 0.5*cos(theta), multiplicative (declared)',
                               'inverse_warp': m, 'sbs': os.path.basename(base + '.roll-inverse.sbs.png')})
        # text + tag (outside fabric)
        self.text((60, 64), p['name'], 64, INK, 'serif')
        self.text((60, 150), 'On the roll · real photo, cylinder view', 30, MUTED, 'sans')
        tx, ty = W - 330, 196
        self.d.rounded_rectangle((tx, ty, tx + 270, ty + 92), 12, fill=(255, 255, 255), outline=GOLD, width=3)
        self.text((tx + 135, ty + 10), p['name'], 34, INK, 'serif', 'c')
        self.text((tx + 135, ty + 52), f"¥{int(p['price']):,}", 30, MUTED, 'serif', 'c')
        self.text((x0, 1190), 'approx. scale', 26, MUTED, 'sans')
        self.material_size(p, 1230, x0, 28)
        return self.finish(out, p, {'inverse_warp': m, 'min_exact_share': 0.15})


class Hero(Family):
    """6: 4:5 hero collage = close-up crops (family 3) + colour stack bands (family 5)."""
    id, name = 6, 'hero'

    def render(self, ctx, out):
        p = ctx
        self.begin(CREAM)
        img = self.src(p['image'])
        boxes = detail_boxes(img, 1, int(img.width * 0.62))           # hero ~1:1 photo scale
        boxes += detail_boxes(img, 2, min(img.width, img.height) // 4)
        self.text((60, 56), p['name'], 64, INK, 'serif')
        self.text((W - 60, 66), f"¥{int(p['price']):,}", 52, INK, 'serif', 'r')
        self.d.line((60, 160, W - 60, 160), fill=GOLD, width=3)
        self.fabric(p['image'], boxes[0], (60, 186, 960, 560))
        self.fabric(p['image'], boxes[1], (60, 766, 460, 225))
        self.fabric(p['image'], boxes[2], (60, 1011, 460, 225))
        mem = sorted(p['series_members'], key=lambda m: m['product_id'] != p['product_id'])
        n, gap, y0, tot = len(mem), 10, 766, 470
        bh = (tot - gap * (n - 1)) // n
        for i, m in enumerate(mem):
            mi = self.src(m['image'])
            sh = round(mi.width * bh / 380)
            y = y0 + i * (bh + gap)
            self.fabric(m['image'], (0, (mi.height - sh) // 2, mi.width, sh), (560, y, 380, bh))
            self.text((952, y + bh // 2 - 16), m['name'][-1], 32, INK, 'serif')
        self.text((560, 1246), f"{n} colours in stock · approx. scale", 26, MUTED, 'sans')
        self.material_size(p, 1250, 60, 26)
        return self.finish(out, p, {'crop_boxes': boxes, 'members': [m['name'] for m in mem]})


class NeedsModel(Family):
    enabled = False

    def render(self, ctx, out):
        raise NotImplementedError('needs model images')


class Worn(NeedsModel):
    id, name = 1, 'worn'


class Garment(NeedsModel):
    id, name = 2, 'garment'


REGISTRY = {f.id: f for f in (Worn, Garment, Closeup, Stack, Roll, Hero)}
ORDER = [1, 2, 3, 4, 5, 6]


def enabled_ids():
    return {i for i, f in REGISTRY.items() if f.enabled}


def get(fid):
    return REGISTRY[int(fid)]()
