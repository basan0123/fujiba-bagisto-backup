#!/usr/bin/env python3
"""Reusable "AI styling concept" step (ws/h, round 4). ONE product per invocation.

  styling_concept.py run --source PRODUCT.jpg --outdir PREVIEW_DIR --name NAME --prompt-file P.txt
                         [--sub-cap-jpy 60] [--max-calls 2] [--product-id N] [--sku SKU]
  styling_concept.py qc-only --source PRODUCT.jpg --ai AI.jpg --outdir DIR --name NAME

Chain:
  1. refs = full source photo + largest watermark-free crop (wm_detect.wm_free_crop, 3:4)
  2. Nano Banana Pro (gemini_poc.py generate-pro, 9:16) — budget enforced there (round cap ¥200,
     total ¥3,000 from fi_ai_usage) AND here (sub-cap / max calls for this NAME prefix)
  3. colour-correct the AI garment pixels ONLY (Lab CDF/LUT match to the clean source crop;
     concept_eval.hist_match). Real photo pixels are only read.
  4. auto QC (recorded either way):  FAIL if weighted ΔE00 (centre of garment) > 4.0
                                     OR ORB RANSAC inliers vs the clean crop < 250
  5. side-by-side: left = real product photo, right = corrected concept, label rendered by OUR
     code (EN / JA / Burmese Unicode). A QC failure is saved with a REJECTED banner and _REJECTED
     suffix — never deleted. Output goes to the preview dir only; nothing is published.
"""
import argparse, json, os, subprocess, sys, unicodedata

HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, HERE)
import heavy_guard  # noqa: E402

DE_MAX = 4.0
INLIERS_MIN = 250
LABEL_EN = 'AI styling concept'
LABEL_JA = '実物は左の写真'
# "AI styling concept — the real fabric is the photo on the left" (Unicode Burmese, not Zawgyi)
LABEL_MY = 'AI ဖြင့် ပြုလုပ်ထားသော ဝတ်ဆင်ပုံ နမူနာ — အထည်အစစ်မှာ ဘယ်ဘက်ရှိ ဓာတ်ပုံ ဖြစ်ပါသည်'
FONT_LATIN = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'
FONT_JA = '/usr/share/fonts/opentype/ipafont-gothic/ipag.ttf'
FONT_MY = '/usr/share/fonts/truetype/padauk/Padauk-Bold.ttf'


def qc_decide(weighted_de00, ransac_inliers, de_max=DE_MAX, inliers_min=INLIERS_MIN):
    """Pure QC rule. FAIL if ΔE00 > de_max OR inliers < inliers_min (boundaries pass)."""
    reasons = []
    if weighted_de00 is None or weighted_de00 > de_max:
        reasons.append(f'weighted_de00_centre {weighted_de00} > {de_max}')
    if ransac_inliers is None or ransac_inliers < inliers_min:
        reasons.append(f'orb_ransac_inliers {ransac_inliers} < {inliers_min}')
    return {'passed': not reasons, 'status': 'PASS' if not reasons else 'REJECTED', 'reasons': reasons,
            'weighted_de00_centre': weighted_de00, 'orb_ransac_inliers': ransac_inliers,
            'thresholds': {'de00_max': de_max, 'inliers_min': inliers_min}}


def is_unicode_burmese(s):
    """True if every Myanmar-block char is a named Unicode char and no Zawgyi-only ordering
    markers appear (Zawgyi puts U+1031 'e' vowel BEFORE the consonant; Unicode never starts a
    syllable with it, i.e. U+1031 never follows a space/start)."""
    my = [c for c in s if 0x1000 <= ord(c) <= 0x109F]
    if not my:
        return False
    if any(not unicodedata.name(c, '') for c in my):
        return False
    for i, c in enumerate(s):
        if c == 'ေ' and (i == 0 or not (0x1000 <= ord(s[i - 1]) <= 0x109F)):
            return False
    return True


