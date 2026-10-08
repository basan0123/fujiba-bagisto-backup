#!/usr/bin/env python3
"""
Quality gate (numeric part). For every fabric region the renderer declared as
"unaltered", rebuild the expected pixels from the SOURCE photo and compare:
  mean ΔE (CIE76, LAB, after σ1.5 blur) ≤ 3.0 — no colour shift
  pHash distance        ≤ 8/64  — same motif layout
  SSIM (luma)           ≥ 0.80  — same detail
Plus: min text size ≥ 26 px (mobile legibility at 1080 wide) and fabric coverage.
Video: regions carry a time `t`; the frame is pulled with ffmpeg.
Prints JSON {passed, checks[]}. Exit code 0 = pass, 2 = fail.

The vision part (motif unchanged, no fake kanji, faces/hands) needs a vision
model — reported as "manual" until ANTHROPIC_API_KEY is configured.
"""
import json
import subprocess
import sys
import tempfile

import numpy as np
from PIL import Image

from common import crop_box, cover, load

DE_MAX, PH_MAX, SSIM_MIN, TEXT_MIN = 3.0, 8, 0.80, 26


def lab(img):
    a = np.asarray(img.convert('RGB'), dtype=np.float64) / 255
    a = np.where(a > 0.04045, ((a + 0.055) / 1.055) ** 2.4, a / 12.92)
    xyz = a @ np.array([[0.4124, 0.2126, 0.0193], [0.3576, 0.7152, 0.1192], [0.1805, 0.0722, 0.9505]])
    xyz /= np.array([0.95047, 1.0, 1.08883])
    f = np.where(xyz > 0.008856, np.cbrt(xyz), 7.787 * xyz + 16 / 116)
    return np.stack([116 * f[..., 1] - 16, 500 * (f[..., 0] - f[..., 1]), 200 * (f[..., 1] - f[..., 2])], -1)


def phash(img):
    g = np.asarray(img.convert('L').resize((32, 32), Image.LANCZOS), dtype=np.float64)
    n = 32
    c = np.cos(np.pi * (2 * np.arange(n)[None, :] + 1) * np.arange(n)[:, None] / (2 * n))
    d = (c @ g @ c.T)[:8, :8].flatten()
    return d[1:] > np.median(d[1:])


def ssim(a, b):
    x = np.asarray(a.convert('L'), dtype=np.float64)
    y = np.asarray(b.convert('L'), dtype=np.float64)
    c1, c2 = (0.01 * 255) ** 2, (0.03 * 255) ** 2
    mx, my = x.mean(), y.mean()
    vx, vy, cov = x.var(), y.var(), ((x - mx) * (y - my)).mean()
    return ((2 * mx * my + c1) * (2 * cov + c2)) / ((mx ** 2 + my ** 2 + c1) * (vx + vy + c2))


def frame(video, t, n=None):
    tmp = tempfile.mktemp(suffix='.png')
    if n is not None:     # exact frame number — -ss can land one frame off, which differs during a zoom
        subprocess.run(['ffmpeg', '-v', 'error', '-y', '-i', video, '-vf', f'select=eq(n\\,{n})', '-vsync', '0', '-frames:v', '1', tmp], check=True)
    else:
        subprocess.run(['ffmpeg', '-v', 'error', '-y', '-ss', f'{t:.3f}', '-i', video, '-frames:v', '1', tmp], check=True)
    return Image.open(tmp).convert('RGB')


def main():
    asset = sys.argv[1]
    meta = json.load(open(asset + '.json'))
    checks, cache, frames = [], {}, {}
    W, H = meta['size']
    covered = 0
    for i, r in enumerate(meta['fabric_regions']):
        if 't' in r:
            key = r.get('n', r['t'])
            out = frames.get(key) or frame(asset, r['t'], r.get('n'))
            frames[key] = out
        else:
            out = cache.setdefault('img', None) or Image.open(asset).convert('RGB')
            cache['img'] = out
        src = load(r['src'])
        x, y, w, h = r['out_box']
        got = out.crop((x, y, x + w, y + h))
        exp = cover(crop_box(src, r['src_box']), w, h)
        excluded = 0.0
        if r.get('mask') == 'circle':             # only the disc is fabric; corners show the background
            from PIL import ImageDraw
            m = Image.new('L', (w, h), 255)
            ImageDraw.Draw(m).ellipse((2, 2, w - 3, h - 3), fill=0)
            got = Image.composite(exp, got, m)
        if r.get('exclude'):                      # declared overlays (callout ring, leader line…) — not fabric
            m = Image.new('L', (w, h), 0)
            from PIL import ImageDraw
            dm = ImageDraw.Draw(m)
            for ex, ey, ew, eh in r['exclude']:
                dm.rectangle((ex - x, ey - y, ex - x + ew, ey - y + eh), fill=255)
            got = Image.composite(exp, got, m)
            excluded = sum(1 for v in m.getdata() if v) / (w * h)
        # S-CIELAB-style: compare colour after a small spatial blur, so JPEG noise on fine
        # texture isn't counted as colour shift, while a real grade/hue shift still is.
        from PIL import ImageFilter
        blur = ImageFilter.GaussianBlur(1.5)
        de = float(np.sqrt(((lab(got.filter(blur)) - lab(exp.filter(blur))) ** 2).sum(-1)).mean())
        ph = int((phash(got) != phash(exp)).sum())
        ss = float(ssim(got, exp))
        ok = de <= DE_MAX and ph <= PH_MAX and ss >= SSIM_MIN
        covered += w * h * (1 - excluded) if 't' not in r else 0   # only unobstructed fabric counts
        checks.append({'check': f'fabric[{i}]' + (f"@{r['t']}s" if 't' in r else ''), 'deltaE': round(de, 2),
                       'phash': ph, 'ssim': round(ss, 3), **({'excluded': round(excluded, 2)} if excluded else {}), 'pass': ok and excluded <= 0.5})
    if meta.get('min_text_px') is not None:
        checks.append({'check': 'min_text_px', 'value': meta['min_text_px'], 'pass': meta['min_text_px'] >= TEXT_MIN})
    if not any('t' in r for r in meta['fabric_regions']):
        share = covered / (W * H)
        need = meta.get('min_exact_share', 0.15)
        checks.append({'check': 'exact_fabric_share', 'value': round(share, 2), 'min': need, 'pass': share >= need})
    checks.append({'check': 'vision (motif/kanji/faces)', 'value': 'manual — no vision API key', 'pass': None})
    passed = all(c['pass'] is not False for c in checks) and bool(meta['fabric_regions'])
    print(json.dumps({'passed': passed, 'checks': checks}, ensure_ascii=False))
    sys.exit(0 if passed else 2)


if __name__ == '__main__':
    main()
