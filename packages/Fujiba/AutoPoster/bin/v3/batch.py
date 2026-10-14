#!/usr/bin/env python3
"""Fujiba V3 FREE creative batch (PREVIEW ONLY: no API calls, no posting, no queue rows,
no DB/catalog writes). Every enabled, in-stock kimono fabric product with our public code.

    setsid nohup nice -n 10 python3 batch.py [--limit N] [--only 2075,2077] [--resume] [--force] \
        > <out>/batch.log 2>&1 &

Per product: one 1080x1350 image of the family the rotation gives it (closeup 3 / roll 4 /
stack 5 / hero 6 / showcase 7; stack + showcase at most once per series, stack only with
>= 2 in-stock colours, never the same family twice in a row inside a series) and an 8 s
9:16 pan/zoom MP4 (no audio) cut from the same real photo. Every fabric region of every
image and of 9 exact video frames (lossless master) goes through bin/intel/fabric_gate.py
master mode, unchanged thresholds. Gate failures are kept under <family>/REJECTED/ and the
next family in the rotation is tried.

Update semantics: manifest/<product_id>.json stores the input fingerprint (photo sha256s,
price, in-stock, family, series members). A re-run rebuilds only products whose fingerprint
changed; products no longer in scope (sold out, disabled, ...) get stale=true + a .STALE
marker next to their outputs (nothing is deleted). --force rebuilds everything; --resume
also skips products already attempted in the current checkpoint (incl. failures).
"""
import argparse
import csv
import hashlib
import json
import os
import shutil
import subprocess
import sys
import time

HERE = os.path.dirname(os.path.abspath(__file__))
BIN = os.path.dirname(HERE)
sys.path.insert(0, os.path.join(BIN, 'intel'))
sys.path.insert(0, os.path.join(BIN, 'v2'))
for _k in ('OMP_NUM_THREADS', 'OPENBLAS_NUM_THREADS', 'MKL_NUM_THREADS'):
    os.environ.setdefault(_k, '2')       # keep the box responsive (load guard 4.2)
__import__('heavy_guard').acquire('v3-batch')

import numpy as np  # noqa: E402
from PIL import Image, ImageDraw  # noqa: E402

import families  # noqa: E402
from common import CREAM, GOLD, INK, cover, crop_box, draw_text, load, save_sidecar  # noqa: E402
from rotation import next_family  # noqa: E402

LIVE = '/var/www/bagisto'
OUT = os.environ.get('V3B_DIR', LIVE + '/storage/app/autoposter/preview/v3-batch')
WM_CSV = LIVE + '/storage/app/intel/twin/watermark_scan_enabled.csv'
GENUINE_WM = {'KIMONO-2471', 'KIMONO-2648', 'KIMONO-2650'}     # owner-confirmed; white-template hits are false alarms
RENDER_VERSION = 'v3b-1'
MIN_SHORT = 800
ROT = [3, 4, 5, 6, 7]
ONCE_PER_SERIES = {5, 7}
LOAD_MAX = 4.2
VW, VH, FPS, VSEC = 1080, 1920, 30, 8
MUTED = (110, 104, 96)


def log(*a):
    print(time.strftime('%F %T'), *a, flush=True)


def wait_load():
    while os.getloadavg()[0] > LOAD_MAX:
        log(f'load {os.getloadavg()[0]:.2f} > {LOAD_MAX}, pausing 30 s')
        time.sleep(30)


def sha256(path):
    h = hashlib.sha256()
    with open(path, 'rb') as f:
        for b in iter(lambda: f.read(1 << 20), b''):
            h.update(b)
    return h.hexdigest()


def jdump(path, obj):
    tmp = path + '.tmp'
    with open(tmp, 'w') as f:
        json.dump(obj, f, ensure_ascii=False, indent=1)
    os.replace(tmp, path)


