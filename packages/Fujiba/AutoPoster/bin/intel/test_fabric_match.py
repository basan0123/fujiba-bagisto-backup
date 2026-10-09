#!/usr/bin/env python3
"""
O/P — fabric matching regression tests.

Uses real catalogue photos, not synthetic patterns, because the thing being
tested is whether real kimono prints can be told apart.

The cases that matter most are the negatives: two DIFFERENT fabrics must never
be reported as the same product, even when their colour palettes are forced to
agree. Colour alone deciding a match is the specific failure this guards.

Run:  python3 test_fabric_match.py [photo_dir]
Exit 0 = all passed.
"""
import json
import os
import subprocess
import sys
import tempfile

import cv2
import numpy as np

HERE = os.path.dirname(os.path.abspath(__file__))
MATCHER = os.path.join(HERE, 'fabric_match.py')
DEFAULT_DIR = ('/var/www/bagisto/public/fb-posts/auto/'
               '1bd1181f-fcd5-4238-a12d-a207d6ea4006/photos')

# Verdicts that assert "this is the same physical fabric".
SAME_PRODUCT = {'exact_match', 'probable_match'}


def crop_pct(img, pct):
    """Centre crop covering `pct` of the original AREA, aspect preserved."""
    h, w = img.shape[:2]
    s = pct ** 0.5
    ch, cw = int(h * s), int(w * s)
    return img[(h - ch) // 2:(h - ch) // 2 + ch, (w - cw) // 2:(w - cw) // 2 + cw]


def recolour_towards(target, img):
    """Force img's a*/b* means onto target's — a deliberate colour trap."""
    lt = cv2.cvtColor(target, cv2.COLOR_BGR2LAB).astype(np.float32)
    li = cv2.cvtColor(img, cv2.COLOR_BGR2LAB).astype(np.float32)
    li[..., 1] += lt[..., 1].mean() - li[..., 1].mean()
    li[..., 2] += lt[..., 2].mean() - li[..., 2].mean()
    return cv2.cvtColor(np.clip(li, 0, 255).astype(np.uint8), cv2.COLOR_LAB2BGR)


def match(ref, cand):
    p = subprocess.run([sys.executable, MATCHER, '--reference', ref, '--candidate', cand],
                       capture_output=True, text=True)
    return json.loads(p.stdout or '{}')


def main():
    photo_dir = sys.argv[1] if len(sys.argv) > 1 else DEFAULT_DIR
    if not os.path.isdir(photo_dir):
        print(f'SKIP: no photo dir at {photo_dir}')
        return 0

    files = sorted(f for f in os.listdir(photo_dir) if f.endswith('.jpg'))
    if len(files) < 3:
        print('SKIP: need at least 3 photos')
        return 0

    tmp = tempfile.mkdtemp(prefix='fabmatch')
    A = cv2.imread(os.path.join(photo_dir, files[0]))
    B = cv2.imread(os.path.join(photo_dir, files[1]))
    C = cv2.imread(os.path.join(photo_dir, files[2]))

    ref = os.path.join(tmp, 'ref.jpg')
    cv2.imwrite(ref, A)

    results = []

    def case(name, img, expect_same, note=''):
        path = os.path.join(tmp, f'{len(results)}.jpg')
        cv2.imwrite(path, img)
        d = match(ref, path)
        verdict = d.get('match_type', 'error')
        is_same = verdict in SAME_PRODUCT
        ok = (is_same == expect_same)
        results.append(ok)
        print(f"  {'OK  ' if ok else 'FAIL'}  {name:32} -> {verdict:15} "
              f"geom={d.get('geometry_similarity', 0):.2f} "
              f"col={d.get('colour_similarity', 0):.2f} "
              f"region={d.get('matched_region_percentage', 0):.0f}% {note}")

    print('Fabric matching — same/different discrimination\n')

    # Positives: the same fabric must be recognised when enough is visible.
    case('identical image', A, True)
    case('same fabric, 25% visible', crop_pct(A, 0.25), True)

    # Small crops: the honest answer is "cannot tell", NOT a same-product claim.
    for pct in (0.10, 0.05, 0.01):
        case(f'same fabric, {int(pct * 100)}% visible', crop_pct(A, pct), False,
             '(too little to claim)')

    # Negatives — the ones that protect against a confident wrong answer.
    case('different fabric B', B, False)
    case('different fabric C', C, False)
    case('different B, colour forced to A', recolour_towards(A, B), False,
         '(colour trap)')
    case('different C, colour forced to A', recolour_towards(A, C), False,
         '(colour trap)')

    passed = sum(results)
    print(f'\n{passed}/{len(results)} passed')

    if passed != len(results):
        return 1

    print('\nNo different fabric was ever reported as the same product.')
    return 0


if __name__ == '__main__':
    sys.exit(main())
