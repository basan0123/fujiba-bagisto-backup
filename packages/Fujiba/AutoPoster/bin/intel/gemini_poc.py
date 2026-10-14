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
import fi_usage  # noqa: E402

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
    # Nano Banana Pro — $2/M in, $12/M text+thinking out, $120/M image out (=$0.134 per 1K/2K image)
    'generate-pro': {'id': 'gemini-3-pro-image', 'in': 2.00, 'out_text': 12.00, 'out_img': 120.00,
                     'max_in_tokens': 6000, 'max_out_tokens': 8192, 'images': 1, 'per_image_usd': 0.134},
    # Veo 3.1 Lite — $0.05/s at 720p (audio always on, included); predictLongRunning
    'video': {'id': 'veo-3.1-lite-generate-preview', 'usd_per_s': 0.05, 'seconds': 8},
}
VEO_API = 'https://generativelanguage.googleapis.com/v1beta/models/{m}:predictLongRunning'
OP_API = 'https://generativelanguage.googleapis.com/v1beta/'

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
    # mirror the newest attempt into fi_ai_usage (idempotent key <dir>#<index>)
    if led:
        fi_usage.record(outdir, len(led) - 1, led[-1])


def db_prior_jpy(outdir):
    """Gemini spend already in fi_ai_usage for OTHER preview dirs (fails closed: huge)."""
    try:
        out = fi_usage._mysql("SELECT COALESCE(SUM(cost_jpy),0) FROM fi_ai_usage WHERE provider='gemini' "
                              "AND (ledger_key IS NULL OR ledger_key NOT LIKE %s);"
                              % fi_usage._q(os.path.basename(os.path.normpath(outdir)) + '#%'))
        return float(out.strip())
    except Exception:  # noqa: BLE001
        return BUDGET_JPY


def worst_case_jpy(m):
    if 'usd_per_s' in m:
        return m['usd_per_s'] * m['seconds'] * YEN_PER_USD
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
    ap.add_argument('mode', choices=['analyse', 'generate', 'generate-pro', 'video'])
    ap.add_argument('--source', required=True)
    ap.add_argument('--outdir', required=True)
    ap.add_argument('--prior-spend-jpy', type=float, default=0.0,
                    help='gemini spend already recorded in fi_ai_usage outside this ledger')
    ap.add_argument('--prompt-file', help='generate: prompt text file (default GENERATE_PROMPT)')
    ap.add_argument('--out-name', default='raw_generated', help='generate: output basename')
    ap.add_argument('--round-cap-jpy', type=float, default=None,
                    help='per-round cap: refuse if this outdir ledger spend + worst-case estimate > cap')
    ap.add_argument('--ref', action='append', default=[],
                    help='extra reference image(s) sent after --source (generate)')
    ap.add_argument('--aspect', default='1:1', help='generate*/video aspect ratio')
    ap.add_argument('--product-id', type=int, default=None)
    a = ap.parse_args()
    heavy_guard.acquire('gemini_poc')
    os.makedirs(a.outdir, exist_ok=True)
    m = MODELS[a.mode]
    img_b64 = downscale_jpeg(a.source)
    a.prior_spend_jpy = max(a.prior_spend_jpy, db_prior_jpy(a.outdir))
    if a.mode == 'video':
        return run_video(a, m, img_b64)

    body = {'contents': [{'role': 'user', 'parts': [
        {'inline_data': {'mime_type': 'image/jpeg', 'data': img_b64}},
        *[{'inline_data': {'mime_type': 'image/jpeg', 'data': downscale_jpeg(r)}} for r in a.ref],
        {'text': ANALYSE_PROMPT if a.mode == 'analyse' else (open(a.prompt_file, encoding='utf-8').read() if a.prompt_file else GENERATE_PROMPT)}]}]}
    if a.mode == 'analyse':
        body['generationConfig'] = {'responseMimeType': 'application/json',
                                    'maxOutputTokens': m['max_out_tokens']}
    else:
        body['generationConfig'] = {'responseModalities': ['IMAGE'],
                                    'maxOutputTokens': m['max_out_tokens'],
                                    'imageConfig': {'aspectRatio': a.aspect, 'imageSize': '1K'}}

    key = read_key()
    for attempt in range(MAX_RETRIES + 1):
        led = load_ledger(a.outdir)
        round_spent = sum(e.get('cost_jpy_charged', 0) for e in led)
        spent = a.prior_spend_jpy + round_spent
        est = worst_case_jpy(m)
        if spent + est > BUDGET_JPY:
            print(json.dumps({'refused': True, 'spent_jpy': spent, 'estimate_jpy': est}))
            return 4
        if a.round_cap_jpy is not None and round_spent + est > a.round_cap_jpy:
            print(json.dumps({'refused': True, 'reason': 'round cap', 'round_spent_jpy': round_spent,
                              'estimate_jpy': est, 'round_cap_jpy': a.round_cap_jpy}))
            return 4
        entry = {'ts': datetime.datetime.now().isoformat(timespec='seconds'), 'mode': a.mode,
                 'model': m['id'], 'attempt': attempt, 'estimate_jpy': round(est, 4),
                 'spent_before_jpy': round(spent, 4), 'out_name': a.out_name, 'product_id': a.product_id}
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
                out = os.path.join(a.outdir, a.out_name + ext)
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


