#!/usr/bin/env python3
"""
Quick-use creative library index (free, read-only over preview outputs).

  library.py [--preview DIR] [--out DIR]

Scans every preview output dir (v3-batch manifests, 2026-10-14-v3*, collection,
gemini/concept/silhouette dirs, …) and writes
  storage/app/autoposter/library/index.json
  storage/app/autoposter/library/index.html   (static; PRIVATE storage, never public)

Per product: sku, public name, price, stock as recorded in the v3-batch manifest,
and each deliverable asset: path, family, size, gate pass (True/False/None =
no gate record), is_ai_concept, generated_at, stale.

Rules:
  * debug/QA files (side-by-sides, masks, shadows, expected/unwarp layers,
    source crops, raw grey AI models, ledgers) are not assets;
  * gate: v3-batch manifest attempt/video result; else sibling <stem>.gate.json
    or <name>_gate_*.json (all must pass); "REJECTED" in a filename ⇒ False;
  * is_ai_concept: anything from a gemini/concept/silhouette/thadingyut/
    living-fabric dir (AI made model, scene or layout — fabric may be real);
  * stale: manifest says stale, or the catalog photo's sha256 no longer matches
    the one the asset was rendered from.
Re-runnable; batch.py calls it after a run finishes.
"""
import argparse
import datetime
import glob
import hashlib
import html
import json
import os
import re

PREVIEW = '/var/www/bagisto/storage/app/autoposter/preview'
OUT = '/var/www/bagisto/storage/app/autoposter/library'
MEDIA = ('.jpg', '.jpeg', '.png', '.mp4', '.webp')
DEBUG = re.compile(r'(\.sbs\d*|side_by_side|_mask|mask\.png|_shadow|expected|unwarp|source_crop|fabric_layer|'
                   r'_qc|grey_a\d|grey_swatch|dryrun|fonts_sample|\.gatemask|_corrected|_raw\b|inverse|ref_|wmfree|source-original|'
                   r'_compare|_frame)', re.I)
AI_DIR = re.compile(r'gemini|concept|silhouette|thadingyut|living-fabric', re.I)


def sha(path):
    h = hashlib.sha256()
    with open(path, 'rb') as f:
        for b in iter(lambda: f.read(1 << 20), b''):
            h.update(b)
    return h.hexdigest()


def load_products(preview):
    prods = {}
    for f in glob.glob(os.path.join(preview, 'v3-batch', 'manifest', '*.json')):
        try:
            m = json.load(open(f))
        except ValueError:
            continue
        src = (m.get('source_images') or [{}])[0]
        cur = None
        if src.get('path') and os.path.exists(src['path']):
            cur = sha(src['path'])
        prods[m['name']] = {
            'product_id': m['product_id'], 'sku': m.get('sku'), 'name': m['name'], 'series': m.get('series'),
            'price': m.get('price'), 'stock_recorded': m.get('available'),
            'source_sha_rendered': src.get('sha256'),
            'source_changed': bool(cur and src.get('sha256') and cur != src['sha256']),
            'manifest': m, 'assets': []}
    return prods


def gate_for(path, man_gate):
    if path in man_gate:
        return man_gate[path]
    base = os.path.basename(path)
    if 'REJECTED' in base.upper():
        return False
    d, stem = os.path.dirname(path), re.sub(r'(\.png|\.jpg|\.mp4|_composite_labelled|_composite)+$', '', base)
    cands = [os.path.join(d, stem + '.gate.json')] + glob.glob(os.path.join(d, stem + '_gate_*.json'))
    res = []
    for c in cands:
        try:
            res.append(bool(json.load(open(c)).get('passed')))
        except (OSError, ValueError, AttributeError):
            pass
    return all(res) if res else None


def match_product(base, names, dir_pid_name):
    key = re.sub(r'[^a-z0-9]', '', base.lower())
    best = None
    for n in names:
        nk = re.sub(r'[^a-z0-9]', '', n.lower())
        if nk and nk in key and (best is None or len(nk) > len(best[1])):
            best = (n, nk)
    return best[0] if best else dir_pid_name


