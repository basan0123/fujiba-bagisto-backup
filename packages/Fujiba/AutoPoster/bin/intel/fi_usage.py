#!/usr/bin/env python3
"""
Mirror ledger.json attempts into the fujiba tenant table fi_ai_usage.

  fi_usage.py sync DIR [DIR...]   idempotent backfill (key = <dirname>#<index>)
  fi_usage.py sum  DIR [DIR...]   compare sum(ledgers) vs sum(DB rows for those dirs)

Library: record(outdir, index, entry) — called by gemini_poc.py after every
ledger write. DB credentials come from /var/www/bagisto/.env and are passed to
the mysql client via a 0600 temp defaults file; they are never printed.
"""
import json, os, subprocess, sys, tempfile

ENV_FILE = '/var/www/bagisto/.env'
TENANT_DB = 'tenant_1bd1181f-fcd5-4238-a12d-a207d6ea4006'  # fujiba.co.jp


def _env():
    env = {}
    with open(ENV_FILE) as f:
        for line in f:
            if '=' in line and not line.lstrip().startswith('#'):
                k, v = line.rstrip('\n').split('=', 1)
                env[k.strip()] = v.strip().strip('"').strip("'")
    return env


def _mysql(sql):
    e = _env()
    fd, path = tempfile.mkstemp(prefix='fiu_', suffix='.cnf')
    try:
        with os.fdopen(fd, 'w') as f:
            f.write('[client]\nuser=%s\npassword=%s\nhost=%s\nport=%s\n' % (
                e['DB_USERNAME'], e['DB_PASSWORD'], e.get('DB_HOST', '127.0.0.1'), e.get('DB_PORT', '3306')))
        r = subprocess.run(['mysql', '--defaults-extra-file=' + path, '-N', '-B', TENANT_DB],
                           input=sql, capture_output=True, text=True, timeout=60)
        if r.returncode:
            raise RuntimeError('mysql failed: ' + r.stderr.strip()[:300])
        return r.stdout
    finally:
        os.unlink(path)


def _q(v):
    if v is None:
        return 'NULL'
    if isinstance(v, (int, float)):
        return repr(v)
    return "'" + str(v).replace('\\', '\\\\').replace("'", "\\'") + "'"


def key_for(outdir, index):
    return '%s#%d' % (os.path.basename(os.path.normpath(outdir)), index)


def row_sql(outdir, index, e):
    units_in = e.get('input_tokens', 0) or 0
    units_out = (e.get('text_output_tokens', 0) or 0) + (e.get('image_output_tokens', 0) or 0) \
        + (e.get('video_seconds', 0) or 0)
    meta = {k: e[k] for k in ('mode', 'attempt', 'out_name', 'image_output_tokens', 'text_output_tokens',
                              'video_seconds', 'actual_jpy', 'error', 'operation_name') if k in e}
    meta['preview_dir'] = os.path.abspath(outdir)
    ts = (e.get('ts') or '').replace('T', ' ') or None
    vals = [_q('gemini_poc_%s' % e.get('mode', 'call')), _q('gemini'), _q(e.get('model')),
            _q(int(units_in)), _q(int(units_out)), _q(float(e.get('cost_jpy_charged', 0) or 0)),
            _q(e.get('status')), _q(e.get('estimate_jpy')), _q(key_for(outdir, index)),
            _q(json.dumps(meta, ensure_ascii=False)), _q(e.get('product_id')), _q(ts), _q(ts)]
    return ('INSERT INTO fi_ai_usage (operation,provider,model,input_units,output_units,cost_jpy,status,'
            'estimate_jpy,ledger_key,meta,product_id,created_at,updated_at) VALUES (%s) '
            'ON DUPLICATE KEY UPDATE cost_jpy=VALUES(cost_jpy),status=VALUES(status),'
            'estimate_jpy=VALUES(estimate_jpy),meta=VALUES(meta),model=VALUES(model);\n' % ','.join(vals))


def record(outdir, index, entry):
    """Best effort: a DB failure must not lose the ledger (already written)."""
    try:
        _mysql(row_sql(outdir, index, entry))
        return True
    except Exception as ex:  # noqa: BLE001
        print(json.dumps({'fi_ai_usage_write': 'failed', 'error': str(ex)[:200]}), file=sys.stderr)
        return False


def _ledger(d):
    with open(os.path.join(d, 'ledger.json')) as f:
        return json.load(f)


def sync(dirs):
    sql = ''.join(row_sql(d, i, e) for d in dirs for i, e in enumerate(_ledger(d)))
    _mysql(sql)


def sums(dirs):
    led = sum(e.get('cost_jpy_charged', 0) or 0 for d in dirs for e in _ledger(d))
    keys = ','.join(_q(key_for(d, i)) for d in dirs for i, _ in enumerate(_ledger(d)))
    out = _mysql('SELECT COUNT(*), COALESCE(SUM(cost_jpy),0) FROM fi_ai_usage WHERE ledger_key IN (%s);' % keys)
    n, s = out.split()
    total = _mysql("SELECT COUNT(*), COALESCE(SUM(cost_jpy),0) FROM fi_ai_usage WHERE provider='gemini';").split()
    return {'ledger_rows': sum(len(_ledger(d)) for d in dirs), 'ledger_sum_jpy': round(led, 4),
            'db_rows': int(n), 'db_sum_jpy': float(s),
            'db_all_gemini_rows': int(total[0]), 'db_all_gemini_sum_jpy': float(total[1])}


if __name__ == '__main__':
    cmd, dirs = sys.argv[1], sys.argv[2:]
    if cmd == 'sync':
        sync(dirs)
    print(json.dumps(sums(dirs)))