def _budget_ok(a, est):
    led = load_ledger(a.outdir)
    round_spent = sum(e.get('cost_jpy_charged', 0) for e in led)
    spent = a.prior_spend_jpy + round_spent
    if spent + est > BUDGET_JPY or (a.round_cap_jpy is not None and round_spent + est > a.round_cap_jpy):
        print(json.dumps({'refused': True, 'spent_jpy': spent, 'round_spent_jpy': round_spent,
                          'estimate_jpy': est, 'round_cap_jpy': a.round_cap_jpy}))
        return None
    return spent


def run_video(a, m, img_b64):
    """ONE Veo image-to-video operation (no retries: each submit may bill)."""
    key = read_key()
    est = worst_case_jpy(m)
    spent = _budget_ok(a, est)
    if spent is None:
        return 4
    prompt = open(a.prompt_file, encoding='utf-8').read()
    body = {'instances': [{'prompt': prompt,
                           'image': {'inlineData': {'mimeType': 'image/jpeg', 'data': img_b64}}}],
            'parameters': {'aspectRatio': a.aspect, 'resolution': '720p',
                           'durationSeconds': m['seconds'], 'personGeneration': 'allow_adult',
                           'numberOfVideos': 1}}
    entry = {'ts': datetime.datetime.now().isoformat(timespec='seconds'), 'mode': 'video',
             'model': m['id'], 'attempt': 0, 'estimate_jpy': round(est, 4),
             'spent_before_jpy': round(spent, 4), 'out_name': a.out_name, 'product_id': a.product_id}
    led = load_ledger(a.outdir)
    hdr = {'Content-Type': 'application/json', 'x-goog-api-key': key}
    try:
        req = urllib.request.Request(VEO_API.format(m=m['id']), data=json.dumps(body).encode(),
                                     headers=hdr, method='POST')
        with urllib.request.urlopen(req, timeout=120) as r:
            op = json.loads(r.read())
    except urllib.error.HTTPError as e:
        msg = e.read().decode(errors='replace')[:600]
        entry.update(status=f'http_{e.code}', error=msg, cost_jpy_charged=0.0 if 400 <= e.code < 500 else est)
        led.append(entry); save_ledger(a.outdir, led)
        print(json.dumps({'status': entry['status'], 'error': msg}))
        return 5
    entry['operation_name'] = op.get('name')
    # charge worst case from submit on (a running op may bill even if we lose it)
    entry.update(status='submitted', cost_jpy_charged=est)
    led.append(entry); save_ledger(a.outdir, led)
    t0 = time.time()
    while not op.get('done'):
        if time.time() - t0 > 900:
            entry['status'] = 'poll_timeout'; save_ledger(a.outdir, led)
            return 6
        time.sleep(10)
        with urllib.request.urlopen(urllib.request.Request(OP_API + op['name'], headers=hdr), timeout=60) as r:
            op = json.loads(r.read())
    if 'error' in op:
        entry.update(status='op_error', error=json.dumps(op['error'])[:600])
        save_ledger(a.outdir, led)
        print(json.dumps({'status': 'op_error', 'error': op['error']}))
        return 6
    gvr = op.get('response', {}).get('generateVideoResponse', {})
    samples = gvr.get('generatedSamples') or []
    if not samples:
        entry.update(status='no_video', error=json.dumps(gvr)[:600], cost_jpy_charged=0.0)
        save_ledger(a.outdir, led)
        print(json.dumps({'status': 'no_video', 'detail': gvr}))
        return 6
    out = os.path.join(a.outdir, a.out_name + '.mp4')
    with urllib.request.urlopen(urllib.request.Request(samples[0]['video']['uri'], headers={'x-goog-api-key': key}),
                                timeout=300) as r, open(out, 'wb') as f:
        f.write(r.read())
    entry.update(status='ok', video_seconds=m['seconds'], actual_jpy=round(est, 4), cost_jpy_charged=round(est, 4))
    save_ledger(a.outdir, led)
    print(json.dumps({'status': 'ok', 'model': m['id'], 'video': out, 'actual_jpy': round(est, 4)}))
    return 0


if __name__ == '__main__':
    sys.exit(main())
