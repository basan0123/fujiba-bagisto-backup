#!/usr/bin/env python3
"""
Draft an engagement CSV from competitor screenshots with tesseract.

    competitor_screens_ocr.py <folder> [--out draft.csv] [--handle H]
                              [--lang eng+jpn] [--limit N] [--psm 11]

<folder> is either the drop folder /root/incoming/competitor-screens (every
<handle>/ sub-folder is one competitor) or one folder of screenshots (then
--handle, else the folder's own name, is the handle).

What it reads (best effort — OCR misreads digits):
  * labelled numbers  "1,234 likes", "View all 56 comments", "12.3K views",
                      "いいね！1,234件", "1.2万回再生"  → one row per screenshot
  * profile grids     3+ bare counts ("12.3K" under each tile) → one row per
                      tile, read in OCR order, as views, post_ref 'new'
  * anything else     one row, numbers listed in notes for a human to map

Every row is marked 'ocr_draft' in notes. fujiba:intel:competitor-metrics
refuses such rows until a human (or Claude, looking at the screenshot) has
checked each number and removed 'ocr_draft'. Counts like "12.3K" are written
already expanded (12300); the raw OCR text is kept in notes.

Image/OCR work is heavy: acquires heavy_guard (one heavy job at a time,
memory cap) before touching any image.
"""
import argparse
import csv
import datetime as dt
import json
import os
import re
import subprocess
import sys

COLUMNS = ['handle', 'post_ref', 'cover_note', 'views', 'likes', 'comments', 'shares', 'saves',
           'captured_at', 'screenshot_file', 'notes']
IMAGE_EXT = ('.png', '.jpg', '.jpeg', '.webp')

NUM = r'(\d{1,3}(?:,\d{3})+|\d+(?:\.\d+)?)\s*([KkMmBb万億])?'
MULT = {'': 1, 'k': 1_000, 'm': 1_000_000, 'b': 1_000_000_000, '万': 10_000, '億': 100_000_000}

LABELS = {
    'views':    [NUM + r'\s*(?:views?|plays?|回再生|再生)', r'(?:views?|再生回数)\s*[:：]?\s*' + NUM],
    'likes':    [NUM + r'\s*(?:likes?|件のいいね)', r'いいね[!！]?\s*' + NUM, r'and\s+' + NUM + r'\s+others'],
    'comments': [NUM + r'\s*(?:comments?|件のコメント|コメント)', r'view\s+all\s+' + NUM],
    'shares':   [NUM + r'\s*(?:shares?|シェア)'],
    'saves':    [NUM + r'\s*(?:saves?|保存)'],
}
BARE_COUNT = re.compile(r'(?<![\w.,:/])' + NUM + r'(?![\w.,:/%])')
TIKTOK_ID = re.compile(r'(?<!\d)(\d{17,21})(?!\d)')


def parse_count(digits, suffix=''):
    """'12.3','K' -> 12300 ; '1,234','' -> 1234 ; '1.2','万' -> 12000. None if not a whole count."""
    suffix = suffix or ''
    mult = MULT[suffix if suffix in ('万', '億') else suffix.lower()]
    digits = digits.replace(',', '')
    if '.' in digits:
        if mult == 1:
            return None
        whole, frac = digits.split('.', 1)
        return int(whole) * mult + (int(frac) * mult) // (10 ** len(frac))
    return int(digits) * mult


def labelled_metrics(text):
    """{'likes': (raw, 1234), ...} from keyword-labelled numbers."""
    out = {}
    flat = ' '.join(text.split())
    for metric, patterns in LABELS.items():
        for pat in patterns:
            m = re.search(pat, flat, re.I)
            if m:
                v = parse_count(m.group(1), m.group(2) or '')
                if v is not None:
                    out[metric] = (m.group(0).strip(), v)
                    break
    return out


def bare_counts(text):
    """Count-looking tokens without a label: suffixed (12.3K) or comma-grouped (1,234) only —
    plain integers are too often clock times, dates or prices to guess at."""
    found = []
    for m in BARE_COUNT.finditer(text):
        digits, suffix = m.group(1), m.group(2) or ''
        if not suffix and ',' not in digits:
            continue
        v = parse_count(digits, suffix)
        if v is not None:
            found.append((m.group(0).strip(), v))
    return found


def draft_rows(handle, path, text, captured_at):
    """Turn one screenshot's OCR text into draft CSV rows (all marked ocr_draft)."""
    name = os.path.basename(path)
    stem = os.path.splitext(name)[0]
    ids = TIKTOK_ID.findall(name) or TIKTOK_ID.findall(text)
    ref_note = ''
    if ids:
        post_ref = ids[0]
    elif stem.isdigit() and len(stem) <= 4:
        # ig_screens/7.png ↔ owner_screenshot_07 (Instagram screenshot numbering)
        post_ref, ref_note = str(int(stem)), ' | post_ref from file name (IG screenshot no.) — use new if not'
    else:
        post_ref = 'new'
    base = {'handle': handle, 'post_ref': post_ref, 'cover_note': f'{name} (OCR)', 'captured_at': captured_at,
            'screenshot_file': path}
    snippet = ' '.join(text.split())[:120]

    lab = labelled_metrics(text)
    if lab:
        row = dict(base)
        for metric, (_raw, v) in lab.items():
            row[metric] = v
        row['notes'] = 'ocr_draft | kind=labelled | raw: ' + '; '.join(r for r, _ in lab.values()) + ref_note
        return [row]

    counts = bare_counts(text)
    if len(counts) >= 3:
        rows = []
        for i, (raw, v) in enumerate(counts, 1):
            row = dict(base, post_ref='new', cover_note=f'{name} grid tile {i} (OCR order)', views=v)
            row['notes'] = f'ocr_draft | kind=grid | raw: {raw}'
            rows.append(row)
        return rows

    row = dict(base)
    if counts:
        row['notes'] = 'ocr_draft | kind=unassigned | numbers: ' + ', '.join(f'{r}={v}' for r, v in counts) + ' — map them by hand' + ref_note
    else:
        row['notes'] = 'ocr_draft | kind=none | no counts found' + ref_note + (f' | text: {snippet}' if snippet else '')
    return [row]


