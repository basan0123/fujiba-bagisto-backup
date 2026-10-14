#!/usr/bin/env python3
"""INFORMATION-ONLY comparison of an AI-generated garment pattern vs the real fabric.
Not a gate: AI-drawn fabric is never trusted and nothing here can 'pass' it.

  ai_pattern_compare.py --source real_crop.jpg --ai ai.jpg --ai-crop x,y,w,h --outdir DIR --name NAME
                        [--label "Styling concept – actual fabric may differ"]

Writes NAME_labelled.png (copy of the AI image with a code-rendered disclaimer),
NAME_compare.png (side by side + palette swatches) and prints JSON:
  dominant colours (k-means k=6, Lab) of each, greedy-matched pairs with CIEDE2000,
  share-weighted mean ΔE00, ORB matches (ratio test) and RANSAC inliers.
"""
import argparse, json, os, sys
import cv2
import numpy as np
from PIL import Image, ImageDraw, ImageFont

HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, HERE)
import heavy_guard  # noqa: E402
from fabric_gate import ciede2000  # noqa: E402

FONT = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'
FONT_R = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf'


def lab_f(bgr):
    """float Lab (L 0-100, a/b signed) from 8-bit BGR."""
    lab = cv2.cvtColor(bgr.astype(np.float32) / 255.0, cv2.COLOR_BGR2LAB)
    return lab


def palette(bgr, k=6):
    small = cv2.resize(bgr, (200, int(200 * bgr.shape[0] / bgr.shape[1])), interpolation=cv2.INTER_AREA)
    lab = lab_f(small).reshape(-1, 3)
    crit = (cv2.TERM_CRITERIA_EPS + cv2.TERM_CRITERIA_MAX_ITER, 50, 0.2)
    cv2.setRNGSeed(7)
    _, lbl, cen = cv2.kmeans(lab.astype(np.float32), k, None, crit, 5, cv2.KMEANS_PP_CENTERS)
    share = np.bincount(lbl.ravel(), minlength=k) / lbl.size
    o = np.argsort(-share)
    return cen[o], share[o]


def lab_to_bgr(lab):
    px = np.array(lab, np.float32).reshape(1, 1, 3)
    return (cv2.cvtColor(px, cv2.COLOR_LAB2BGR)[0, 0] * 255).clip(0, 255).astype(int).tolist()