def centre(mask):
    import numpy as np
    ys, xs = np.where(mask)
    y0, y1, x0, x1 = ys.min(), ys.max(), xs.min(), xs.max()
    h, w = y1 - y0, x1 - x0
    c = np.zeros_like(mask)
    c[y0 + h // 4:y1 - h // 4, x0 + w // 4:x1 - w // 4] = True
    return mask & c


def evaluate(src_crop, img):
    import numpy as np
    from concept_eval import garment_mask, weighted_palette_de, strip_from_mask
    from ai_pattern_compare import orb_stats
    mask = garment_mask(img)
    cm = centre(mask)
    cstrip, _ = strip_from_mask(img, cm if cm.sum() > 500 else mask)
    strip, bbox = strip_from_mask(img, mask)
    orb = orb_stats(src_crop, strip)
    return mask, {'mask_frac': round(float(mask.mean()), 4), 'bbox': bbox,
                  'weighted_de00_centre': weighted_palette_de(src_crop, cstrip),
                  'weighted_de00_full': weighted_palette_de(src_crop, strip), 'orb': orb}


def correct(img, mask, src_crop):
    from concept_eval import hist_match
    return hist_match(img, mask, src_crop)


def compose(real_path, concept_bgr, out_path, rejected, qc):
    import cv2
    from PIL import Image, ImageDraw, ImageFont
    H = 1600
    real = Image.open(real_path).convert('RGB')
    real = real.resize((round(real.width * H / real.height), H), Image.LANCZOS)
    con = Image.fromarray(cv2.cvtColor(concept_bgr, cv2.COLOR_BGR2RGB))
    con = con.resize((round(con.width * H / con.height), H), Image.LANCZOS)
    gap, band = 24, 230
    W = real.width + con.width + gap
    canvas = Image.new('RGB', (W, H + band), (250, 248, 244))
    canvas.paste(real, (0, 0)); canvas.paste(con, (real.width + gap, 0))
    d = ImageDraw.Draw(canvas)
    fen = ImageFont.truetype(FONT_LATIN, 30); fja = ImageFont.truetype(FONT_JA, 34)
    fmy = ImageFont.truetype(FONT_MY, 30, layout_engine=ImageFont.Layout.RAQM)
    small = ImageFont.truetype(FONT_LATIN, 24)
    d.text((20, 18), 'REAL PRODUCT PHOTO', font=small, fill=(255, 255, 255), stroke_width=3, stroke_fill=(0, 0, 0))
    d.text((real.width + gap + 20, 18), 'AI CONCEPT (colour-corrected)', font=small, fill=(255, 255, 255),
           stroke_width=3, stroke_fill=(0, 0, 0))
    y = H + 20
    d.text((24, y), f'{LABEL_EN} / ', font=fen, fill=(30, 30, 30))
    d.text((24 + d.textlength(f'{LABEL_EN} / ', font=fen), y - 2), LABEL_JA, font=fja, fill=(30, 30, 30))
    d.text((24, y + 52), LABEL_MY, font=fmy, fill=(30, 30, 30))
    d.text((24, y + 140), f"QC {qc['status']}: ΔE00 centre {qc['weighted_de00_centre']} (max {DE_MAX}), "
           f"ORB inliers {qc['orb_ransac_inliers']} (min {INLIERS_MIN})", font=small,
           fill=(170, 20, 20) if rejected else (20, 110, 40))
    if rejected:
        d.rectangle([real.width + gap, H // 2 - 60, W, H // 2 + 60], fill=(170, 20, 20))
        d.text((real.width + gap + 30, H // 2 - 36), 'REJECTED — QC FAIL', font=ImageFont.truetype(FONT_LATIN, 56),
               fill=(255, 255, 255))
    canvas.save(out_path)
    return out_path


def ledger_for(outdir, prefix):
    try:
        led = json.load(open(os.path.join(outdir, 'ledger.json')))
    except FileNotFoundError:
        return []
    return [e for e in led if str(e.get('out_name', '')).startswith(prefix)]


def post(a, src_path, crop_path, ai_path, attempt_name):
    import cv2
    src_crop = cv2.imread(crop_path)
    ai = cv2.imread(ai_path)
    mask, raw = evaluate(src_crop, ai)
    corr = correct(ai, mask, src_crop)
    cp = os.path.join(a.outdir, attempt_name + '_corrected.png'); cv2.imwrite(cp, corr)
    cv2.imwrite(os.path.join(a.outdir, attempt_name + '_mask.png'), mask.astype('uint8') * 255)
    _, cor = evaluate(src_crop, corr)
    qc = qc_decide(cor['weighted_de00_centre'], cor['orb']['ransac_inliers'])
    rej = not qc['passed']
    out = os.path.join(a.outdir, attempt_name + ('_side_by_side_REJECTED.png' if rej else '_side_by_side.png'))
    compose(src_path, corr, out, rej, qc)
    res = {'name': attempt_name, 'source': src_path, 'ref_crop': crop_path, 'ai_raw': ai_path,
           'corrected': cp, 'side_by_side': out, 'raw_metrics': raw, 'corrected_metrics': cor, 'qc': qc,
           'label': {'en': LABEL_EN, 'ja': LABEL_JA, 'my': LABEL_MY,
                     'my_unicode_ok': is_unicode_burmese(LABEL_MY)},
           'note': 'AI fabric is untrusted; preview only, not published'}
    with open(os.path.join(a.outdir, attempt_name + '_qc.json'), 'w') as f:
        json.dump(res, f, indent=1, ensure_ascii=False)
    return res


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('mode', choices=['run', 'qc-only'])
    ap.add_argument('--source', required=True); ap.add_argument('--outdir', required=True)
    ap.add_argument('--name', required=True); ap.add_argument('--prompt-file')
    ap.add_argument('--ai'); ap.add_argument('--sub-cap-jpy', type=float, default=60.0)
    ap.add_argument('--max-calls', type=int, default=2); ap.add_argument('--round-cap-jpy', type=float, default=200.0)
    ap.add_argument('--product-id', type=int); ap.add_argument('--sku', default='')
    a = ap.parse_args()
    heavy_guard.acquire('styling_concept')
    import cv2
    import wm_detect
    os.makedirs(a.outdir, exist_ok=True)
    src = cv2.imread(a.source)
    crop_path = os.path.join(a.outdir, a.name + '_ref_clean_crop.jpg')
    if not os.path.exists(crop_path):
        rect, wm, rc = wm_detect.wm_free_crop(src, 0.75)
        if rect is None:
            raise SystemExit('no watermark-free crop found')
        x, y, w, h = rect
        cv2.imwrite(crop_path, src[y:y + h, x:x + w], [cv2.IMWRITE_JPEG_QUALITY, 95])
        json.dump({'crop': rect, 'watermark_boxes': wm['boxes'], 'recheck': rc},
                  open(crop_path + '.json', 'w'), indent=1)
    if a.mode == 'qc-only':
        print(json.dumps(post(a, a.source, crop_path, a.ai, a.name), indent=1, ensure_ascii=False))
        return 0
    # sub-cap for this concept (NAME prefix), in addition to gemini_poc's round & total caps
    prior = ledger_for(a.outdir, a.name)
    sub_spent = sum(e.get('cost_jpy_charged', 0) for e in prior)
    calls = len([e for e in prior if e.get('status') in ('ok', 'ok_no_image', 'error') or
                 str(e.get('status', '')).startswith('http_5')])
    sys.path.insert(0, HERE)
    import gemini_poc
    est = gemini_poc.worst_case_jpy(gemini_poc.MODELS['generate-pro'])
    if calls >= a.max_calls or sub_spent + est > a.sub_cap_jpy:
        print(json.dumps({'refused': True, 'reason': 'sub-cap', 'calls': calls, 'sub_spent_jpy': sub_spent,
                          'estimate_jpy': round(est, 2), 'sub_cap_jpy': a.sub_cap_jpy}))
        return 4
    attempt_name = f'{a.name}_a{calls + 1}'
    cmd = [sys.executable, os.path.join(HERE, 'gemini_poc.py'), 'generate-pro', '--source', a.source,
           '--ref', crop_path, '--outdir', a.outdir, '--prompt-file', a.prompt_file, '--out-name',
           attempt_name + '_raw', '--aspect', '9:16', '--round-cap-jpy', str(a.round_cap_jpy)]
    if a.product_id:
        cmd += ['--product-id', str(a.product_id)]
    r = subprocess.run(cmd, capture_output=True, text=True)
    print(r.stdout.strip(), r.stderr.strip()[-400:], file=sys.stderr)
    try:
        gen = json.loads(r.stdout.strip().splitlines()[-1])
    except (ValueError, IndexError):
        return 6
    if not gen.get('raw_image'):
        print(json.dumps({'generated': False, 'gen': gen}))
        return 6
    res = post(a, a.source, crop_path, gen['raw_image'], attempt_name)
    res['generation'] = gen
    with open(os.path.join(a.outdir, attempt_name + '_qc.json'), 'w') as f:
        json.dump(res, f, indent=1, ensure_ascii=False)
    print(json.dumps({'name': attempt_name, 'actual_jpy': gen.get('actual_jpy'), 'qc': res['qc'],
                      'side_by_side': res['side_by_side']}, ensure_ascii=False))
    return 0


if __name__ == '__main__':
    sys.exit(main())
