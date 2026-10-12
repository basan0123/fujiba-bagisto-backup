#!/usr/bin/env python3
"""WS-L pipeline test renders from a gated repeat tile (preview only, never published).

    twin_render.py --tile T.png --out-dir D --code FV-80-H --px-per-cm 18.0 --scale-basis "..." [--tier B]

Outputs (all lossless masters + JSON sidecars; JPEG/MP4 previews derived afterwards):
  flat_110x200_150dpi.png   6496 x 11811 px: tile scaled ONCE (wrap-padded Lanczos) to the stated
                            cm-per-repeat, then tiled by integer copy in uint8 strips
  flat_tiles_gate.png(.json)  gate sidecar: every full tile of the flat master = the scaled tile
  ad_1080.png(.json)        FB square, cover(crop_box(flat)) + declared caption band
  story_1080x1920.png(.json)
  lungi_dressform.png       PROCEDURAL MOCK: deterministic 2-D UV warp + declared multiplicative
  worn_waistdown.png        shading layer (+ *_fabric.png / *_shade.png / .json for re-computation)
  video_master.mkv(.json)   10 s lossless FFV1: 6 s slow pan (gate-able frames) + 4 s
                            deterministic ripple (declared shading); video_preview.mp4 derived
Scale: unless a ruler measured it, px-per-cm is an ASSUMPTION and is written into every sidecar.
"""
import argparse
import json
import math
import os
import subprocess
import sys

import cv2
import numpy as np

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import twin_common as T  # noqa: E402

V2 = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), 'v2')
sys.path.insert(0, V2)
from common import cover, crop_box, load, draw_text  # noqa: E402  (the gate's own crop/scale code)
from PIL import Image, ImageDraw  # noqa: E402

DPI = 150
PX_PER_CM_OUT = DPI / 2.54
W_CM, L_CM = 110, 200
OUT_W, OUT_H = int(round(W_CM * PX_PER_CM_OUT)), int(round(L_CM * PX_PER_CM_OUT))   # 6496 x 11811
CAPTION_BG = (24, 22, 30)
CREAM = (250, 243, 228)


def save_png(arr_rgb, path):
    if not cv2.imwrite(path, cv2.cvtColor(arr_rgb, cv2.COLOR_RGB2BGR), [cv2.IMWRITE_PNG_COMPRESSION, 6]):
        raise IOError('write failed ' + path)
    if os.path.getsize(path) == 0:
        raise IOError('zero-size ' + path)


def side(path, data):
    with open(path + '.json', 'w') as f:
        json.dump(data, f, ensure_ascii=False, indent=1)


def scale_tile(tile_rgb, sx, sy):
    """Resize one repeat with wrap padding so the seam is resampled with its real neighbours."""
    th, tw = tile_rgb.shape[:2]
    p = 8
    pad = np.pad(tile_rgb, ((p, p), (p, p), (0, 0)), mode='wrap')
    ow, oh = int(round(tw * sx)), int(round(th * sy))
    kx, ky = ow / tw, oh / th            # exact period after rounding
    M = np.float32([[1 / kx, 0, (0.5 / kx) - 0.5 + p], [0, 1 / ky, (0.5 / ky) - 0.5 + p]])
    out = cv2.warpAffine(pad, M, (ow, oh), flags=cv2.INTER_LANCZOS4 | cv2.WARP_INVERSE_MAP, borderMode=cv2.BORDER_REFLECT)
    return out, (kx, ky)


def flat_render(tile_s, path):
    """Integer tiling in uint8 strips (no float full frame)."""
    th, tw = tile_s.shape[:2]
    out = np.empty((OUT_H, OUT_W, 3), np.uint8)
    cols = np.arange(OUT_W) % tw
    for y0 in range(0, OUT_H, 512):
        y1 = min(OUT_H, y0 + 512)
        rows = np.arange(y0, y1) % th
        out[y0:y1] = tile_s[rows][:, cols]
    save_png(out, path)
    return out


def caption(img, box, lines, sizes):
    d = ImageDraw.Draw(img)
    x, y, w, h = box
    d.rectangle((x, y, x + w, y + h), fill=CAPTION_BG)
    yy = y + 14
    for text, s in zip(lines, sizes):
        draw_text(d, (x + 28, yy), text, s, CREAM, 'sans')
        yy += int(s * 1.35)


def gate(master, out_json):
    gp = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'fabric_gate.py')
    r = subprocess.run(['python3', gp, '--master', master], capture_output=True, text=True, timeout=1800)
    try:
        res = json.loads(r.stdout.strip().splitlines()[-1])
    except Exception:  # noqa: BLE001
        res = {'passed': False, 'error': (r.stdout + r.stderr)[-800:]}
    with open(out_json, 'w') as f:
        json.dump(res, f, indent=1)
    return res