def scope():
    path = os.path.join(OUT, 'scope.json')
    r = subprocess.run(['php', 'artisan', 'tinker', f"--execute=require '{HERE}/batch_scope.php';"], cwd=LIVE,
                       env={**os.environ, 'V3B_OUT': path}, capture_output=True, text=True, timeout=900)
    if r.returncode or not os.path.exists(path):
        raise SystemExit(f'scope failed: {r.stdout[-500:]} {r.stderr[-500:]}')
    log('scope:', r.stdout.strip().splitlines()[-1])
    return json.load(open(path))


# ---- watermark ---------------------------------------------------------
def wm_source(p):
    """Genuinely watermarked products: use the primary only if wm_detect finds no mark on it,
    else the largest watermark-free 4:5 crop (saved losslessly as its own source)."""
    import cv2
    import wm_detect
    bgr = cv2.imread(p['image'])
    det = wm_detect.detect(bgr)
    if not det['watermark_present']:
        return p['image'], {'primary_clean': True}
    rect, _, rc = wm_detect.wm_free_crop(bgr, 0.8, wm=det)
    info = {'primary_clean': False, 'boxes': det['boxes'], 'crop': rect, 'recheck': rc}
    if not rect or min(rect[2], rect[3]) < MIN_SHORT:
        return None, info
    os.makedirs(os.path.join(OUT, '_crops'), exist_ok=True)
    dst = os.path.join(OUT, '_crops', f"{p['product_id']}.png")
    x, y, w, h = rect
    cv2.imwrite(dst, bgr[y:y + h, x:x + w])
    return dst, info


# ---- video -------------------------------------------------------------
def ease(t):
    t = min(max(t, 0.0), 1.0)
    return t * t * (3 - 2 * t)


