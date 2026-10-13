#!/usr/bin/env python3
"""V3 preview driver (PREVIEW ONLY: no posting, no queue rows).

    nice -n 10 python3 run.py <family_id> <ctx.json> <out_dir> [product_id ...]

Per product: render master PNG + sidecar -> zero-size check -> fabric_gate --master ->
deliver.py JPEG (only on pass) -> side-by-side PNG per fabric region (expected | composited).
Writes <out_dir>/F<id>.summary.json with gate metrics and seconds per image.
"""
import json
import os
import subprocess
import sys
import time

HERE = os.path.dirname(os.path.abspath(__file__))
BIN = os.path.dirname(HERE)
sys.path.insert(0, os.path.join(BIN, 'intel'))
__import__('heavy_guard').acquire('v3-run')

import numpy as np  # noqa: E402
from PIL import Image  # noqa: E402

import families  # noqa: E402
from common import cover, crop_box, load  # noqa: E402


def nonzero(path):
    if not os.path.exists(path) or os.path.getsize(path) == 0:
        raise SystemExit(f'zero-size output: {path}')


def side_by_side(master, side, base):
    out = []
    m = Image.open(master).convert('RGB')
    for i, r in enumerate(side['fabric_regions']):
        x, y, w, h = r['out_box']
        exp = cover(crop_box(load(r['src']), r['src_box']), w, h)
        got = m.crop((x, y, x + w, y + h))
        sb = Image.new('RGB', (w * 2 + 20, h), (255, 255, 255))
        sb.paste(exp, (0, 0))
        sb.paste(got, (w + 20, 0))
        p = f'{base}.sbs{i}.png'
        sb.save(p)
        nonzero(p)
        out.append(os.path.basename(p))
    for i, u in enumerate(side.get('unchecked_regions') or []):
        if u.get('sbs'):
            out.append(u['sbs'])
    return out


def main():
    fid, ctx_file, out_dir = int(sys.argv[1]), sys.argv[2], sys.argv[3]
    only = {int(a) for a in sys.argv[4:]}
    ctx = json.load(open(ctx_file))
    fam = families.get(fid)
    rows = []
    for p in ctx['products']:
        if only and p['product_id'] not in only:
            continue
        t0 = time.time()
        base = os.path.join(out_dir, f"F{fid}-{fam.name}-{p['name']}")
        master = base + '.png'
        side = fam.render(p, master)
        nonzero(master)
        nonzero(master + '.json')
        g = subprocess.run([sys.executable, os.path.join(BIN, 'intel', 'fabric_gate.py'), '--master', master,
                            '--mask-out', base + '.gatemask.png'], capture_output=True, text=True)
        gate = json.loads(g.stdout)
        with open(base + '.gate.json', 'w') as f:
            json.dump(gate, f, ensure_ascii=False, indent=1)
        inv = side.get('inverse_warp')
        if inv is not None and not inv.get('pass'):
            gate['passed'] = False                      # inverse-warp check is part of the gate for warped fabric
            gate.setdefault('checks', []).append({'check': 'roll_inverse_warp', **inv})
            with open(base + '.gate.json', 'w') as f:
                json.dump(gate, f, ensure_ascii=False, indent=1)
        delivered = None
        if gate.get('passed'):
            dv = subprocess.run([sys.executable, os.path.join(BIN, 'v2', 'deliver.py'), master, base + '.gate.json', base + '.jpg'],
                                capture_output=True, text=True)
            delivered = json.loads(dv.stdout.strip().splitlines()[-1])
            nonzero(base + '.jpg')
        sbs = side_by_side(master, side, base)
        rows.append({'product': p['name'], 'family': fam.name, 'passed': gate.get('passed'), 'summary': gate.get('summary'),
                     'extra': side.get('inverse_warp'), 'delivered': delivered, 'sbs': sbs,
                     'seconds': round(time.time() - t0, 2)})
        print(json.dumps(rows[-1], ensure_ascii=False))
    with open(os.path.join(out_dir, f'F{fid}.summary.json'), 'w') as f:
        json.dump(rows, f, ensure_ascii=False, indent=1)
    return 0 if rows and all(r['passed'] for r in rows) else 2


if __name__ == '__main__':
    sys.exit(main())
