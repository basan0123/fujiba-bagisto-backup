#!/usr/bin/env python3
"""
Measured (grade-A) facts for one close-up crop of a product photo.

    measure.py <image> <x> <y> <w> <h>

Prints JSON: sha256 of the file, crop size, sharpness (variance of a Laplacian),
and a 6-colour palette (k-means on the crop) with hex + share. Pure Pillow+numpy.
"""
import hashlib
import json
import sys

import numpy as np
from PIL import Image, ImageOps


def palette(px, k=6, iters=12):
    px = px.reshape(-1, 3).astype(np.float32)
    lum = px @ np.array([0.299, 0.587, 0.114], dtype=np.float32)
    order = np.argsort(lum)
    cent = px[order[((np.arange(k) + 0.5) / k * len(px)).astype(int)]]
    for _ in range(iters):
        d = ((px[:, None, :] - cent[None, :, :]) ** 2).sum(-1)
        lab = d.argmin(1)
        for j in range(k):
            m = lab == j
            if m.any():
                cent[j] = px[m].mean(0)
    share = np.bincount(lab, minlength=k) / len(px)
    out = [{'hex': '#%02X%02X%02X' % tuple(int(round(v)) for v in c), 'share': round(float(s), 3)}
           for c, s in zip(cent, share) if s >= 0.03]
    return sorted(out, key=lambda c: -c['share'])


def main():
    path, x, y, w, h = sys.argv[1], *map(int, sys.argv[2:6])
    raw = open(path, 'rb').read()
    img = ImageOps.exif_transpose(Image.open(path)).convert('RGB')
    crop = img.crop((x, y, x + w, y + h))
    small = np.asarray(crop.resize((120, int(120 * h / w))))
    g = np.asarray(crop.convert('L'), dtype=np.float32)
    lap = g[1:-1, 1:-1] * 4 - g[:-2, 1:-1] - g[2:, 1:-1] - g[1:-1, :-2] - g[1:-1, 2:]
    print(json.dumps({
        'sha256': hashlib.sha256(raw).hexdigest(),
        'size': img.size, 'box': [x, y, w, h],
        'sharpness': round(float(lap.var()), 1),
        'palette': palette(small),
    }))


if __name__ == '__main__':
    import os as _o, sys as _s; _s.path.insert(0, _o.path.join(_o.path.dirname(_o.path.abspath(__file__)), '..', 'intel')); __import__('heavy_guard').acquire('measure')  # one heavy job at a time + memory cap
    main()
