#!/usr/bin/env python3
"""
Gemini proof of concept — Fujiba Intelligence (ws/h). ONE analysis call or ONE
image-generation call per invocation, with a hard yen budget enforced HERE
(a billing alert is not a cap).

  gemini_poc.py analyse  --source img --outdir DIR [--prior-spend-jpy N]
  gemini_poc.py generate --source img --outdir DIR [--prior-spend-jpy N]

The API key is read from the live .env inside this process and sent only in
the x-goog-api-key header. It is never printed, logged or written.
Every attempt (incl. failures) is appended to DIR/ledger.json.
Outputs are AI inference / untrusted; generated fabric is never trusted.
"""
import argparse, base64, datetime, json, os, sys, time, urllib.request, urllib.error

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import heavy_guard  # noqa: E402

ENV_FILE = '/var/www/bagisto/.env'
API = 'https://generativelanguage.googleapis.com/v1beta/models/{m}:generateContent'
YEN_PER_USD = 155.0
BUDGET_JPY = 3000.0
MAX_RETRIES = 2
# Official paid-tier prices, https://ai.google.dev/gemini-api/docs/pricing (fetched 2026-10-14)
MODELS = {
    'analyse': {'id': 'gemini-3.8-flash', 'in': 0.75, 'out_text': 3.75, 'out_img': 0.0,
                'max_in_tokens': 4000, 'max_out_tokens': 4096, 'images': 0, 'per_image_usd': 0},
    'generate': {'id': 'gemini-3.1-flash-image', 'in': 0.50, 'out_text': 3.00, 'out_img': 60.00,
                 'max_in_tokens': 4000, 'max_out_tokens': 8192, 'images': 1, 'per_image_usd': 0.067},
}

ANALYSE_PROMPT = """You are analysing a product photo of a Japanese cotton kimono-print fabric.
Describe ONLY what is visible. Return STRICT JSON, no markdown, with this shape:
{"dominant_colours":[{"name":str,"hex":"#RRGGBB","share":0-1,"uncertain":bool}],
 "motifs":[{"name":str,"description":str,"prominence":"primary|secondary|accent","uncertain":bool}],
 "composition":str,
 "pattern_details":{"layout":str,"density":"sparse|medium|dense","scale":str,
   "repeat_estimate":{"value_cm":number|null,"basis":str,"uncertain":bool},
   "metallic_or_special_finish":{"present":bool,"uncertain":bool},"notes":str},
 "confidence":0-1,
 "uncertain_items":[str]}
Shares of dominant_colours should sum to about 1. Ignore watermarks, labels and the grey backdrop.
Flag anything you are guessing as uncertain. This is inference, not verified fact."""

GENERATE_PROMPT = """Create a premium product studio photograph, square 1:1.
Keep the fabric pieces EXACTLY where they are in the reference image: same position, same size,
same outline. Do not move, recolour or redraw the fabric. Change ONLY the surroundings:
replace the grey backdrop with a calm, luxurious Japanese studio set — warm off-white washi
paper wall, a pale hinoki wood surface, soft diffused window light from the left with gentle
natural shadows, and a few small tasteful props placed only in the empty corners away from the
fabric (a ceramic tea cup, a small sprig of maple leaves, a bamboo ruler).
No text, no logos, no people, no watermark."""


def read_key():
    with open(ENV_FILE) as f:
        for line in f:
            if line.startswith('GEMINI_API_KEY='):
                return line.split('=', 1)[1].strip().strip('"').strip("'")
    raise SystemExit('GEMINI_API_KEY missing')


def ledger_path(outdir):
    return os.path.join(outdir, 'ledger.json')


def load_ledger(outdir):
    try:
        with open(ledger_path(outdir)) as f:
            return json.load(f)
    except FileNotFoundError:
        return []


def save_ledger(outdir, led):
    tmp = ledger_path(outdir) + '.tmp'
    with open(tmp, 'w') as f:
        json.dump(led, f, indent=2, ensure_ascii=False)
    os.replace(tmp, ledger_path(outdir))


def worst_case_jpy(m):
    usd = (m['max_in_tokens'] * m['in'] + m['max_out_tokens'] * m['out_text']) / 1e6
    usd += m['images'] * m['per_image_usd']
    return usd * YEN_PER_USD


def actual_jpy(m, usage):
    p = usage.get('promptTokenCount', 0)
    thoughts = usage.get('thoughtsTokenCount', 0)
    img_out = 0
    for d in usage.get('candidatesTokensDetails', []) or []:
        if d.get('modality') == 'IMAGE':
            img_out += d.get('tokenCount', 0)
    cand = usage.get('candidatesTokenCount', 0)
    text_out = max(cand - img_out, 0) + thoughts
    usd = (p * m['in'] + text_out * m['out_text'] + img_out * m['out_img']) / 1e6
    return usd * YEN_PER_USD, p, text_out, img_out


