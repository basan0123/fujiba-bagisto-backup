#!/usr/bin/env python3
"""
Master-mode fabric gate tests (fabric_gate.py --master). Deterministic, offline.

  1  exact placement, lossless PNG                → PASS
  2  declared overlay (text panel) on fabric       → PASS
  3  UNDECLARED overlay on fabric                  → REJECT
  4  recoloured fabric (hue shift)                 → REJECT
  5  lossy JPEG q60 4:2:0 passed off as a master   → measured (ΔE00 > 0)
  6  lossless video master, frame by exact index   → PASS
  7  video frame with undeclared overlay           → REJECT
  8  region covered >50% by declared overlays      → REJECT
  9  deliver.py refuses when gate failed           → nothing written

Run: python3 test_master_gate.py   (exit 0 = all passed)
"""
import json
import os
import subprocess
import sys
import tempfile

import cv2
import numpy as np
from PIL import Image, ImageDraw

HERE = os.path.dirname(os.path.abspath(__file__))
GATE = os.path.join(HERE, 'fabric_gate.py')
DELIVER = os.path.join(os.path.dirname(HERE), 'v2', 'deliver.py')
sys.path.insert(0, os.path.join(os.path.dirname(HERE), 'v2'))
from common import cover, crop_box  # noqa: E402
sys.path.insert(0, HERE)
from test_fabric_integrity import make_fabric  # noqa: E402

W, H = 600, 700
BOX, OUT = [40, 40, 400, 400], [100, 150, 400, 400]


def canvas(src):
    cv = Image.new('RGB', (W, H), (20, 20, 30))
    cv.paste(cover(crop_box(Image.open(src).convert('RGB'), BOX), OUT[2], OUT[3]), tuple(OUT[:2]))
    return cv


def sidecar(path, src, exclude=None, n=None):
    r = {'src': src, 'src_box': BOX, 'out_box': OUT}
    if exclude:
        r['exclude'] = exclude
    if n is not None:
        r['n'] = n
    with open(path + '.json', 'w') as f:
        json.dump({'size': [W, H], 'fabric_regions': [r], 'min_exact_share': 0.1, 'frames': 3, 'fps': 30}, f)


def gate(master):
    p = subprocess.run([sys.executable, GATE, '--master', master], capture_output=True, text=True)
    try:
        return p.returncode, json.loads(p.stdout)
    except ValueError:
        return p.returncode, {'passed': False, 'error': p.stderr[-300:]}


def write_video(path, frames):
    ff = subprocess.Popen(['ffmpeg', '-v', 'error', '-y', '-f', 'rawvideo', '-pix_fmt', 'rgb24', '-s', f'{W}x{H}', '-r', '30', '-i', '-',
                           '-c:v', 'libx264rgb', '-qp', '0', '-preset', 'veryfast', '-pix_fmt', 'rgb24', path], stdin=subprocess.PIPE)
    for f in frames:
        ff.stdin.write(f.tobytes())
    ff.stdin.close()
    assert ff.wait() == 0


def main():
    td = tempfile.mkdtemp()
    src = os.path.join(td, 'src.png')
    make_fabric(src)
    results = []

    def rec(name, ok, detail=''):
        results.append(ok)
        print(f"  {'OK  ' if ok else 'FAIL'}  {name}  {detail}")

    def png(name, img, exclude=None):
        p = os.path.join(td, name + '.png')
        img.save(p)
        sidecar(p, src, exclude)
        return gate(p)

    rc, r = png('exact', canvas(src))
    rec('exact placement → PASS', r['passed'], json.dumps(r.get('summary')))

    panel = (150, 450, 300, 60)
    img = canvas(src)
    ImageDraw.Draw(img).rectangle((panel[0], panel[1], panel[0] + panel[2], panel[1] + panel[3]), fill=(0, 0, 0))
    rc, r = png('declared', img, [list(panel)])
    rec('declared overlay → PASS', r['passed'])

    rc, r = png('undeclared', img)
    rec('undeclared overlay → REJECT', not r['passed'] and rc == 2, json.dumps(r.get('summary')))

    img = canvas(src)
    a = cv2.cvtColor(np.asarray(img), cv2.COLOR_RGB2HSV)
    a[..., 0] = (a[..., 0].astype(int) + 8) % 180
    rc, r = png('recolour', Image.fromarray(cv2.cvtColor(a, cv2.COLOR_HSV2RGB)))
    rec('recoloured fabric → REJECT', not r['passed'], json.dumps(r.get('summary')))

    jp = os.path.join(td, 'lossy.jpg')
    canvas(src).save(jp, quality=60, subsampling=2)
    lossy = os.path.join(td, 'lossy.png')
    Image.open(jp).save(lossy)
    sidecar(lossy, src)
    rc, r = gate(lossy)
    rec('lossy q60 4:2:0 master measured (ΔE00 > 0)', (r['summary']['worst_deltaE00_mean'] or 0) > 0, json.dumps(r.get('summary')))

    frames = [np.asarray(canvas(src)) for _ in range(3)]
    vid = os.path.join(td, 'v.mkv')
    write_video(vid, frames)
    sidecar(vid, src, n=2)
    rc, r = gate(vid)
    rec('lossless video, exact frame n=2 → PASS', r['passed'], json.dumps(r.get('summary')))

    bad = np.asarray(canvas(src)).copy()
    bad[300:360, 200:400] = (255, 255, 255)
    vid2 = os.path.join(td, 'v2.mkv')
    write_video(vid2, [frames[0], frames[1], bad])
    sidecar(vid2, src, n=2)
    rc, r = gate(vid2)
    rec('video frame with undeclared overlay → REJECT', not r['passed'])

    rc, r = png('mostly_covered', canvas(src), [[OUT[0], OUT[1], OUT[2], int(OUT[3] * 0.6)]])
    rec('declared overlays > 50% of region → REJECT', not r['passed'])

    g = os.path.join(td, 'fail.gate.json')
    json.dump({'passed': False}, open(g, 'w'))
    out = os.path.join(td, 'never.jpg')
    p = subprocess.run([sys.executable, DELIVER, os.path.join(td, 'undeclared.png'), g, out], capture_output=True, text=True)
    rec('deliver.py refuses failed gate', p.returncode == 2 and not os.path.exists(out))

    print(f'\n{sum(results)}/{len(results)} passed')
    return 0 if all(results) else 1


if __name__ == '__main__':
    sys.exit(main())
