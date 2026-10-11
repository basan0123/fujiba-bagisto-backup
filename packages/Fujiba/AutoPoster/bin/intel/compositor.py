#!/usr/bin/env python3
"""
Fabric compositor — Fujiba Intelligence Agent, Phase 1.

This is the component that ENFORCES fabric safety. A generation provider may
ignore any instruction it is given, so provider output is treated as untrusted:
inside the fabric mask, every provider pixel is DISCARDED and replaced with
pixels taken from the original source photograph.

    AI person/background/scene  →  compositor  →  REAL fabric re-inserted
                                                →  fabric integrity gate

    compositor.py --generated g.png --source orig.jpg --mask m.png --out o.png
                  [--shadow s.png] [--shadow-strength 0.35]

Allowed on fabric: crop, scale, rotate, perspective warp, mask, composite and a
physically plausible shadow applied as a SEPARATE MULTIPLICATIVE layer (so the
gate can exclude it from the colour comparison). Never recolour, redraw,
inpaint, or hallucinate-upscale.

Prints JSON {out, replaced_pixels, mask_coverage}. Exit 0 ok, 3 on IO error.
"""
import argparse
import json
import sys

import cv2
import numpy as np


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--generated', required=True, help='untrusted provider output')
    ap.add_argument('--source', required=True, help='original fabric photo (truth)')
    ap.add_argument('--mask', required=True, help='white = where fabric belongs')
    ap.add_argument('--out', required=True)
    ap.add_argument('--shadow', help='optional greyscale shadow layer')
    ap.add_argument('--shadow-strength', type=float, default=0.35)
    args = ap.parse_args()

    gen = cv2.imread(args.generated, cv2.IMREAD_COLOR)
    src = cv2.imread(args.source, cv2.IMREAD_COLOR)
    msk = cv2.imread(args.mask, cv2.IMREAD_GRAYSCALE)

    if gen is None or src is None or msk is None:
        print(json.dumps({'error': 'could not read generated, source or mask'}))
        return 3

    h, w = gen.shape[:2]
    if src.shape[:2] != (h, w):
        src = cv2.resize(src, (w, h), interpolation=cv2.INTER_AREA)
    if msk.shape[:2] != (h, w):
        msk = cv2.resize(msk, (w, h), interpolation=cv2.INTER_NEAREST)

    mask = msk > 127

    # The whole point: provider pixels inside the mask never survive.
    out = gen.copy()
    out[mask] = src[mask]

    # Shadow as a separate multiplicative layer, so the gate can exclude it
    # from ΔE rather than mistake lighting for a colour shift.
    if args.shadow:
        sh = cv2.imread(args.shadow, cv2.IMREAD_GRAYSCALE)
        if sh is not None:
            if sh.shape[:2] != (h, w):
                sh = cv2.resize(sh, (w, h), interpolation=cv2.INTER_AREA)
            factor = 1.0 - (sh.astype(np.float32) / 255.0) * float(args.shadow_strength)
            shaded = (out.astype(np.float32) * factor[..., None]).clip(0, 255).astype(np.uint8)
            out[mask] = shaded[mask]

    if not cv2.imwrite(args.out, out):
        print(json.dumps({'error': f'could not write {args.out}'}))
        return 3

    print(json.dumps({
        'out': args.out,
        'replaced_pixels': int(mask.sum()),
        'mask_coverage': round(float(mask.sum()) / float(h * w), 4),
    }))
    return 0


if __name__ == '__main__':
    import os as _o, sys as _s; _s.path.insert(0, _o.path.dirname(_o.path.abspath(__file__))); __import__('heavy_guard').acquire('compositor')  # one heavy job at a time + memory cap
    sys.exit(main())
