"""Family 8 'collection' (PREVIEW ONLY, FREE: no paid API, no AI pixels).

A series poster on a procedural washi-like paper drawn by this code:
  * title "<series> Collection" + the catalog Pattern line (factual, never invented);
  * one folded piece per in-stock colour: a flat front face (gated region, exact rebuild)
    + a perspective top flap (warped -> unchecked_regions, verified by an inverse warp with
    fabric_gate's own metrics/thresholds, like the roll in families 4/7);
  * circle zoom-ins cropped from real product pixels (declared `mask: circle`, gated);
  * every crop lies inside a watermark-free rectangle from wm_detect.wm_free_crop;
  * text only from ctx: our public code + own price (product_flat), colour names ONLY from
    the catalog "Color: X (Name)" line (JA only if the JA catalog line carries a Japanese
    name; never machine-translated), material icon only for "Hosonuno (100% cotton)";
  * brand mark "Fujiba Elegance Emporium" in a friendly OFL font, outside fabric.
No "Made in Japan", no length/size claims ("size not confirmed"), "approx. scale".
Layout variant via env V3_LAYOUT = A | B | C (original arrangements).
"""
import os
import re
import sys
import zlib

import numpy as np
from PIL import Image, ImageDraw, ImageFilter, ImageFont

import families as F

HERE = os.path.dirname(os.path.abspath(__file__))
BIN = os.path.dirname(HERE)
sys.path.insert(0, os.path.join(BIN, 'v2'))
sys.path.insert(0, os.path.join(BIN, 'intel'))
from common import GOLD, INK, cover, crop_box, shadow  # noqa: E402

FONTS = os.path.join(BIN, 'v2', 'fonts')
BRAND_FONTS = [  # owner picks; the first is used on posters
    ('Fredoka SemiBold', os.path.join(FONTS, 'fredoka-latin-600-normal.woff')),
    ('Mali SemiBold', os.path.join(FONTS, 'mali-latin-600-normal.woff')),
    ('Quicksand Bold', os.path.join(FONTS, 'quicksand-latin-700-normal.woff')),
]
BRAND = 'Fujiba Elegance Emporium'
BRAND_RGB = (178, 92, 104)          # soft rose ink, flat (no glow)
PAPER = (244, 238, 226)
MUTED = F.MUTED
HOSONUNO = 'Hosonuno (100% cotton)'
JA_RE = re.compile('[぀-ヿ一-鿿]')


# ---- catalog parsing (pure, unit-tested) --------------------------------
_STOP = r'(?=Product code:|Color:|Colour:|Pattern:|Fabric:|Width:|Length:|$)'


def catalog_fields(desc_en, desc_ja=''):
    """Parse our catalog description. Returns dict with colour_en / colour_ja / pattern / fabric.
    colour_ja is set only when the JA description's Color line itself holds Japanese text."""
    def grab(key, txt):
        m = re.search(key + r'\s*(.*?)' + _STOP, txt or '', re.S)
        return m.group(1).strip() if m else None

    def colour(txt):
        c = grab(r'Colou?r:', txt)
        if not c:
            return None
        m = re.search(r'\(([^)]+)\)', c)
        return (m.group(1) if m else c).strip()

    en = colour(desc_en)
    ja = colour(desc_ja)
    return {'colour_en': en if en and not JA_RE.search(en) else None,
            'colour_ja': ja if ja and JA_RE.search(ja) else None,
            'pattern': grab('Pattern:', desc_en), 'fabric': grab('Fabric:', desc_en)}