def family_of(rel, base):
    parts = rel.split(os.sep)
    if parts[0] == 'v3-batch' and len(parts) > 2:
        return parts[1]
    m = re.match(r'F\d+-([a-z]+(?:-[A-Z])?)', base)
    if m:
        return m.group(1)
    m = re.match(r'([a-z]+)[_-]', base)
    return m.group(1) if m else parts[0]


def build(preview):
    prods = load_products(preview)
    by_pid = {p['product_id']: n for n, p in prods.items()}
    names = sorted(prods, key=len, reverse=True)
    man_gate, man_stale = {}, {}
    for n, p in prods.items():
        m = p['manifest']
        for a in m.get('attempts', []):
            for o in a.get('outputs', []):
                man_gate[o] = bool(a.get('passed'))
        for o in (m.get('video') or {}).get('outputs', []):
            man_gate[o] = bool(m['video'].get('passed'))
        for o in m.get('outputs', []):
            man_stale[o] = bool(m.get('stale'))
    unassigned = []
    for root, dirs, files in os.walk(preview):
        dirs[:] = [d for d in dirs if d not in ('manifest', 'src')]  # src = input copies, not assets
        rel_dir = os.path.relpath(root, preview)
        dir_name = None
        led = os.path.join(root, 'ledger.json')
        if os.path.exists(led):
            try:
                pids = {e.get('product_id') for e in json.load(open(led)) if e.get('product_id')}
                if len(pids) == 1:
                    dir_name = by_pid.get(pids.pop())
            except (ValueError, AttributeError):
                pass
        for f in sorted(files):
            if not f.lower().endswith(MEDIA) or DEBUG.search(f):
                continue
            path = os.path.join(root, f)
            if path.endswith('.png') and os.path.exists(path[:-4] + '.jpg'):
                continue  # lossless master of a delivered jpg
            rel = os.path.relpath(path, preview)
            n = match_product(f, names, dir_name)
            st = os.stat(path)
            asset = {'path': path, 'dir': rel_dir, 'family': family_of(rel, f), 'bytes': st.st_size,
                     'gate_pass': gate_for(path, man_gate), 'is_ai_concept': bool(AI_DIR.search(rel_dir)),
                     'generated_at': datetime.datetime.fromtimestamp(st.st_mtime).astimezone().isoformat(timespec='seconds')}
            if n:
                asset['stale'] = man_stale.get(path, False) or prods[n]['source_changed']
                prods[n]['assets'].append(asset)
            else:
                asset['stale'] = None
                unassigned.append(asset)
    out = []
    for n in sorted(prods):
        p = {k: v for k, v in prods[n].items() if k != 'manifest'}
        p['assets'].sort(key=lambda a: (a['family'], a['path']))
        out.append(p)
    allassets = [a for p in out for a in p['assets']]
    return {'generated_at': datetime.datetime.now().astimezone().isoformat(timespec='seconds'),
            'preview_root': preview, 'private': True,
            'counts': {'products': len(out), 'products_with_assets': sum(bool(p['assets']) for p in out),
                       'assets': len(allassets), 'gate_pass': sum(a['gate_pass'] is True for a in allassets),
                       'gate_fail': sum(a['gate_pass'] is False for a in allassets),
                       'gate_unknown': sum(a['gate_pass'] is None for a in allassets),
                       'ai_concept': sum(a['is_ai_concept'] for a in allassets),
                       'stale': sum(bool(a['stale']) for a in allassets), 'unassigned': len(unassigned)},
            'filters': ['text (sku/name/family/path)', 'gate pass only', 'hide AI concept', 'hide stale'],
            'products': out, 'unassigned': unassigned}