# ------------------------------------------------------------------ procedural mocks

def silhouette(H, W, y_top, y_bot, cx, prof):
    """prof(t) -> half width px. Returns mask (float 0..1, feathered), x_rel, t."""
    yy, xx = np.mgrid[0:H, 0:W].astype(np.float32)
    t = (yy - y_top) / float(y_bot - y_top)
    R = np.maximum(prof(np.clip(t, 0, 1)), 1.0)
    x_rel = (xx - cx) / R
    inside = (t >= 0) & (t <= 1) & (np.abs(x_rel) <= 1)
    m = inside.astype(np.float32)
    m = cv2.GaussianBlur(m, (0, 0), 1.2)
    return m, np.clip(x_rel, -1, 1), np.clip(t, 0, 1)


def lungi_maps(x_rel, t, folds, seed_phase, src_px_cm, circ_front_cm=100.0, height_cm=110.0, knot=None):
    """Deterministic UV: height -> fabric width (render x), around -> fabric length (render y)."""
    theta = np.arcsin(x_rel)
    amp = folds['amp_top'] * (1 - t) + folds['amp_bot'] * t
    k = folds['count']
    disp = amp * np.sin(k * theta + seed_phase)
    if knot is not None:                        # radiating folds converging on the front knot
        kx, ky, ka = knot
        r = np.sqrt((x_rel - kx) ** 2 + (t - ky) ** 2) + 1e-3
        disp += ka * np.exp(-r * 6) * np.sin(18 * np.arctan2(t - ky, x_rel - kx))
    th2 = theta + disp
    s_cm = 100.0 + th2 / math.pi * circ_front_cm      # centre of the 200 cm length
    u_cm = t * height_cm
    mx = (u_cm * src_px_cm).astype(np.float32)
    my = (s_cm * src_px_cm).astype(np.float32)
    # shading normal from the displaced cylinder: d(disp)/dtheta tilts the surface
    ddisp = amp * k * np.cos(k * theta + seed_phase)
    return mx, my, theta, ddisp


def shade_layer(x_rel, theta, ddisp, t, light=(-0.45, -0.35, 0.82), lo=0.55, hi=1.04):
    nx = np.sin(theta) + 0.35 * ddisp * np.cos(theta)
    nz = np.cos(theta)
    ny = -0.12 + 0.0 * t
    n = np.sqrt(nx * nx + ny * ny + nz * nz) + 1e-6
    L = np.array(light) / np.linalg.norm(light)
    lam = np.clip((nx * L[0] + ny * L[1] + nz * L[2]) / n, 0, 1)
    s = lo + (hi - lo) * (0.25 + 0.75 * lam)
    return np.clip(s, lo, hi).astype(np.float32)