def natural_key(s):
    return [int(t) if t.isdigit() else t.lower() for t in re.split(r'(\d+)', s)]


def collect(folder, handle=None):
    """[(handle, image_path)] — sub-folders are handles; loose images use --handle or the folder name."""
    items = []
    entries = sorted(os.listdir(folder), key=natural_key)
    for e in entries:
        p = os.path.join(folder, e)
        if os.path.isdir(p):
            for f in sorted(os.listdir(p), key=natural_key):
                if f.lower().endswith(IMAGE_EXT):
                    items.append((e, os.path.join(p, f)))
        elif e.lower().endswith(IMAGE_EXT):
            items.append((handle or os.path.basename(os.path.abspath(folder)), p))
    return items


def ocr(path, lang, psm):
    env = dict(os.environ, OMP_THREAD_LIMIT='1')
    r = subprocess.run(['tesseract', path, 'stdout', '-l', lang, '--psm', str(psm)],
                       capture_output=True, text=True, timeout=180, env=env)
    if r.returncode != 0:
        raise RuntimeError((r.stderr or '').strip()[-300:])
    return r.stdout


def available_lang(wanted):
    try:
        have = set(subprocess.run(['tesseract', '--list-langs'], capture_output=True, text=True, timeout=30).stdout.split()[1:])
    except Exception:
        return 'eng'
    langs = [l for l in wanted.split('+') if l in have]
    return '+'.join(langs) or 'eng'


def write_csv(path, rows):
    tmp = path + f'.tmp{os.getpid()}'
    with open(tmp, 'w', encoding='utf-8-sig', newline='') as f:
        w = csv.DictWriter(f, fieldnames=COLUMNS, extrasaction='ignore')
        w.writeheader()
        for r in rows:
            w.writerow({c: r.get(c, '') for c in COLUMNS})
    if os.path.getsize(tmp) == 0:
        os.unlink(tmp)
        raise RuntimeError(f'zero-size output refused: {path}')
    os.replace(tmp, path)


def main():
    ap = argparse.ArgumentParser(description=__doc__.split('\n\n')[0])
    ap.add_argument('folder')
    ap.add_argument('--out')
    ap.add_argument('--handle')
    ap.add_argument('--lang', default='eng+jpn')
    ap.add_argument('--psm', type=int, default=11)
    ap.add_argument('--limit', type=int, default=0)
    a = ap.parse_args()

    if not os.path.isdir(a.folder):
        print(json.dumps({'error': 'not a folder', 'folder': a.folder}))
        return 2
    items = collect(a.folder, a.handle)
    if a.limit:
        items = items[:a.limit]
    lang = available_lang(a.lang)
    out = a.out or os.path.join(a.folder, 'ocr_draft_' + dt.datetime.now().strftime('%Y%m%d_%H%M%S') + '.csv')

    rows, errors = [], []
    for handle, path in items:
        captured = dt.datetime.fromtimestamp(os.path.getmtime(path)).strftime('%Y-%m-%d %H:%M')
        try:
            text = ocr(path, lang, a.psm)
        except Exception as e:  # keep going; a human sees the error row
            errors.append({'file': path, 'error': str(e)[:200]})
            rows.append({'handle': handle, 'post_ref': 'new', 'cover_note': os.path.basename(path) + ' (OCR failed)',
                         'captured_at': captured, 'screenshot_file': path, 'notes': 'ocr_draft | kind=error | ' + str(e)[:120]})
            continue
        rows.extend(draft_rows(handle, path, text, captured))

    write_csv(out, rows)
    print(json.dumps({'images': len(items), 'rows': len(rows), 'errors': errors, 'lang': lang, 'psm': a.psm,
                      'out': out, 'bytes': os.path.getsize(out),
                      'kinds': {k: sum(1 for r in rows if f'kind={k}' in r.get('notes', '')) for k in
                                ('labelled', 'grid', 'unassigned', 'none', 'error')}}, ensure_ascii=False))
    return 0


if __name__ == '__main__':
    import os as _o, sys as _s; _s.path.insert(0, _o.path.dirname(_o.path.abspath(__file__))); __import__('heavy_guard').acquire('competitor_screens_ocr')  # one heavy job at a time + memory cap
    try:
        _o.nice(10)
    except OSError:
        pass
    sys.exit(main())
