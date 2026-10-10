#!/usr/bin/env python3
"""
Derive the delivery file from a lossless master — ONLY after the fabric gate passed.

    deliver.py <master.png|master.mkv> <gate.json> <out.jpg|out.mp4>

  image  PNG → JPEG q95, 4:4:4 (4:2:0 smears fine gold lines, ΔE≈5)
  video  lossless mkv → H.264 MP4 crf 19 yuv420p + silent AAC, faststart

Also measures delivery drift (mean CIEDE2000 after σ1.5 blur, master vs
delivery, over the verified fabric mask for images / whole frame for video
frame 0) and writes it to <out>.json together with the gate result.
Exit 0 ok, 2 gate not passed (nothing written), 3 error.
"""
import json
import os
import subprocess
import sys
import tempfile

import cv2
import numpy as np

sys.path.insert(0, os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), 'intel'))
from fabric_gate import ciede2000, to_lab  # noqa: E402


def drift(a, b, mask=None):
    a = cv2.GaussianBlur(a, (0, 0), 1.5)
    b = cv2.GaussianBlur(b, (0, 0), 1.5)
    de = ciede2000(to_lab(a), to_lab(b))
    if mask is not None and mask.any():
        de = de[mask]
    return round(float(de.mean()), 3)


def frame0(path):
    with tempfile.TemporaryDirectory() as td:
        f = os.path.join(td, 'f.png')
        subprocess.run(['ffmpeg', '-v', 'error', '-y', '-i', path, '-vf', 'select=eq(n\\,0)', '-vsync', '0', '-frames:v', '1', f], check=True)
        return cv2.imread(f, cv2.IMREAD_COLOR)


def main():
    master, gate_file, out = sys.argv[1:4]
    gate = json.load(open(gate_file))
    if gate.get('passed') is not True:
        print(json.dumps({'delivered': False, 'reason': 'gate not passed'}))
        return 2
    side = json.load(open(master + '.json'))
    if master.lower().endswith('.png'):
        from PIL import Image
        Image.open(master).convert('RGB').save(out, quality=95, subsampling=0)
        mpath = os.path.splitext(master)[0] + '.gatemask.png'
        m = cv2.imread(mpath, cv2.IMREAD_GRAYSCALE) if os.path.exists(mpath) else None
        d = drift(cv2.imread(master), cv2.imread(out), None if m is None else m > 127)
    else:
        dur = side['frames'] / side['fps']
        subprocess.run(['ffmpeg', '-v', 'error', '-y', '-i', master, '-f', 'lavfi', '-t', f'{dur:.3f}', '-i', 'anullsrc=r=44100:cl=stereo',
                        '-map', '0:v', '-map', '1:a', '-c:v', 'libx264', '-preset', 'medium', '-crf', '19', '-pix_fmt', 'yuv420p',
                        '-c:a', 'aac', '-shortest', '-movflags', '+faststart', out], check=True)
        d = drift(frame0(master), frame0(out))
    unchecked = side.get('unchecked_regions') or []
    side.update({'delivered_from': os.path.basename(master), 'gate': gate, 'delivery_drift_deltaE00': d,
                 # fabric the gate could not verify => never eligible for a test post
                 'test_eligible': bool(gate.get('passed')) and not unchecked})
    with open(out + '.json', 'w') as f:
        json.dump(side, f, ensure_ascii=False, indent=1)
    print(json.dumps({'delivered': True, 'out': out, 'delivery_drift_deltaE00': d, 'test_eligible': side['test_eligible'],
                      'unchecked_regions': len(unchecked)}))
    return 0


if __name__ == '__main__':
    try:
        sys.exit(main())
    except (OSError, ValueError, KeyError, subprocess.CalledProcessError) as exc:
        print(json.dumps({'delivered': False, 'error': f'{type(exc).__name__}: {exc}'}))
        sys.exit(3)