def mock(flat_rgb, out_path, kind, code, scale_note):
    """kind: dressform | worn. Plain background, deterministic geometry, declared shading."""
    W, H = 1080, 1350
    src_px_cm = 10.0                                    # intermediate fabric resolution (px/cm)
    # fabric region used: full 110 cm width x 200 cm length, downscaled once (INTER_AREA)
    fab = cv2.resize(flat_rgb, (int(W_CM * src_px_cm), int(L_CM * src_px_cm)), interpolation=cv2.INTER_AREA)
    bg = np.zeros((H, W, 3), np.float32)
    g = np.linspace(0, 1, H)[:, None, None]
    bg[:] = (np.array([246, 240, 230]) * (1 - g) + np.array([226, 222, 216]) * g)
    canvas = Image.fromarray(bg.astype(np.uint8))
    d = ImageDraw.Draw(canvas)
    cx = W // 2
    if kind == 'dressform':
        y_top, y_bot = 400, 1230
        prof = lambda t: 205 + 55 * np.sin(np.clip(t, 0, 1) * math.pi * 0.55) - 12 * t      # noqa: E731
        # dress form torso + stand (neutral linen), drawn procedurally
        d.ellipse((cx - 30, 70, cx + 30, 110), fill=(120, 104, 86))
        d.rectangle((cx - 16, 100, cx + 16, 150), fill=(120, 104, 86))
        d.polygon([(cx - 150, 160), (cx + 150, 160), (cx + 205, 250), (cx + 175, 330), (cx + 200, 405),
                   (cx - 200, 405), (cx - 175, 330), (cx - 205, 250)], fill=(214, 200, 178))
        d.rectangle((cx - 9, y_bot - 4, cx + 9, 1300), fill=(90, 78, 64))
        d.polygon([(cx - 120, 1330), (cx + 120, 1330), (cx + 14, 1296), (cx - 14, 1296)], fill=(90, 78, 64))
        folds = {'count': 9, 'amp_top': 0.10, 'amp_bot': 0.035}
        knot = None
    else:
        y_top, y_bot = 210, 1160
        prof = lambda t: 150 + 70 * np.sin(np.clip(t, 0, 1) * math.pi * 0.9) - 25 * t ** 2      # noqa: E731
        d.rectangle((cx - 175, 120, cx + 175, 235), fill=(236, 236, 238))       # plain top hem (no person shown above waist)
        for sx in (-1, 1):                                                       # feet/sandals hint below the hem
            fx = cx + sx * 70
            d.ellipse((fx - 48, 1225, fx + 48, 1290), fill=(196, 156, 128))
            d.rectangle((fx - 50, 1262, fx + 50, 1300), fill=(84, 60, 44))
        folds = {'count': 7, 'amp_top': 0.07, 'amp_bot': 0.03}
        knot = (0.12, 0.05, 0.10)
    m, x_rel, t = silhouette(H, W, y_top, y_bot, cx, prof)
    mx, my, theta, ddisp = lungi_maps(x_rel, t, folds, 0.7, src_px_cm, knot=knot)
    fabric = cv2.remap(fab, mx, my, interpolation=cv2.INTER_LINEAR, borderMode=cv2.BORDER_WRAP)
    shade = shade_layer(x_rel, theta, ddisp, t)
    shaded = np.clip(fabric.astype(np.float32) * shade[..., None] + 0.5, 0, 255)
    base = np.asarray(canvas).astype(np.float32)
    out = base * (1 - m[..., None]) + shaded * m[..., None]
    img = Image.fromarray(out.astype(np.uint8))
    caption(img, (0, H - 160, W, 160), [f'{code} · PROCEDURAL MOCK, not photoreal',
                                        'print = real pixels of our photo (tile)',
                                        'shading = declared overlay · ' + scale_note],
            [32, 26, 26])
    img.save(out_path, format='PNG', compress_level=6)
    base_name = out_path[:-4]
    save_png(fabric, base_name + '_fabric.png')
    cv2.imwrite(base_name + '_shade.png', np.clip(shade * 200, 0, 255).astype(np.uint8))
    side(out_path, {'kind': kind, 'label': 'procedural mock, not photoreal', 'size': [W, H], 'src_px_per_cm': src_px_cm,
                    'silhouette': {'y_top': y_top, 'y_bot': y_bot, 'cx': cx}, 'folds': folds, 'knot': knot,
                    'mapping': 'height -> fabric width (110 cm), around -> fabric length (front 100 cm of 200 cm)',
                    'overlays': ['multiplicative shading layer (*_shade.png, value/200)', 'background', 'form/feet shapes',
                                 'caption band'], 'fabric_layer': base_name + '_fabric.png'})
    for p in (out_path, base_name + '_fabric.png', base_name + '_shade.png'):
        if os.path.getsize(p) == 0:
            raise IOError('zero-size ' + p)
    return fabric, shade, m


# ------------------------------------------------------------------------- video

