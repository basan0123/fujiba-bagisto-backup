#!/usr/bin/env python3
"""Composite N real fabric crops into N green placeholder panels of a generated scene.

  scene_composite_multi.py --scene raw.png --spec spec.json --outdir DIR --name NAME

spec.json: {"pieces": [{"source": path, "code": "FV-89-B", "price": 1595,
                        "exclude": [[x,y,w,h],...]}, ...]}   (assigned to panels left->right)

* Panels: green mask -> connected components. A component whose region fills its
  4-vertex approximation (>= 0.95) is a fully visible TOP panel. A component that
  is partly hidden (an overlap) gets its quad estimated from the convex hull and
  only its VISIBLE green pixels are replaced (z-order: occluded first, top last).
  Components that cannot be resolved (merged panels / not 4 corners) => reject.
* Crops: wm_detect.wm_free_crop() picks the largest watermark-free fabric rectangle
  of the panel's aspect (template + OCR detectors, OCR re-check on the crop).
* Fabric pixels are only resampled (INTER_AREA downscale + Lanczos perspective warp).
  No tint/shade/blur on fabric. Labels (code + price) are drawn by this code on the
  paper below each piece and are verified not to touch any fabric pixel.
* Gate a (fabric_gate.py, expected warp vs final) and gate b (inverse warp vs source
  crop) run on EVERY region; any failure => exit 3, image marked rejected.
"""
import argparse, json, os, subprocess, sys
import cv2
import numpy as np
from PIL import Image, ImageDraw, ImageFont

HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, HERE)
import heavy_guard  # noqa: E402
import wm_detect    # noqa: E402

HSV_LO = np.array([40, 120, 80]); HSV_HI = np.array([90, 255, 255])
FONT = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'


def green_mask(img):
    return cv2.inRange(cv2.cvtColor(img, cv2.COLOR_BGR2HSV), HSV_LO, HSV_HI)


def order_quad(p):
    p = p.reshape(-1, 2).astype(np.float32)
    s = p.sum(1); d = np.diff(p, axis=1).ravel()
    return np.array([p[s.argmin()], p[d.argmin()], p[s.argmax()], p[d.argmax()]], np.float32)


def quad_of(contour):
    """4-vertex approximation of a contour (or its hull) by growing epsilon."""
    peri = cv2.arcLength(contour, True)
    for k in np.arange(0.01, 0.08, 0.005):
        ap = cv2.approxPolyDP(contour, k * peri, True)
        if len(ap) == 4 and cv2.isContourConvex(ap):
            return ap
        if len(ap) < 4:
            break
    return None