# ---- procedural washi ---------------------------------------------------
def washi(w, h, seed, base=PAPER):
    """Plain washi-like paper: soft low-frequency mottling + long thin fibres + fine grain."""
    rng = np.random.default_rng(seed)
    a = np.empty((h, w, 3), np.float32)
    a[:] = base
    low = rng.normal(0, 1, (h // 60 + 2, w // 60 + 2)).astype(np.float32)
    low = np.asarray(Image.fromarray(low).resize((w, h), Image.BICUBIC))
    a += low[..., None] * 3.0
    a += rng.normal(0, 1.6, (h, w, 1)).astype(np.float32)
    img = Image.fromarray(np.clip(a, 0, 255).astype(np.uint8))
    fib = Image.new('L', (w, h), 0)
    d = ImageDraw.Draw(fib)
    for _ in range(int(w * h / 2600)):
        x, y = rng.uniform(0, w), rng.uniform(0, h)
        ang, L = rng.uniform(0, np.pi), rng.uniform(20, 90)
        pts = []
        for t in np.linspace(0, 1, 6):
            ang += rng.normal(0, 0.25)
            pts.append((x, y))
            x, y = x + np.cos(ang) * L / 5, y + np.sin(ang) * L / 5
        d.line(pts, fill=int(rng.uniform(40, 110)), width=1)
    fib = fib.filter(ImageFilter.GaussianBlur(0.6))
    img.paste(Image.new('RGB', (w, h), (255, 253, 247)), (0, 0), fib)
    return img


def brand_font(size, idx=0):
    return ImageFont.truetype(BRAND_FONTS[idx][1], size)


def fonts_sample(out):
    """Strip with the brand mark in each candidate font, for the owner's choice."""
    W, rowh = 1080, 170
    img = washi(W, rowh * len(BRAND_FONTS) + 40, 7)
    d = ImageDraw.Draw(img)
    for i, (name, _) in enumerate(BRAND_FONTS):
        y = 20 + i * rowh
        d.text((60, y + 18), BRAND, font=brand_font(64, i), fill=BRAND_RGB)
        d.text((60, y + 108), f'{i + 1}. {name} (OFL)' + ('  — used on posters' if i == 0 else ''),
               font=ImageFont.truetype('/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf', 24), fill=MUTED)
        if i:
            d.line((60, y, W - 60, y), fill=(220, 210, 192), width=2)
    img.save(out)
    return out


# ---- the family ---------------------------------------------------------
class Collection(F.Family):
    """8: series collection poster (folded pieces + circle zoom-ins + catalog colour names)."""
    id, name = 8, 'collection'
    FLAP_INSET = 0.07      # top edge of the flap is 7% narrower each side (receding)

    def __init__(self, layout=None):
        self.variant = (layout or os.environ.get('V3_LAYOUT') or 'A').upper()

    # -- watermark-free crops --
    def safe_rect(self, path, aspect):
        import cv2
        import wm_detect
        key = (path, round(aspect, 3))
        cache = self.__dict__.setdefault('_safe', {})
        if key not in cache:
            bgr = cv2.cvtColor(np.asarray(self.src(path)), cv2.COLOR_RGB2BGR)
            wm = self.__dict__.setdefault('_wm', {}).get(path) or wm_detect.detect(bgr)
            self._wm[path] = wm
            rect, _, recheck = wm_detect.wm_free_crop(bgr, aspect, wm=wm)
            if not rect:
                raise RuntimeError(f'no watermark-free crop (aspect {aspect:.2f}) in {path}')
            cache[key] = rect
            self.crops.append({'src': path, 'aspect': round(aspect, 3), 'rect': rect,
                               'watermark_in_source': wm['watermark_present'], 'ocr_recheck': recheck})
        return cache[key]

    def circle_box(self, path, n_px_hint):
        """Most detailed square inside the largest watermark-free square of the photo."""
        sx, sy, sw, sh = self.safe_rect(path, 1.0)
        sub = crop_box(self.src(path), (sx, sy, sw, sh))
        size = max(64, min(sw, sh) // n_px_hint)
        bx, by, bw, bh = F.detail_boxes(sub, 1, size)[0]
        return (sx + bx, sy + by, bw, bh)

    def circle(self, path, box, x, y, d):
        """Gated circle: cover(crop) pasted through a circle mask; gate checks the inset ellipse."""
        tile = cover(crop_box(self.src(path), box), d, d)
        m = Image.new('L', (d, d), 0)
        ImageDraw.Draw(m).ellipse((0, 0, d - 1, d - 1), fill=255)
        shadow(self.canvas, (x, y, x + d, y + d), radius=14, offset=(0, 8), opacity=50)
        self.canvas.paste(tile, (x, y), m)
        self.d.ellipse((x - 5, y - 5, x + d + 4, y + d + 4), outline=(255, 252, 246), width=4)
        self.regions.append({'src': path, 'src_box': list(map(int, box)), 'out_box': [x, y, d, d], 'mask': 'circle'})

    def folded(self, path, x, y, w, flap_h, face_h, mat=0, exclude_later=None):
        """Folded piece = perspective top flap (unchecked + inverse-warp verified) + 4px fold
        gap (paper, non-fabric) + flat face (gated). Returns its bbox."""
        import cv2
        flap_flat = int(round(flap_h * 1.8))            # foreshortened: flat flap is taller
        cx, cy, cw, ch = self.safe_rect(path, w / (flap_flat + face_h))
        fh_src = round(ch * flap_flat / (flap_flat + face_h))
        flap_src = (cx, cy, cw, fh_src)
        face_src = (cx, cy + fh_src, cw, ch - fh_src)
        gap = 4
        bbox = (x - mat, y - mat, x + w + mat, y + flap_h + gap + face_h + mat)
        if mat:
            self.d.rectangle(bbox, fill=(253, 250, 243))
        self.d.rectangle((x, y + flap_h, x + w - 1, y + flap_h + gap - 1), fill=(214, 204, 186))
        # flat face (exact rebuild by the gate)
        self.fabric(path, face_src, (x, y + flap_h + gap, w, face_h))
        # flap: perspective warp of the real flat flap pixels
        flat = np.asarray(cover(crop_box(self.src(path), flap_src), w, flap_flat))
        ins = w * self.FLAP_INSET
        sq = np.float32([[0, 0], [w - 1, 0], [w - 1, flap_flat - 1], [0, flap_flat - 1]])
        dq = np.float32([[x + ins, y], [x + w - 1 - ins, y], [x + w - 1, y + flap_h - 1], [x, y + flap_h - 1]])
        M = cv2.getPerspectiveTransform(sq, dq)
        Wc, Hc = self.canvas.size
        warped = cv2.warpPerspective(flat, M, (Wc, Hc), flags=cv2.INTER_LANCZOS4, borderMode=cv2.BORDER_REPLICATE)
        qm = np.zeros((Hc, Wc), np.uint8)
        cv2.fillConvexPoly(qm, np.round(dq).astype(np.int32), 255)
        arr = np.asarray(self.canvas).copy()
        arr[qm > 0] = warped[qm > 0]
        self.canvas.paste(Image.fromarray(arr), (0, 0))
        self.d = ImageDraw.Draw(self.canvas)
        self._pending_flaps.append({'path': path, 'flat': flat, 'M': M, 'src_box': list(map(int, flap_src)),
                                    'quad': dq.round(1).tolist(), 'bbox': bbox})
        return bbox

    def verify_flaps(self, base):
        """Inverse-warp each flap from the FINAL canvas and compare with its flat source."""
        import cv2
        import fabric_gate as fg
        cfg = {**fg.DEFAULTS, **fg.MASTER_DEFAULTS}
        res = []
        canvas = np.asarray(self.canvas)
        for i, f in enumerate(self._pending_flaps):
            flat = f['flat']
            h, w = flat.shape[:2]
            back = cv2.warpPerspective(canvas, np.linalg.inv(f['M']), (w, h), flags=cv2.INTER_LANCZOS4,
                                       borderMode=cv2.BORDER_REPLICATE)
            m = np.zeros((h, w), bool)
            m[6:h - 6, 6:w - 6] = True                   # skip the hard quad edge
            exp, got = cv2.cvtColor(flat, cv2.COLOR_RGB2BGR), cv2.cvtColor(back, cv2.COLOR_RGB2BGR)
            r = fg.measure_region(exp, got, m, cfg, cfg['blur_sigma'])
            r['pass'] = not r['fails']
            sp = f'{base}.flap{i}-inverse.sbs.png'
            sb = Image.new('RGB', (w * 2 + 20, h), (255, 255, 255))
            sb.paste(Image.fromarray(flat), (0, 0))
            sb.paste(Image.fromarray(back), (w + 20, 0))
            sb.save(sp)
            self.unchecked.append({'kind': 'fold_flap_perspective', 'src': f['path'], 'src_box': f['src_box'],
                                   'quad': f['quad'], 'flat_size': [w, h], 'inverse_warp': r,
                                   'sbs': os.path.basename(sp)})
            res.append(r)
        worst = lambda k, fn: fn(r[k] for r in res) if res else None  # noqa: E731
        return {'pass': all(r['pass'] for r in res), 'flaps': len(res),
                'worst_deltaE00_mean': worst('deltaE00_mean', max), 'min_ssim': worst('ssim', min),
                'max_edge_diff': worst('edge_diff', max)}

    # -- text helpers --
    def brand(self, xy, size=44, anchor='l'):
        f = brand_font(size)
        x, y = xy
        if anchor == 'r':
            x -= f.getlength(BRAND)
        elif anchor == 'c':
            x -= f.getlength(BRAND) / 2
        self.d.text((x, y), BRAND, font=f, fill=BRAND_RGB)
        self.texts.append({'text': BRAND, 'size': size, 'font': BRAND_FONTS[0][0]})

    def cotton_icon(self, x, y, r=13):
        for dx, dy in ((0, -r * 0.8), (-r * 0.8, r * 0.2), (r * 0.8, r * 0.2), (0, r * 0.5)):
            self.d.ellipse((x + dx - r, y + dy - r, x + dx + r, y + dy + r), fill=(255, 255, 255), outline=(170, 160, 140), width=2)
        self.d.line((x, y + r * 1.4, x, y + r * 2.6), fill=(120, 140, 90), width=3)

    def footer(self, p, info, y=1206):
        """Material (catalog Fabric only) left + scale/size note right; brand mark on its own line."""
        if info['fabric'] == HOSONUNO:
            self.cotton_icon(74, y + 12)
            self.text((104, y), '100% cotton / 綿100%', 28, INK, 'ja')
        self.text((F.W - 60, y + 4), 'approx. scale · size not confirmed', 26, MUTED, 'sans', 'r')
        self.d.line((60, y + 54, F.W - 60, y + 54), fill=(226, 214, 192), width=2)
        self.brand((F.W // 2, y + 70), 48, 'c')

    def chip(self, x, y, m, w=None, center=False):
        """Colour-name chip: our code + catalog colour name(s) + own price."""
        names = m['info']['colour_en'] or ''
        if m['info']['colour_ja']:
            names += f" / {m['info']['colour_ja']}"
        lines = [(m['name'], 34, INK, 'serif'), (names, 30, MUTED, 'ja' if m['info']['colour_ja'] else 'sans'),
                 (f"¥{int(m['price']):,}", 32, INK, 'serif')]
        lines = [ln for ln in lines if ln[0]]
        cw = w or 260
        ch = 22 + sum(s + 14 for _, s, _, _ in lines)
        self.d.rounded_rectangle((x, y, x + cw, y + ch), 18, fill=(255, 252, 246), outline=(222, 206, 176), width=2)
        ty = y + 14
        for t, s, c, k in lines:
            if center:
                self.text((x + cw // 2, ty), t, s, c, k, 'c')
            else:
                self.text((x + 22, ty), t, s, c, k)
            ty += s + 14
        return ch

    # -- render --
    def render(self, ctx, out):
        p = ctx
        mem = [dict(m, info=catalog_fields(m.get('description_en', ''), m.get('description_ja', '')))
               for m in p['series_members']]
        info = catalog_fields(p.get('description_en', ''), p.get('description_ja', ''))
        self.begin(PAPER)
        self.crops, self._pending_flaps = [], []
        self.canvas.paste(washi(F.W, F.H, zlib.crc32(p['series'].encode())), (0, 0))
        self.d = ImageDraw.Draw(self.canvas)
        # header (title is ours; subtitle is the catalog Pattern line verbatim)
        self.text((60, 50), f"{p['series']} Collection", 68, INK, 'serif')
        sub = f"{info['pattern']} · {len(mem)} colours in stock" if info['pattern'] else f'{len(mem)} colours in stock'
        self.text((60, 140), sub, 30, MUTED, 'sans')
        self.d.line((60, 192, F.W - 60, 192), fill=GOLD, width=3)
        getattr(self, 'layout_' + self.variant)(p, mem)
        self.footer(p, info)
        base = os.path.splitext(out)[0]
        inv = self.verify_flaps(base)
        return self.finish(out, p, {'variant': self.variant, 'members': [m['name'] for m in mem],
                                    'catalog': {m['name']: m['info'] for m in mem}, 'crops': self.crops,
                                    'inverse_warp': inv, 'min_exact_share': 0.15})

    def layout_A(self, p, mem):
        """Folded pieces in a row, label chips under each, circle zoom-ins in a row below."""
        n = len(mem)
        gap = 30
        w = (F.W - 120 - gap * (n - 1)) // n
        y = 236
        for i, m in enumerate(mem):
            x = 60 + i * (w + gap)
            shadow(self.canvas, (x, y, x + w, y + 64 + 380), radius=16, offset=(0, 10), opacity=55)
            self.folded(m['image'], x, y, w, 60, 380)
            self.chip(x, y + 460, m, w, center=True)
        d = min(230, w - 40)
        for i, m in enumerate(mem):
            x = 60 + i * (w + gap) + (w - d) // 2
            self.circle(m['image'], self.circle_box(m['image'], 5), x, 870, d)
        self.text((60, 870 + d + 18), 'circles: close-up of the real fabric', 26, MUTED, 'sans')

    def layout_B(self, p, mem):
        """Cascading stack (back = top-left, front = bottom-right) + one big circle on the right."""
        w, flap, face, dx, dy = 380, 60, 400, 130, 210
        x0, y0 = 60, 236
        boxes = [(x0 + i * dx, y0 + i * dy) for i in range(len(mem))]
        for bx, by in boxes:
            shadow(self.canvas, (bx, by, bx + w, by + flap + 4 + face), radius=18, offset=(0, 12), opacity=55)
        start = len(self.regions)
        bbs = []
        for i, (m, (bx, by)) in enumerate(zip(mem, boxes)):
            bbs.append(self.folded(m['image'], bx, by, w, flap, face, mat=6 if i else 0))
        # back faces are partly covered by later pieces (mat + flap + face): declare overlays
        faces = self.regions[start:]
        for i, r in enumerate(faces):
            r['exclude'] = [[a, b, c - a + 1, e - b + 1] for (a, b, c, e) in bbs[i + 1:]]
        hero = next((m for m in mem if m['product_id'] == p['product_id']), mem[0])
        d = 300
        cx, cy = F.W - 60 - d, 236
        self.circle(hero['image'], self.circle_box(hero['image'], 4), cx, cy, d)
        self.text((cx + d // 2, cy + d + 14), f"{hero['name']} close-up", 26, MUTED, 'sans', 'c')
        y = cy + d + 60
        for m in mem:
            y += self.chip(cx - 20, y, m, d + 40) + 14

    def layout_C(self, p, mem):
        """Grid: one row per colour = folded piece | circle zoom-in | colour-name chip."""
        rowh, y = 312, 222
        for m in mem:
            x = 60
            shadow(self.canvas, (x, y, x + 330, y + 50 + 4 + 240), radius=14, offset=(0, 8), opacity=50)
            self.folded(m['image'], x, y, 330, 50, 240)
            d = 250
            self.circle(m['image'], self.circle_box(m['image'], 5), 430, y + 22, d)
            self.chip(720, y + 50, m, 300)
            y += rowh


families_registered = F.REGISTRY.setdefault(Collection.id, Collection)
if Collection.id not in F.ORDER:
    F.ORDER.append(Collection.id)
