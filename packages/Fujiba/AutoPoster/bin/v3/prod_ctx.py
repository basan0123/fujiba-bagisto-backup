#!/usr/bin/env python3
"""Build a ctx.json for run_production.py from a fresh batch_scope.php output (READ-ONLY).

    python3 prod_ctx.py <scope.json> <out ctx.json> <product_id>:<audience> ...

audience: my | ne | ja. A product is included only if its scope row has no fail reason
(same fail-closed rules as batch.py); otherwise this exits 2 and names the reason.
"""
import json
import sys


def build(scope, picks):
    rows = {r['product_id']: r for r in scope['products']}
    out, bad = [], []
    for pid, aud in picks:
        r = rows.get(pid)
        if not r or r['fail']:
            bad.append([pid, r['fail'] if r else ['not in scope']])
            continue
        out.append({'product_id': pid, 'sku': r['sku'], 'name': r['name'], 'price': r['price'],
                    'available': r['available'], 'image': r['image'], 'image_sha256': r['image_sha256'],
                    'material': r['material'], 'size': r['size'], 'audience': aud})
    return {'built_at': scope['built_at'], 'products': out, 'rejected': bad}


if __name__ == '__main__':
    scope = json.load(open(sys.argv[1]))
    picks = [(int(a.split(':')[0]), a.split(':')[1]) for a in sys.argv[3:]]
    ctx = build(scope, picks)
    json.dump(ctx, open(sys.argv[2], 'w'), ensure_ascii=False, indent=1)
    print(json.dumps({'products': len(ctx['products']), 'rejected': ctx['rejected']}))
    sys.exit(2 if ctx['rejected'] else 0)