def video(p, src_path, base):
    """8 s 9:16 pan/zoom: detail push-out to the whole 9:16 view of the real photo. Every
    frame is cover(crop_box(photo, box_n)); the text panel is a declared overlay."""
    src = load(src_path)
    iw, ih = src.size
    fw = min(iw, ih * 9 / 16)
    fh = fw * 16 / 9
    full = [(iw - fw) / 2, (ih - fh) / 2, fw, fh]
    d = families.detail_boxes(src, 1, max(64, int(min(iw, ih) // 5)))
    zw, zh = fw * 0.6, fh * 0.6
    cx, cy = (d[0][0] + d[0][2] / 2, d[0][1] + d[0][3] / 2) if d else (iw / 2, ih / 2)
    zoom = [min(max(cx - zw / 2, 0), iw - zw), min(max(cy - zh / 2, 0), ih - zh), zw, zh]
    n = VSEC * FPS
    panel = [0, VH - 330, VW, 330]
    check = sorted(set(list(range(0, n, FPS)) + [n - 1]))
    regions, texts = [], []
    master = base + '.mkv'
    ff = subprocess.Popen(['ffmpeg', '-v', 'error', '-y', '-f', 'rawvideo', '-pix_fmt', 'rgb24', '-s', f'{VW}x{VH}', '-r', str(FPS),
                           '-i', '-', '-threads', '2', '-c:v', 'libx264rgb', '-qp', '0', '-preset', 'veryfast', '-pix_fmt', 'rgb24', master],
                          stdin=subprocess.PIPE)
    m = p.get('material') or {}
    sz = p.get('size') or {}
    lines = [(p['name'], 64, INK, 'serif'), (f"¥{int(p['price']):,}", 54, INK, 'serif')]
    if m.get('en') == 'Hosonuno (100% cotton)':
        lines.append(('Hosonuno (100% cotton) · 綿100%', 30, MUTED, 'ja'))
    if sz.get('width_cm') or sz.get('length_m'):
        parts = ([f"width {sz['width_cm']:g} cm"] if sz.get('width_cm') else []) + ([f"{sz['length_m']:g} m"] if sz.get('length_m') else [])
        lines.append((' · '.join(parts), 28, MUTED, 'sans'))
    else:
        lines.append(('size not confirmed · real photo', 28, MUTED, 'sans'))
    for i in range(n):
        t = ease(i / (n - 1))
        box = [int(round(a + (b - a) * t)) for a, b in zip(zoom, full)]
        fr = cover(crop_box(src, box), VW, VH)
        dr = ImageDraw.Draw(fr)
        dr.rectangle((panel[0], panel[1], panel[0] + panel[2] - 1, panel[1] + panel[3] - 1), fill=CREAM)
        dr.line((60, panel[1] + 4, VW - 60, panel[1] + 4), fill=GOLD, width=3)
        y = panel[1] + 30
        for s, size, col, kind in lines:
            draw_text(dr, (60, y), s, size, col, kind)
            if i == 0:
                texts.append({'text': s, 'size': size})
            y += size + 18
        if i in check:
            regions.append({'n': i, 'src': src_path, 'src_box': box, 'out_box': [0, 0, VW, VH], 'exclude': [panel]})
        ff.stdin.write(fr.tobytes())
    ff.stdin.close()
    if ff.wait():
        raise RuntimeError('ffmpeg failed')
    save_sidecar(master, {'template': 'v3b_panzoom', 'size': [VW, VH], 'fps': FPS, 'frames': n, 'fabric_regions': regions,
                          'texts': texts, 'min_text_px': min(t['size'] for t in texts), 'preview_only': True,
                          'motion': {'from_box': [round(v) for v in zoom], 'to_box': [round(v) for v in full], 'ease': 'smoothstep'}})
    return master


def deliver_video(master, out):
    import deliver
    subprocess.run(['ffmpeg', '-v', 'error', '-y', '-i', master, '-map', '0:v', '-an', '-threads', '2', '-c:v', 'libx264', '-preset', 'medium',
                    '-crf', '19', '-pix_fmt', 'yuv420p', '-movflags', '+faststart', out], check=True)
    return deliver.drift(deliver.frame0(master), deliver.frame0(out))


# ---- main --------------------------------------------------------------
def gate(master, mask_out=None):
    import cv2
    cv2.setNumThreads(2)
    import fabric_gate as fg
    return fg.gate_master(master, {**fg.DEFAULTS, **fg.MASTER_DEFAULTS}, mask_out)


def render_image(fid, p, stem):
    fam = families.get(fid)
    d = os.path.join(OUT, fam.name)
    os.makedirs(d, exist_ok=True)
    base = os.path.join(d, stem)
    master = base + '.png'
    t0 = time.time()
    try:
        side = fam.render(p, master)
        g = gate(master, base + '.gatemask.png')
    except Exception as exc:  # noqa: BLE001 — a renderer crash is a rejection, not a batch abort
        return {'family': fam.name, 'passed': False, 'error': f'{type(exc).__name__}: {exc}', 'seconds': round(time.time() - t0, 2)}
    inv = side.get('inverse_warp')
    if inv is not None and not inv.get('pass'):
        g['passed'] = False
        g.setdefault('checks', []).append({'check': 'roll_inverse_warp', **inv})
    jdump(base + '.gate.json', g)
    res = {'family': fam.name, 'family_id': fid, 'passed': bool(g.get('passed')), 'gate_summary': g.get('summary'),
           'failed_checks': [c['check'] for c in g.get('checks', []) if c.get('pass') is False]}
    if g.get('passed'):
        dv = subprocess.run([sys.executable, os.path.join(BIN, 'v2', 'deliver.py'), master, base + '.gate.json', base + '.jpg'],
                            capture_output=True, text=True)
        res['delivered'] = json.loads(dv.stdout.strip().splitlines()[-1])
        res['outputs'] = [master, base + '.jpg']
        if not os.path.getsize(base + '.jpg'):
            res['passed'] = False
    if not res['passed']:
        rj = os.path.join(d, 'REJECTED')
        os.makedirs(rj, exist_ok=True)
        moved = []
        for suf in ('.png', '.png.json', '.gate.json', '.gatemask.png', '.roll-inverse.sbs.png', '.jpg', '.jpg.json'):
            if os.path.exists(base + suf):
                shutil.move(base + suf, os.path.join(rj, stem + suf))
                moved.append(os.path.join(rj, stem + suf))
        res['outputs'] = moved
    res['seconds'] = round(time.time() - t0, 2)
    return res


def assign(series_members):
    """Deterministic family plan per product id for one series (list ordered by name)."""
    plan, prev, used = {}, None, set()
    for m in series_members:
        en = {f for f in ROT if f not in used or f not in ONCE_PER_SERIES}
        if len(series_members) < 2:
            en.discard(5)
        f = next_family(ROT, en, prev)
        plan[m['product_id']] = f
        used.add(f)
        prev = f
    return plan


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--limit', type=int, default=0)
    ap.add_argument('--only', default='')
    ap.add_argument('--resume', action='store_true')
    ap.add_argument('--force', action='store_true')
    a = ap.parse_args()
    only = {int(x) for x in a.only.split(',') if x.strip()}
    os.makedirs(os.path.join(OUT, 'manifest'), exist_ok=True)
    os.makedirs(os.path.join(OUT, 'video'), exist_ok=True)
    t_start = time.time()
    sc = scope()
    rows = sc['products']
    skips = []

    def skip(p, reason, detail=''):
        skips.append({'product_id': p['product_id'], 'sku': p['sku'], 'name': p['name'], 'reason': reason, 'detail': detail,
                      'image': p.get('image_rel') or ''})

    cand = []
    for p in rows:
        if p['fail']:
            first = p['fail'][0]
            skip(p, first.split(':')[0].split(' (')[0], '; '.join(p['fail']))
            continue
        if min(p['width'] or 0, p['height'] or 0) < MIN_SHORT:
            skip(p, 'photo short side < 800 px', f"{p['width']}x{p['height']}")
            continue
        cand.append(p)
    # watermark (genuine list only)
    for p in list(cand):
        if p['sku'] in GENUINE_WM:
            wait_load()
            src, info = wm_source(p)
            p['wm'] = info
            if src is None:
                skip(p, 'watermarked photo, no clean crop >= 800 px', json.dumps(info, ensure_ascii=False))
                cand.remove(p)
            else:
                p['image'] = src
                p['image_render_sha256'] = sha256(src)
    by_series = {}
    for p in cand:
        by_series.setdefault(p['series'], []).append(p)
    for s in by_series:
        by_series[s].sort(key=lambda m: (m['name'], m['product_id']))
    in_scope = {p['product_id'] for p in cand}

    # stale marking: manifests of products no longer in scope
    for fn in os.listdir(os.path.join(OUT, 'manifest')):
        if not fn.endswith('.json'):
            continue
        mp = os.path.join(OUT, 'manifest', fn)
        man = json.load(open(mp))
        if man['product_id'] not in in_scope and not man.get('stale'):
            why = next((s['detail'] for s in skips if s['product_id'] == man['product_id']), 'no longer enabled')
            man.update({'stale': True, 'stale_reason': why, 'stale_at': time.strftime('%FT%T%z')})
            jdump(mp, man)
            for o in man.get('outputs', []):
                if os.path.exists(o):
                    open(o + '.STALE', 'w').write(why + '\n')
            log('stale', man['product_id'], why)

    ck_path = os.path.join(OUT, 'checkpoint.json')
    ck = json.load(open(ck_path)) if a.resume and os.path.exists(ck_path) else {'started': time.strftime('%FT%T%z'), 'done': []}
    todo = [p for s in sorted(by_series) for p in by_series[s] if not only or p['product_id'] in only]
    done_n = 0
    for p in todo:
        pid = p['product_id']
        series = by_series[p['series']]
        plan = assign(series)
        members = [{'product_id': m['product_id'], 'name': m['name'], 'price': m['price'], 'image': m['image']} for m in series]
        fp_src = {'v': RENDER_VERSION, 'sha': p.get('image_render_sha256') or p['image_sha256'], 'price': p['price'],
                  'in_stock': p['available'] > 0, 'family': plan[pid], 'material': p['material'], 'size': p['size'],
                  'members': [(m['product_id'], m['price'], m.get('image_render_sha256') or m['image_sha256']) for m in series]}
        fp = hashlib.sha256(json.dumps(fp_src, sort_keys=True, ensure_ascii=False).encode()).hexdigest()
        mp = os.path.join(OUT, 'manifest', f'{pid}.json')
        old = json.load(open(mp)) if os.path.exists(mp) else None
        if not a.force and old and old.get('fingerprint') == fp and old.get('status') in ('rendered', 'rejected') \
                and all(os.path.exists(o) for o in old.get('outputs', [])):
            if old.get('stale'):
                old.pop('stale', None)
                jdump(mp, old)
            continue
        if a.resume and pid in ck['done']:
            continue
        if a.limit and done_n >= a.limit:
            break
        wait_load()
        t0 = time.time()
        ctx = {'product_id': pid, 'name': p['name'], 'price': p['price'], 'image': p['image'], 'material': p['material'],
               'size': p['size'], 'series': p['series'], 'series_members': members}
        stem = f"{p['name']}-{pid}"
        attempts, chosen, prev = [], None, None
        order = [plan[pid]] + [f for f in ROT if f != plan[pid]]
        for f in order:
            if f == 5 and len(series) < 2:
                continue
            r = render_image(f, ctx, stem)
            attempts.append(r)
            if r['passed']:
                chosen = r
                break
        vid = {}
        try:
            vbase = os.path.join(OUT, 'video', stem)
            vt = time.time()
            master = video(ctx, p['image'], vbase)
            g = gate(master)
            jdump(vbase + '.gate.json', g)
            vid = {'passed': bool(g['passed']), 'frames_checked': len([c for c in g['checks'] if c['check'].startswith('fabric')]),
                   'gate_summary': g['summary']}
            if g['passed']:
                vid['delivery_drift_deltaE00'] = deliver_video(master, vbase + '.mp4')
                vid['outputs'] = [vbase + '.mp4', vbase + '.gate.json', master + '.json']
                os.remove(master)            # lossless master is deterministic & ~100+ MB; frames were gated from it
                vid['master_removed_after_gate'] = True
            else:
                rj = os.path.join(OUT, 'video', 'REJECTED')
                os.makedirs(rj, exist_ok=True)
                for suf in ('.mkv', '.mkv.json', '.gate.json'):
                    if os.path.exists(vbase + suf):
                        shutil.move(vbase + suf, os.path.join(rj, stem + suf))
                vid['outputs'] = [os.path.join(rj, stem + '.mkv')]
            vid['seconds'] = round(time.time() - vt, 2)
        except Exception as exc:  # noqa: BLE001
            vid = {'passed': False, 'error': f'{type(exc).__name__}: {exc}', 'outputs': []}
        if not chosen:
            skip(p, 'all families rejected by gate', '; '.join(f"{r['family']}:{r.get('failed_checks') or r.get('error')}" for r in attempts))
        man = {'product_id': pid, 'sku': p['sku'], 'name': p['name'], 'series': p['series'], 'price': p['price'],
               'available': p['available'], 'qty': p['qty'], 'reserved': p['reserved'],
               'source_images': [{'path': p['image'], 'sha256': p.get('image_render_sha256') or p['image_sha256'],
                                  'catalog_path': p['image_rel'], 'catalog_sha256': p['image_sha256']}],
               'watermark': p.get('wm'), 'material': p['material'], 'size': p['size'],
               'size_note': None if (p['size']['width_cm'] or p['size']['length_m']) else 'size not confirmed',
               'planned_family': families.REGISTRY[plan[pid]].name, 'family': chosen['family'] if chosen else None,
               'attempts': attempts, 'video': vid, 'status': 'rendered' if chosen else 'rejected',
               'outputs': (chosen['outputs'] if chosen else []) + [o for o in vid.get('outputs', []) if o.endswith('.mp4')],
               'fingerprint': fp, 'fingerprint_inputs': fp_src, 'render_version': RENDER_VERSION,
               'generated_at': time.strftime('%FT%T%z'), 'seconds': round(time.time() - t0, 2),
               'preview_only': True, 'paid_api_calls': 0}
        jdump(mp, man)
        ck['done'].append(pid)
        jdump(ck_path, ck)
        done_n += 1
        log(f"{pid} {p['name']} img={man['family']} ({len(attempts)} try) video={vid.get('passed')} {man['seconds']}s")

    write_summary(sc, skips, t_start)
    return 0


def write_summary(sc, skips, t_start):
    mans = [json.load(open(os.path.join(OUT, 'manifest', f))) for f in sorted(os.listdir(os.path.join(OUT, 'manifest'))) if f.endswith('.json')]
    live = [m for m in mans if not m.get('stale')]
    per_fam, att_pass, att_fail, img_secs = {}, 0, 0, []
    for m in live:
        if m['family']:
            per_fam[m['family']] = per_fam.get(m['family'], 0) + 1
        for a in m['attempts']:
            att_pass += bool(a['passed'])
            att_fail += not a['passed']
            img_secs.append(a['seconds'])
    reasons = {}
    for s in skips:
        reasons[s['reason']] = reasons.get(s['reason'], 0) + 1
    vids = [m['video'] for m in live]
    summ = {'generated_at': time.strftime('%FT%T%z'), 'run_seconds': round(time.time() - t_start, 1),
            'enabled_rows': len(sc['products']), 'in_scope_rendered_or_attempted': len(live),
            'rendered': sum(m['status'] == 'rendered' for m in live), 'all_families_rejected': sum(m['status'] == 'rejected' for m in live),
            'stale': sum(bool(m.get('stale')) for m in mans), 'skipped': len(skips), 'skip_reasons': reasons,
            'images_per_family': per_fam, 'image_gate': {'pass': att_pass, 'fail': att_fail},
            'video': {'pass': sum(bool(v.get('passed')) for v in vids), 'fail': sum(not v.get('passed') for v in vids)},
            'seconds_per_image_mean': round(sum(img_secs) / len(img_secs), 2) if img_secs else None,
            'seconds_per_video_mean': round(np.mean([v['seconds'] for v in vids if v.get('seconds')]), 2) if any(v.get('seconds') for v in vids) else None,
            'paid_api_calls': 0}
    jdump(os.path.join(OUT, 'summary.json'), summ)
    with open(os.path.join(OUT, 'summary.csv'), 'w', newline='') as f:
        w = csv.writer(f)
        w.writerow(['product_id', 'name', 'series', 'price', 'available', 'status', 'stale', 'planned_family', 'family', 'attempts',
                    'video_passed', 'image_jpg', 'video_mp4', 'generated_at'])
        for m in mans:
            jpg = next((o for o in m['outputs'] if o.endswith('.jpg')), '')
            mp4 = next((o for o in m['outputs'] if o.endswith('.mp4')), '')
            w.writerow([m['product_id'], m['name'], m['series'], m['price'], m['available'], m['status'], bool(m.get('stale')),
                        m['planned_family'], m['family'], len(m['attempts']), m['video'].get('passed'), jpg, mp4, m['generated_at']])
    with open(os.path.join(OUT, 'skips.csv'), 'w', newline='') as f:
        w = csv.DictWriter(f, fieldnames=['product_id', 'sku', 'name', 'reason', 'detail', 'image'])
        w.writeheader()
        w.writerows(skips)
    log('summary', json.dumps(summ, ensure_ascii=False))


if __name__ == '__main__':
    sys.exit(main())