def orb_stats(a, b):
    ga = cv2.cvtColor(a, cv2.COLOR_BGR2GRAY); gb = cv2.cvtColor(b, cv2.COLOR_BGR2GRAY)
    orb = cv2.ORB_create(3000)
    ka, da = orb.detectAndCompute(ga, None); kb, db = orb.detectAndCompute(gb, None)
    if da is None or db is None:
        return {'kp_source': len(ka or []), 'kp_ai': len(kb or []), 'good_matches': 0, 'ransac_inliers': 0}
    m = cv2.BFMatcher(cv2.NORM_HAMMING).knnMatch(da, db, k=2)
    good = [x[0] for x in m if len(x) == 2 and x[0].distance < 0.75 * x[1].distance]
    inl = 0
    if len(good) >= 8:
        pa = np.float32([ka[g.queryIdx].pt for g in good]); pb = np.float32([kb[g.trainIdx].pt for g in good])
        _, mask = cv2.findHomography(pa, pb, cv2.RANSAC, 5.0)
        inl = int(mask.sum()) if mask is not None else 0
    return {'kp_source': len(ka), 'kp_ai': len(kb), 'good_matches': len(good), 'ransac_inliers': inl}


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--source', required=True); ap.add_argument('--ai', required=True)
    ap.add_argument('--ai-crop', required=True); ap.add_argument('--outdir', required=True)
    ap.add_argument('--name', required=True)
    ap.add_argument('--label', default='Styling concept – actual fabric may differ')
    a = ap.parse_args()
    heavy_guard.acquire('ai_pattern_compare')
    src = cv2.imread(a.source); ai = cv2.imread(a.ai)
    x, y, w, h = map(int, a.ai_crop.split(','))
    skirt = ai[y:y + h, x:x + w]
    p = lambda s: os.path.join(a.outdir, f'{a.name}_{s}')

    # labelled copy (the AI original is kept untouched)
    pil = Image.open(a.ai).convert('RGBA')
    f = ImageFont.truetype(FONT_R, 24)
    d = ImageDraw.Draw(pil); tw = d.textlength(a.label, font=f)
    ov = Image.new('RGBA', pil.size, (0, 0, 0, 0)); od = ImageDraw.Draw(ov)
    bx = [pil.width - int(tw) - 44, pil.height - 56, pil.width - 14, pil.height - 14]
    od.rounded_rectangle(bx, radius=10, fill=(20, 20, 20, 170))
    od.text((bx[0] + 15, bx[1] + 8), a.label, font=f, fill=(255, 255, 255, 255))
    Image.alpha_composite(pil, ov).convert('RGB').save(p('labelled.png'))

    cs, ss = palette(src); ca, sa = palette(skirt)
    # greedy match each source colour to the nearest unused AI colour
    pairs, used = [], set()
    for i in range(len(cs)):
        de = [float(ciede2000(cs[i].reshape(1, 1, 3), ca[j].reshape(1, 1, 3)).ravel()[0]) if j not in used else 1e9
              for j in range(len(ca))]
        j = int(np.argmin(de)); used.add(j)
        pairs.append({'source_hex': '#%02x%02x%02x' % tuple(lab_to_bgr(cs[i])[::-1]),
                      'source_share': round(float(ss[i]), 3),
                      'ai_hex': '#%02x%02x%02x' % tuple(lab_to_bgr(ca[j])[::-1]),
                      'ai_share': round(float(sa[j]), 3), 'de00': round(de[j], 2)})
    wmean = float(sum(q['de00'] * q['source_share'] for q in pairs))
    # ORB at matched scale: resize skirt crop so its width ~ source width
    sk = cv2.resize(skirt, (src.shape[1], int(src.shape[1] * skirt.shape[0] / skirt.shape[1])), interpolation=cv2.INTER_AREA)
    orb = orb_stats(src, sk)
    # control: ORB of source vs itself flipped (unrelated layout, same motifs) for scale of numbers
    orb_ctrl = orb_stats(src, cv2.flip(src, 1))

    # side-by-side PNG
    H = 700
    rs = lambda im: cv2.resize(im, (int(im.shape[1] * H / im.shape[0]), H), interpolation=cv2.INTER_AREA)
    L, R = rs(src), rs(skirt)
    pad = np.full((H, 20, 3), 245, np.uint8)
    top = np.hstack([L, pad, R])
    sw = np.full((150, top.shape[1], 3), 245, np.uint8)
    for k, q in enumerate(pairs):
        cx = 20 + k * 110
        cv2.rectangle(sw, (cx, 10), (cx + 45, 55), lab_to_bgr(cs[k]), -1)
        j = [ii for ii in range(len(ca)) if '#%02x%02x%02x' % tuple(lab_to_bgr(ca[ii])[::-1]) == q['ai_hex']][0]
        cv2.rectangle(sw, (cx + 50, 10), (cx + 95, 55), lab_to_bgr(ca[j]), -1)
        cv2.putText(sw, f"dE {q['de00']:.1f}", (cx, 80), cv2.FONT_HERSHEY_SIMPLEX, 0.5, (40, 40, 40), 1, cv2.LINE_AA)
    cv2.putText(sw, f"weighted dE00 {wmean:.1f} | ORB good {orb['good_matches']} inliers {orb['ransac_inliers']}"
                f" (flipped-self control: {orb_ctrl['good_matches']}/{orb_ctrl['ransac_inliers']})",
                (20, 120), cv2.FONT_HERSHEY_SIMPLEX, 0.55, (40, 40, 40), 1, cv2.LINE_AA)
    hdr = np.full((50, top.shape[1], 3), 245, np.uint8)
    cv2.putText(hdr, 'REAL fabric', (10, 32), cv2.FONT_HERSHEY_SIMPLEX, 0.75, (30, 30, 30), 2, cv2.LINE_AA)
    cv2.putText(hdr, 'AI concept', (L.shape[1] + 30, 32),
                cv2.FONT_HERSHEY_SIMPLEX, 0.75, (20, 20, 160), 2, cv2.LINE_AA)
    cv2.imwrite(p('compare.png'), np.vstack([hdr, top, sw]))
    out = {'information_only': True, 'gate_claimed': False, 'ai_crop': [x, y, w, h],
           'palette_pairs': pairs, 'weighted_mean_de00': round(wmean, 2),
           'orb': orb, 'orb_control_flipped_self': orb_ctrl,
           'files': {'labelled': p('labelled.png'), 'compare': p('compare.png')}}
    with open(p('compare.json'), 'w') as fh:
        json.dump(out, fh, indent=2)
    print(json.dumps(out))


if __name__ == '__main__':
    main()