def downscale_jpeg(path, size=1024):
    import cv2
    img = cv2.imread(path, cv2.IMREAD_COLOR)
    h, w = img.shape[:2]
    s = size / max(h, w)
    img = cv2.resize(img, (round(w * s), round(h * s)), interpolation=cv2.INTER_AREA)
    ok, buf = cv2.imencode('.jpg', img, [cv2.IMWRITE_JPEG_QUALITY, 92])
    return base64.b64encode(buf.tobytes()).decode()


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('mode', choices=['analyse', 'generate'])
    ap.add_argument('--source', required=True)
    ap.add_argument('--outdir', required=True)
    ap.add_argument('--prior-spend-jpy', type=float, default=0.0,
                    help='gemini spend already recorded in fi_ai_usage outside this ledger')
    a = ap.parse_args()
    heavy_guard.acquire('gemini_poc')
    os.makedirs(a.outdir, exist_ok=True)
    m = MODELS[a.mode]
    img_b64 = downscale_jpeg(a.source)

    body = {'contents': [{'role': 'user', 'parts': [
        {'inline_data': {'mime_type': 'image/jpeg', 'data': img_b64}},
        {'text': ANALYSE_PROMPT if a.mode == 'analyse' else GENERATE_PROMPT}]}]}
    if a.mode == 'analyse':
        body['generationConfig'] = {'responseMimeType': 'application/json',
                                    'maxOutputTokens': m['max_out_tokens']}
    else:
        body['generationConfig'] = {'responseModalities': ['IMAGE'],
                                    'maxOutputTokens': m['max_out_tokens'],
                                    'imageConfig': {'aspectRatio': '1:1', 'imageSize': '1K'}}

    key = read_key()
    for attempt in range(MAX_RETRIES + 1):
        led = load_ledger(a.outdir)
        spent = a.prior_spend_jpy + sum(e.get('cost_jpy_charged', 0) for e in led)
        est = worst_case_jpy(m)
        if spent + est > BUDGET_JPY:
            print(json.dumps({'refused': True, 'spent_jpy': spent, 'estimate_jpy': est}))
            return 4
        entry = {'ts': datetime.datetime.now().isoformat(timespec='seconds'), 'mode': a.mode,
                 'model': m['id'], 'attempt': attempt, 'estimate_jpy': round(est, 4),
                 'spent_before_jpy': round(spent, 4)}
        req = urllib.request.Request(API.format(m=m['id']), data=json.dumps(body).encode(),
                                     headers={'Content-Type': 'application/json',
                                              'x-goog-api-key': key}, method='POST')
        try:
            with urllib.request.urlopen(req, timeout=180) as r:
                resp = json.loads(r.read())
        except urllib.error.HTTPError as e:
            msg = e.read().decode(errors='replace')[:600]
            entry.update(status=f'http_{e.code}', error=msg,
                         # a rejected request is not billed, but count worst case for 5xx/timeouts
                         cost_jpy_charged=0.0 if 400 <= e.code < 500 else est)
            led.append(entry); save_ledger(a.outdir, led)
            print(json.dumps({'attempt': attempt, 'status': entry['status'], 'error': msg}), file=sys.stderr)
            if e.code in (400, 401, 403, 404):
                return 5
            time.sleep(5); continue
        except Exception as e:  # timeout: may have been billed → charge worst case
            entry.update(status='error', error=type(e).__name__, cost_jpy_charged=est)
            led.append(entry); save_ledger(a.outdir, led)
            time.sleep(5); continue

        usage = resp.get('usageMetadata', {})
        cost, p, tout, iout = actual_jpy(m, usage)
        entry.update(status='ok', usage=usage, input_tokens=p, text_output_tokens=tout,
                     image_output_tokens=iout, actual_jpy=round(cost, 4),
                     cost_jpy_charged=round(cost, 4))
        parts = (resp.get('candidates') or [{}])[0].get('content', {}).get('parts', [])
        result = {'model': m['id'], 'actual_jpy': round(cost, 4), 'estimate_jpy': round(est, 4),
                  'input_tokens': p, 'text_output_tokens': tout, 'image_output_tokens': iout}
        if a.mode == 'analyse':
            text = ''.join(x.get('text', '') for x in parts if not x.get('thought'))
            with open(os.path.join(a.outdir, 'analysis_raw.json'), 'w') as f:
                json.dump(resp, f, indent=2, ensure_ascii=False)
            try:
                parsed = json.loads(text)
            except ValueError:
                entry['status'] = 'ok_unparseable'
                parsed = None
            with open(os.path.join(a.outdir, 'analysis.json'), 'w') as f:
                json.dump({'label': 'AI inference (Gemini) — not verified fact',
                           'model': m['id'], 'analysis': parsed, 'text': text}, f,
                          indent=2, ensure_ascii=False)
            result['parsed'] = parsed is not None
        else:
            imgs = [x for x in parts if 'inlineData' in x or 'inline_data' in x]
            if not imgs:
                entry['status'] = 'ok_no_image'
            else:
                d = imgs[0].get('inlineData') or imgs[0].get('inline_data')
                ext = '.png' if 'png' in d.get('mimeType', d.get('mime_type', '')) else '.jpg'
                out = os.path.join(a.outdir, 'raw_generated' + ext)
                with open(out, 'wb') as f:
                    f.write(base64.b64decode(d['data']))
                result['raw_image'] = out
            slim = json.loads(json.dumps(resp))
            for c in slim.get('candidates', []):
                for x in c.get('content', {}).get('parts', []):
                    for k in ('inlineData', 'inline_data'):
                        if k in x:
                            x[k]['data'] = '<omitted>'
            with open(os.path.join(a.outdir, 'generate_raw.json'), 'w') as f:
                json.dump(slim, f, indent=2)
        led.append(entry); save_ledger(a.outdir, led)
        result['status'] = entry['status']
        print(json.dumps(result))
        return 0 if entry['status'] == 'ok' else 6
    return 7


if __name__ == '__main__':
    sys.exit(main())
