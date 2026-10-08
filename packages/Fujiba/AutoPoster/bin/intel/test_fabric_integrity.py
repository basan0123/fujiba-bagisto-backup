#!/usr/bin/env python3
"""
Test #9 — provider-generated fabric must never survive into an approved asset.

Deterministic fixtures (fixed seed, no network, no paid API). A MaliciousProvider
stands in for a generation provider that ignores its instructions and alters the
fabric anyway.

  1  honest composite      → compositor → gate PASS
  2  altered provider      → compositor → gate PASS   (fabric restored)
  3  altered provider      → NO compositor → gate REJECT
  4  recoloured fabric     → gate REJECT   (colour)
  5  inpainted motif       → gate REJECT   (pattern/structure)
  6  shadow layer          → gate PASS     (lighting is not a colour shift)

Run:  python3 test_fabric_integrity.py
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
GATE = os.path.join(HERE, 'fabric_gate.py')
COMPOSITOR = os.path.join(HERE, 'compositor.py')

# Spec thresholds (config/fujiba_intel.php fabric_gate).
CFG = json.dumps({
    'ciede2000_mean_max': 2.0,
    'ciede2000_p95_max': 5.0,
    'ssim_min': 0.95,
    'pattern_inlier_min': 0.6,
    'edge_diff_max': 0.15,
})


def make_fabric(path, seed=7, size=480):
    """A deterministic 'kimono-like' print: motifs over a coloured ground."""
    rng = np.random.default_rng(seed)
    img = np.zeros((size, size, 3), np.uint8)
    img[:, :] = (70, 40, 140)                      # BGR ground

    for _ in range(90):
        c = tuple(int(v) for v in rng.integers(60, 255, 3))
        cx, cy = int(rng.integers(20, size - 20)), int(rng.integers(20, size - 20))
        r = int(rng.integers(8, 26))
        cv2.circle(img, (cx, cy), r, c, -1)
        cv2.circle(img, (cx, cy), r, (20, 20, 20), 2)

    for _ in range(26):
        p1 = (int(rng.integers(0, size)), int(rng.integers(0, size)))
        p2 = (int(rng.integers(0, size)), int(rng.integers(0, size)))
        cv2.line(img, p1, p2, (210, 200, 180), 2)

    cv2.imwrite(path, img)
    return img


def make_mask(path, shape, inset=60):
    m = np.zeros(shape[:2], np.uint8)
    m[inset:shape[0] - inset, inset:shape[1] - inset] = 255
    cv2.imwrite(path, m)
    return m


class MaliciousProvider:
    """Stands in for a provider that ignores 'do not touch the fabric'."""

    @staticmethod
    def recolour(src, mask):
        out = src.copy()
        hsv = cv2.cvtColor(out, cv2.COLOR_BGR2HSV).astype(np.int16)
        hsv[..., 0] = (hsv[..., 0] + 60) % 180          # rotate hue
        shifted = cv2.cvtColor(hsv.astype(np.uint8), cv2.COLOR_HSV2BGR)
        out[mask > 127] = shifted[mask > 127]
        return out

    @staticmethod
    def inpaint_motif(src, mask):
        """Paint invented motifs over the real print."""
        out = src.copy()
        rng = np.random.default_rng(99)
        ys, xs = np.where(mask > 127)
        y0, y1, x0, x1 = ys.min(), ys.max(), xs.min(), xs.max()
        for _ in range(40):
            cx = int(rng.integers(x0 + 10, x1 - 10))
            cy = int(rng.integers(y0 + 10, y1 - 10))
            cv2.rectangle(out, (cx - 14, cy - 14), (cx + 14, cy + 14), (255, 255, 255), -1)
            cv2.putText(out, 'AI', (cx - 12, cy + 6), cv2.FONT_HERSHEY_SIMPLEX,
                        0.5, (0, 0, 0), 2)
        return out


def run_gate(source, candidate, mask=None, shadow=None):
    cmd = [sys.executable, GATE, '--source', source, '--candidate', candidate,
           '--config-json', CFG]
    if mask:
        cmd += ['--mask', mask]
    if shadow:
        cmd += ['--shadow', shadow]
    p = subprocess.run(cmd, capture_output=True, text=True)
    try:
        return json.loads(p.stdout or '{}'), p.returncode
    except ValueError:
        return {'passed': False, 'error': p.stdout + p.stderr}, p.returncode


def run_compositor(generated, source, mask, out, shadow=None):
    cmd = [sys.executable, COMPOSITOR, '--generated', generated, '--source', source,
           '--mask', mask, '--out', out]
    if shadow:
        cmd += ['--shadow', shadow]
    p = subprocess.run(cmd, capture_output=True, text=True)
    return p.returncode == 0


def main():
    tmp = tempfile.mkdtemp(prefix='fabric_test')
    results = []

    def record(name, expect_pass, got_pass, detail=''):
        ok = (expect_pass == got_pass)
        results.append((name, expect_pass, got_pass, ok, detail))
        print(f"  {'OK  ' if ok else 'FAIL'}  {name}: expected "
              f"{'PASS' if expect_pass else 'REJECT'}, got "
              f"{'PASS' if got_pass else 'REJECT'}  {detail}")

    src_p = os.path.join(tmp, 'source.png')
    src = make_fabric(src_p)
    mask_p = os.path.join(tmp, 'mask.png')
    mask = make_mask(mask_p, src.shape)

    print('Fabric integrity — test #9\n')

    # 1. honest composite: provider only fills OUTSIDE the mask
    honest = src.copy()
    honest[mask <= 127] = (30, 120, 60)
    honest_p = os.path.join(tmp, 'honest.png')
    cv2.imwrite(honest_p, honest)
    comp1 = os.path.join(tmp, 'c1.png')
    run_compositor(honest_p, src_p, mask_p, comp1)
    r, _ = run_gate(src_p, comp1, mask_p)
    record('honest composite → compositor → gate', True, r.get('passed', False))

    # 2. malicious recolour, BUT through the compositor
    bad = MaliciousProvider.recolour(src, mask)
    bad_p = os.path.join(tmp, 'bad.png')
    cv2.imwrite(bad_p, bad)
    comp2 = os.path.join(tmp, 'c2.png')
    run_compositor(bad_p, src_p, mask_p, comp2)
    r, _ = run_gate(src_p, comp2, mask_p)
    record('altered provider → compositor → gate (fabric restored)', True,
           r.get('passed', False))

    # 3. same malicious output, compositor BYPASSED — must be rejected
    r, _ = run_gate(src_p, bad_p, mask_p)
    de = next((c for c in r.get('checks', []) if c['check'] == 'colour_ciede2000'), {})
    record('altered provider → NO compositor → gate', False, r.get('passed', True),
           f"ΔE mean={de.get('mean')}")

    # 4. recoloured fabric specifically
    r, _ = run_gate(src_p, bad_p, mask_p)
    colour_failed = any(c['check'] == 'colour_ciede2000' and not c['pass']
                        for c in r.get('checks', []))
    record('recoloured fabric rejected on colour', True, colour_failed)

    # 5. inpainted motif
    inp = MaliciousProvider.inpaint_motif(src, mask)
    inp_p = os.path.join(tmp, 'inpaint.png')
    cv2.imwrite(inp_p, inp)
    r, _ = run_gate(src_p, inp_p, mask_p)
    record('inpainted motif → gate', False, r.get('passed', True))

    # 6. plausible shadow must NOT be mistaken for tampering.
    #
    # The shadow is blurred, so its penumbra darkens pixels that a hard >127
    # cut would leave in the comparison. The DECLARED shadow region must
    # therefore cover the whole area the layer actually affects — here, a
    # dilated binarisation of the blurred layer. Declaring less than you
    # darkened is a fixture bug, not a reason to relax the ΔE limit.
    shadow = np.zeros(src.shape[:2], np.uint8)
    cv2.rectangle(shadow, (80, 80), (240, 400), 160, -1)
    shadow = cv2.GaussianBlur(shadow, (61, 61), 0)
    shadow_p = os.path.join(tmp, 'shadow.png')
    cv2.imwrite(shadow_p, shadow)

    declared = ((shadow > 2).astype(np.uint8)) * 255
    declared = cv2.dilate(declared, np.ones((9, 9), np.uint8), iterations=1)
    declared_p = os.path.join(tmp, 'shadow_declared.png')
    cv2.imwrite(declared_p, declared)

    comp3 = os.path.join(tmp, 'c3.png')
    run_compositor(honest_p, src_p, mask_p, comp3, shadow=shadow_p)
    r, _ = run_gate(src_p, comp3, mask_p, shadow=declared_p)
    de3 = next((c for c in r.get('checks', []) if c['check'] == 'colour_ciede2000'), {})
    record('shadow layer excluded from ΔE', True, r.get('passed', False),
           f"ΔE mean={de3.get('mean')} p95={de3.get('p95')}")

    # 6b. an UNDECLARED shadow must still be caught — the exclusion must not
    # become a loophole for silently darkening fabric.
    r, _ = run_gate(src_p, comp3, mask_p)
    record('undeclared shadow still rejected', False, r.get('passed', True))

    failed = [x for x in results if not x[3]]
    print(f"\n{len(results) - len(failed)}/{len(results)} passed")
    if failed:
        print('FAILURES:')
        for name, exp, got, _, detail in failed:
            print(f'  - {name}: expected {exp}, got {got} {detail}')
        return 1

    print('\nProvider-generated fabric cannot reach an approved asset.')
    return 0


if __name__ == '__main__':
    sys.exit(main())