def video(flat_path, flat_rgb, out_dir, code, scale_note, fps=30):
    W, H = 1080, 1920
    src = load(flat_path)
    bw, bh = int(round(40 * PX_PER_CM_OUT)), int(round(71.1 * PX_PER_CM_OUT))      # 40 x 71 cm window
    x0, y0 = int(0.18 * OUT_W), int(0.10 * OUT_H)
    master = os.path.join(out_dir, 'video_master.mkv')
    cmd = ['ffmpeg', '-v', 'error', '-y', '-f', 'rawvideo', '-pix_fmt', 'rgb24', '-s', f'{W}x{H}', '-r', str(fps), '-i', '-',
           '-c:v', 'ffv1', '-level', '3', '-pix_fmt', 'bgr0', master]
    p = subprocess.Popen(cmd, stdin=subprocess.PIPE)
    regions, procedural = [], []
    cap_box = (0, H - 150, W, 150)
    n_pan, n_total = 180, 300
    last_box = None
    base = None
    for n in range(n_total):
        if n < n_pan:
            box = (x0 + 2 * n, y0 + 6 * n, bw, bh)                 # integer source steps: 2,6 px/frame
            fr = cover(crop_box(src, box), W, H)
            last_box = box
            if n in (0, 45, 90, 135, n_pan - 1):
                regions.append({'n': n, 'src': flat_path, 'src_box': list(box), 'out_box': [0, 0, W, H],
                                'exclude': [list(cap_box)]})
        else:
            if base is None:
                base = np.asarray(cover(crop_box(src, last_box), W, H)).astype(np.uint8)
            tt = (n - n_pan) / fps
            yy, xx = np.mgrid[0:H, 0:W].astype(np.float32)
            env = 0.5 - 0.5 * np.cos(np.clip(tt / 1.0, 0, 1) * math.pi)              # ease in over 1 s
            ph = 2 * math.pi * (xx / 420.0 + yy / 900.0) - 2 * math.pi * 0.6 * tt
            dy = (14.0 * env * np.sin(ph)).astype(np.float32)
            dx = (6.0 * env * np.cos(ph * 0.7)).astype(np.float32)
            warped = cv2.remap(base, xx + dx, yy + dy, interpolation=cv2.INTER_LINEAR, borderMode=cv2.BORDER_REFLECT)
            shade = (1.0 + 0.10 * env * np.cos(ph)).astype(np.float32)
            fr = Image.fromarray(np.clip(warped.astype(np.float32) * shade[..., None] + 0.5, 0, 255).astype(np.uint8))
            if n in (n_pan, n_pan + 60, n_total - 1):
                procedural.append({'n': n, 't': round(tt, 3), 'wave': {'amp_y': 14, 'amp_x': 6, 'lambda_x': 420, 'lambda_y': 900,
                                                                       'cycles_per_s': 0.6}, 'shade': '1 + 0.10*env*cos(phase)'})
        img = fr.copy()
        caption(img, cap_box, [f'{code} · exact print, real pixels' if n < n_pan else f'{code} · procedural fabric motion (mock)',
                               'PREVIEW · not published · ' + scale_note], [34, 26])
        p.stdin.write(np.asarray(img.convert('RGB'), np.uint8).tobytes())
    p.stdin.close()
    if p.wait() != 0 or os.path.getsize(master) == 0:
        raise IOError('ffmpeg master failed')
    side(master, {'size': [W, H], 'fps': fps, 'frames': n_total, 'fabric_regions': regions, 'procedural_frames': procedural,
                  'min_text_px': 26, 'note': 'frames 0-179 = cover(crop_box(flat)) pan (gate-able); 180-299 = deterministic '
                                             'ripple remap + multiplicative shading (declared overlay, not gate-able)'})
    prev = os.path.join(out_dir, 'video_preview.mp4')
    r = subprocess.run(['ffmpeg', '-v', 'error', '-y', '-i', master, '-c:v', 'libx264', '-pix_fmt', 'yuv420p', '-crf', '18',
                        '-movflags', '+faststart', prev], capture_output=True, text=True, timeout=600)
    if r.returncode != 0 or os.path.getsize(prev) == 0:
        raise IOError('ffmpeg preview failed ' + r.stderr[-300:])
    return master, prev


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--tile', required=True)
    ap.add_argument('--out-dir', required=True)
    ap.add_argument('--code', required=True)
    ap.add_argument('--px-per-cm', type=float, required=True, help='source photo px per cm of fabric')
    ap.add_argument('--scale-basis', required=True)
    ap.add_argument('--tier', default='?')
    ap.add_argument('--skip-video', action='store_true')
    args = ap.parse_args()
    T.guard('twin_render')
    os.makedirs(args.out_dir, exist_ok=True)
    tile = cv2.cvtColor(cv2.imread(args.tile, cv2.IMREAD_COLOR), cv2.COLOR_BGR2RGB)
    k = PX_PER_CM_OUT / args.px_per_cm
    tile_s, (kx, ky) = scale_tile(tile, k, k)
    th, tw = tile_s.shape[:2]
    rep_cm = (tile.shape[1] / args.px_per_cm, tile.shape[0] / args.px_per_cm)
    scale_note = f'scale ASSUMED ({args.px_per_cm:g} px/cm)'
    tpath = os.path.join(args.out_dir, 'tile_scaled_150dpi.png')
    save_png(tile_s, tpath)
    flat_path = os.path.join(args.out_dir, 'flat_110x200_150dpi.png')
    flat = flat_render(tile_s, flat_path)
    # gate sidecar for the flat master: every full tile must equal the scaled tile (block of k x k tiles)
    kk = max(1, int(math.ceil(600 / max(1, min(tw, th)))))
    block = np.tile(tile_s, (kk, kk, 1))
    bpath = os.path.join(args.out_dir, 'gate_block.png')
    save_png(block, bpath)
    bh_, bw_ = block.shape[:2]
    regs = []
    for yy in range(0, OUT_H - bh_ + 1, bh_):
        for xx in range(0, OUT_W - bw_ + 1, bw_):
            regs.append({'src': bpath, 'src_box': [0, 0, bw_, bh_], 'out_box': [xx, yy, bw_, bh_]})
    flat_gate = flat_path
    side(flat_gate, {'size': [OUT_W, OUT_H], 'fabric_regions': regs, 'cm': [W_CM, L_CM], 'dpi': DPI, 'tile': args.tile,
                     'tile_scaled': tpath, 'scale_assumed': True, 'scale_basis': args.scale_basis, 'px_per_cm_source': args.px_per_cm,
                     'repeat_cm_assumed': [round(rep_cm[0], 2), round(rep_cm[1], 2)], 'tier': args.tier,
                     'tile_px': [tile.shape[1], tile.shape[0]], 'tile_scaled_px': [tw, th], 'scale_factor': [kx, ky],
                     'method': 'wrap-padded Lanczos scale of one repeat, then integer tiling (uint8 strips)'})
    prev = cv2.resize(flat, (int(OUT_W * 1100 / OUT_H), 1100), interpolation=cv2.INTER_AREA)
    cv2.imwrite(os.path.join(args.out_dir, 'flat_preview.jpg'), cv2.cvtColor(prev, cv2.COLOR_RGB2BGR), [cv2.IMWRITE_JPEG_QUALITY, 90])

    src = load(flat_path)
    outs = {}
    # FB square ad: 40 x 40 cm window
    box = (int(0.30 * OUT_W), int(0.30 * OUT_H), int(round(40 * PX_PER_CM_OUT)), int(round(40 * PX_PER_CM_OUT)))
    ad = cover(crop_box(src, box), 1080, 1080)
    cap = (0, 930, 1080, 150)
    caption(ad, cap, [f'{args.code} · Japanese cotton print', f'exact print, our photo · {scale_note}'], [36, 26])
    ap_ = os.path.join(args.out_dir, 'ad_1080.png')
    ad.save(ap_, format='PNG', compress_level=6)
    side(ap_, {'size': [1080, 1080], 'fabric_regions': [{'src': flat_path, 'src_box': list(box), 'out_box': [0, 0, 1080, 1080],
                                                          'exclude': [list(cap)]}], 'min_text_px': 26, 'tier': args.tier})
    outs['ad'] = ap_
    # story: 40 x 71 cm window, top + bottom bands
    box = (int(0.05 * OUT_W), int(0.45 * OUT_H), int(round(40 * PX_PER_CM_OUT)), int(round(71.1 * PX_PER_CM_OUT)))
    st = cover(crop_box(src, box), 1080, 1920)
    top, bot = (0, 0, 1080, 150), (0, 1740, 1080, 180)
    caption(st, top, [f'{args.code}', 'Japanese cotton print'], [44, 28])
    caption(st, bot, ['exact print · real pixels from our photo', f'{scale_note} · PREVIEW'], [32, 26])
    sp = os.path.join(args.out_dir, 'story_1080x1920.png')
    st.save(sp, format='PNG', compress_level=6)
    side(sp, {'size': [1080, 1920], 'fabric_regions': [{'src': flat_path, 'src_box': list(box), 'out_box': [0, 0, 1080, 1920],
                                                         'exclude': [list(top), list(bot)]}], 'min_text_px': 26, 'tier': args.tier})
    outs['story'] = sp
    del src
    for kind, name in (('dressform', 'lungi_dressform.png'), ('worn', 'worn_waistdown.png')):
        mock(flat, os.path.join(args.out_dir, name), kind, args.code, scale_note)
        outs[kind] = os.path.join(args.out_dir, name)
    if not args.skip_video:
        outs['video_master'], outs['video_preview'] = video(flat_path, flat, args.out_dir, args.code, scale_note)
    del flat
    # gates (circular: they rebuild expected pixels from the same flat render / tile)
    gates = {}
    for name, path in (('flat', flat_gate), ('ad', outs['ad']), ('story', outs['story'])):
        gates[name] = gate(path, path + '.gate.json')
    if 'video_master' in outs:
        gates['video_pan'] = gate(outs['video_master'], outs['video_master'] + '.gate.json')
    print(json.dumps({'outputs': outs, 'gates': {k: {'passed': v.get('passed'), 'summary': v.get('summary'), 'error': v.get('error')}
                                                  for k, v in gates.items()}}))
    return 0


if __name__ == '__main__':
    sys.exit(main())