def render_html(idx):
    e = html.escape
    rows = []
    for p in idx['products']:
        for a in p['assets']:
            g = {True: 'PASS', False: 'FAIL', None: '—'}[a['gate_pass']]
            rows.append(
                f"<tr data-g='{g}' data-ai='{int(a['is_ai_concept'])}' data-st='{int(bool(a['stale']))}'>"
                f"<td>{e(str(p['sku']))}</td><td>{e(p['name'])}</td><td>¥{e(str(p['price']))}</td>"
                f"<td>{e(str(p['stock_recorded']))}</td><td>{e(a['family'])}</td><td>{a['bytes'] // 1024} KB</td>"
                f"<td class='{g}'>{g}</td><td>{'AI concept' if a['is_ai_concept'] else ''}</td>"
                f"<td>{'stale' if a['stale'] else ''}</td><td>{e(a['generated_at'][:16])}</td>"
                f"<td class='p'>{e(a['path'])}</td></tr>")
    c = idx['counts']
    return f"""<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Creative Library</title><style>
:root{{--bg:#fff;--fg:#1a1a1a;--mut:#666;--line:#ddd}}
@media (prefers-color-scheme:dark){{:root{{--bg:#151515;--fg:#eee;--mut:#999;--line:#333}}}}
body{{background:var(--bg);color:var(--fg);font:13px system-ui,sans-serif;margin:16px}}
table{{border-collapse:collapse;width:100%}}td,th{{border-bottom:1px solid var(--line);padding:4px 6px;text-align:left}}
.p{{color:var(--mut);font-size:11px;word-break:break-all}}.PASS{{color:#1a7f37}}.FAIL{{color:#c62828}}
.bar{{display:flex;gap:12px;flex-wrap:wrap;margin:8px 0}}</style></head><body>
<h1>Creative library (private preview index)</h1>
<p>Generated {e(idx['generated_at'])} · {c['products_with_assets']}/{c['products']} products with assets · {c['assets']} assets
 · gate pass {c['gate_pass']} / fail {c['gate_fail']} / none {c['gate_unknown']} · AI concept {c['ai_concept']} · stale {c['stale']} · unassigned {c['unassigned']}.
 AI-concept assets must carry "Image for reference / ပုံစံနမူနာသာ".</p>
<div class="bar"><input id="q" placeholder="filter sku / name / family / path">
<label><input type="checkbox" id="gp"> gate PASS only</label><label><input type="checkbox" id="ai"> hide AI concept</label>
<label><input type="checkbox" id="st"> hide stale</label></div>
<table><thead><tr><th>SKU</th><th>Name</th><th>Price</th><th>Stock (rec.)</th><th>Family</th><th>Size</th><th>Gate</th><th>AI</th><th>Stale</th><th>Generated</th><th>Path</th></tr></thead>
<tbody>{''.join(rows)}</tbody></table>
<script>
const f=()=>{{const q=document.getElementById('q').value.toLowerCase(),gp=document.getElementById('gp').checked,
ai=document.getElementById('ai').checked,st=document.getElementById('st').checked;
document.querySelectorAll('tbody tr').forEach(r=>{{r.style.display=(r.textContent.toLowerCase().includes(q)&&(!gp||r.dataset.g==='PASS')
&&(!ai||r.dataset.ai==='0')&&(!st||r.dataset.st==='0'))?'':'none'}})}};
document.querySelectorAll('input').forEach(i=>i.addEventListener('input',f));
</script></body></html>"""


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--preview', default=PREVIEW)
    ap.add_argument('--out', default=OUT)
    a = ap.parse_args()
    os.makedirs(a.out, exist_ok=True)
    idx = build(a.preview)
    for name, data in (('index.json', json.dumps(idx, ensure_ascii=False, indent=1)), ('index.html', render_html(idx))):
        tmp = os.path.join(a.out, name + '.tmp')
        with open(tmp, 'w', encoding='utf-8') as f:
            f.write(data)
        os.replace(tmp, os.path.join(a.out, name))
    print(json.dumps({'out': a.out, **idx['counts']}))
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