def find_panels(scene, n):
    H, W = scene.shape[:2]
    g = cv2.morphologyEx(green_mask(scene), cv2.MORPH_OPEN, np.ones((5, 5), np.uint8))
    cs, _ = cv2.findContours(g, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    cs = sorted([c for c in cs if cv2.contourArea(c) > 0.02 * W * H], key=cv2.contourArea, reverse=True)
    if len(cs) != n:
        return None, f'expected {n} green panels, found {len(cs)} (merged or missing)'
    panels = []
    for c in cs:
        vis = np.zeros((H, W), np.uint8); cv2.drawContours(vis, [c], -1, 255, cv2.FILLED)
        vis &= g
        ap = quad_of(c)
        top = ap is not None and cv2.contourArea(c) / max(cv2.contourArea(ap), 1) >= 0.95
        if not top:
            ap = quad_of(cv2.convexHull(c))   # occluded: estimate full quad from hull
            if ap is None:
                return None, 'panel not resolvable to a 4-corner quad'
        q = order_quad(ap)
        pts = q.astype(np.int32)
        if (pts[:, 0] <= 1).any() or (pts[:, 1] <= 1).any() or (pts[:, 0] >= W - 2).any() or (pts[:, 1] >= H - 2).any():
            return None, 'panel touches image border'
        panels.append({'quad': q, 'visible': vis, 'top': bool(top),
                       'fill': round(cv2.contourArea(c) / max(cv2.contourArea(ap), 1), 3),
                       'area_frac': round(cv2.contourArea(c) / (W * H), 4)})
    panels.sort(key=lambda p: p['quad'][:, 0].mean())   # left -> right
    return panels, None


def gate(src, cand, mask, out_json):
    r = subprocess.run([sys.executable, os.path.join(HERE, 'fabric_gate.py'), '--source', src,
                        '--candidate', cand, '--mask', mask], capture_output=True, text=True)
    try:
        res = json.loads(r.stdout.strip().splitlines()[-1])
    except Exception:
        res = {'passed': False, 'error': (r.stdout + r.stderr)[-400:]}
    with open(out_json, 'w') as f:
        json.dump(res, f)
    return res


def draw_label(img_bgr, text_lines, anchor_xy, forbid):
    """Code-rendered label on a soft paper-white pill, centred at anchor (top-centre)."""
    pil = Image.fromarray(cv2.cvtColor(img_bgr, cv2.COLOR_BGR2RGB)).convert('RGBA')
    font = ImageFont.truetype(FONT, 26)
    font2 = ImageFont.truetype(FONT, 22)
    d = ImageDraw.Draw(pil)
    w1 = d.textlength(text_lines[0], font=font); w2 = d.textlength(text_lines[1], font=font2)
    bw, bh = int(max(w1, w2) + 36), 74
    x0, y0 = int(anchor_xy[0] - bw / 2), int(anchor_xy[1])
    box = [x0, y0, x0 + bw, y0 + bh]
    H, W = forbid.shape
    if box[0] < 0 or box[2] > W or box[3] > H or forbid[box[1]:box[3], box[0]:box[2]].any():
        return None, box
    ov = Image.new('RGBA', pil.size, (0, 0, 0, 0)); od = ImageDraw.Draw(ov)
    od.rounded_rectangle(box, radius=14, fill=(255, 252, 246, 225))
    od.text((anchor_xy[0] - w1 / 2, y0 + 8), text_lines[0], font=font, fill=(40, 34, 30, 255))
    od.text((anchor_xy[0] - w2 / 2, y0 + 42), text_lines[1], font=font2, fill=(150, 30, 30, 255))
    pil = Image.alpha_composite(pil, ov).convert('RGB')
    return cv2.cvtColor(np.array(pil), cv2.COLOR_RGB2BGR), box


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--scene', required=True); ap.add_argument('--spec', required=True)
    ap.add_argument('--outdir', required=True); ap.add_argument('--name', required=True)
    a = ap.parse_args()
    heavy_guard.acquire('scene_composite_multi')
    spec = json.load(open(a.spec))
    scene = cv2.imread(a.scene); H, W = scene.shape[:2]
    p = lambda s: os.path.join(a.outdir, f'{a.name}_{s}')
    out = {'scene': a.scene, 'scene_size': [W, H], 'regions': []}

    def finish(code):
        with open(p('composite.json'), 'w') as f:
            json.dump(out, f, indent=2)
        print(json.dumps(out)); sys.exit(code)

    panels, err = find_panels(scene, len(spec['pieces']))
    if err:
        out['rejected'] = err; finish(3)

    comp = scene.copy()
    fabric_all = np.zeros((H, W), np.uint8)
    order = sorted(range(len(panels)), key=lambda i: panels[i]['top'])   # occluded first
    regions = [None] * len(panels)
    for i in order:
        pan, piece = panels[i], spec['pieces'][i]
        q = pan['quad']
        qw = (np.linalg.norm(q[1] - q[0]) + np.linalg.norm(q[2] - q[3])) / 2
        qh = (np.linalg.norm(q[3] - q[0]) + np.linalg.norm(q[2] - q[1])) / 2
        src = cv2.imread(piece['source'])
        rect, wm, recheck = wm_detect.wm_free_crop(src, qw / qh, exclude=piece.get('exclude', []))
        reg = {'code': piece['code'], 'source': piece['source'], 'quad': q.round(1).tolist(),
               'top_panel': pan['top'], 'panel_fill': pan['fill'], 'panel_area_frac': pan['area_frac'],
               'watermark_in_source': wm['watermark_present'], 'watermark_boxes': wm['boxes'],
               'crop_rect': rect, 'crop_recheck': recheck}
        if not rect:
            reg['rejected'] = 'no watermark-free crop of panel aspect'
            out['regions'].append(reg); out['rejected'] = reg['rejected']; finish(3)
        x, y, cw, ch = rect
        crop = src[y:y + ch, x:x + cw]
        ew, eh = max(int(round(qw)), 8), max(int(round(qh)), 8)
        reg['effective_res'] = [ew, eh]; reg['downscale'] = round(cw / ew, 3)
        small = cv2.resize(crop, (ew, eh), interpolation=cv2.INTER_AREA)
        rq = np.array([[0, 0], [ew - 1, 0], [ew - 1, eh - 1], [0, eh - 1]], np.float32)
        M = cv2.getPerspectiveTransform(rq, q)
        warped = cv2.warpPerspective(small, M, (W, H), flags=cv2.INTER_LANCZOS4, borderMode=cv2.BORDER_REPLICATE)
        qm = np.zeros((H, W), np.uint8); cv2.fillConvexPoly(qm, q.astype(np.int32), 255)
        qm = cv2.dilate(qm, np.ones((5, 5), np.uint8))   # cover 2px green fringe
        if pan['top']:
            region = qm
        else:   # only the visible part of an occluded panel (+fringe), never over another panel
            region = cv2.dilate(pan['visible'], np.ones((5, 5), np.uint8)) & qm
        comp[region > 0] = warped[region > 0]
        fabric_all |= region
        cv2.imwrite(p(f'r{i}_expected.png'), warped); cv2.imwrite(p(f'r{i}_mask.png'), region)
        cv2.imwrite(p(f'r{i}_src_small.png'), small)
        regions[i] = (reg, M, rq, ew, eh, region)

    # occluded regions may have been partly covered by a later top panel: final masks
    for i in order:
        reg, M, rq, ew, eh, region = regions[i]
        later = [regions[j][5] for j in order[order.index(i) + 1:]]
        final = region.copy()
        for m in later:
            final &= ~m
        cv2.imwrite(p(f'r{i}_mask.png'), final)
        regions[i] = (reg, M, rq, ew, eh, final)

    spill = int(np.count_nonzero(green_mask(comp)[fabric_all == 0]))
    out['green_spill_px_outside'] = spill
    if spill > 50:
        out['rejected'] = f'green spill outside fabric: {spill}px'; finish(3)

    # labels below each piece, on paper only
    forbid = cv2.dilate(fabric_all, np.ones((9, 9), np.uint8)) > 0
    labelled = comp.copy()
    for i in range(len(panels)):
        reg = regions[i][0]; q = panels[i]['quad']; piece = spec['pieces'][i]
        anchor = (float(q[2:4, 0].mean()), float(q[2:4, 1].max()) + 22)
        lines = [piece['code'], f"¥{int(round(piece['price'])):,}"]
        res, box = draw_label(labelled, lines, anchor, forbid)
        if res is None:
            reg['label'] = {'placed': False, 'box': box}
            out['rejected'] = f"label for {piece['code']} would touch fabric/border"; out['regions'] = [r[0] for r in regions]; finish(3)
        labelled = res; reg['label'] = {'placed': True, 'box': box, 'text': lines}

    final_path = p('final.png'); cv2.imwrite(final_path, labelled)
    all_pass = True
    for i in range(len(panels)):
        reg, M, rq, ew, eh, final = regions[i]
        er = cv2.erode(final, np.ones((3, 3), np.uint8))
        cv2.imwrite(p(f'r{i}_gatemask.png'), er)
        # gate-a reference: the independent warp inside this region; outside it the
        # un-labelled composite, so the gate's bbox-based pattern checks compare fabric
        # with fabric and paper with paper (not the warp's border-replicate smear).
        ref = comp.copy()
        warped_i = cv2.imread(p(f'r{i}_expected.png'))
        ref[final > 0] = warped_i[final > 0]
        cv2.imwrite(p(f'r{i}_gate_a_ref.png'), ref)
        reg['gate_a'] = gate(p(f'r{i}_gate_a_ref.png'), final_path, p(f'r{i}_gatemask.png'), p(f'r{i}_gate_a.json'))
        Minv = np.linalg.inv(M)
        inv = cv2.warpPerspective(labelled, Minv, (ew, eh), flags=cv2.INTER_LANCZOS4)
        im = cv2.warpPerspective(final, Minv, (ew, eh), flags=cv2.INTER_NEAREST)
        im = cv2.erode(im, np.ones((7, 7), np.uint8)); im[:3] = 0; im[-3:] = 0; im[:, :3] = 0; im[:, -3:] = 0
        cv2.imwrite(p(f'r{i}_inv_candidate.png'), inv); cv2.imwrite(p(f'r{i}_inv_mask.png'), im)
        reg['gate_b'] = gate(p(f'r{i}_src_small.png'), p(f'r{i}_inv_candidate.png'), p(f'r{i}_inv_mask.png'), p(f'r{i}_gate_b.json'))
        reg['verdict'] = 'pass' if reg['gate_a'].get('passed') and reg['gate_b'].get('passed') else 'fail'
        all_pass &= reg['verdict'] == 'pass'
    out['regions'] = [r[0] for r in regions]
    out['final'] = final_path
    out['verdict'] = 'pass' if all_pass else 'rejected'
    if not all_pass:
        out['rejected'] = 'one or more fabric regions failed gate a/b'
        os.replace(final_path, p('final_REJECTED.png')); out['final'] = p('final_REJECTED.png')
        finish(3)
    finish(0)


if __name__ == '__main__':
    main()
