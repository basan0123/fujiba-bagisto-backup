#!/usr/bin/env python3
"""
Parsing tests for competitor_screens_ocr.py — no images, no tesseract, no
heavy_guard (only the pure text→row functions are imported).

Run:  python3 test_competitor_screens_ocr.py      Exit 0 = all passed.
"""
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import competitor_screens_ocr as o  # noqa: E402

FAILS = []


def check(name, got, want):
    if got != want:
        FAILS.append(f'{name}: got {got!r}, want {want!r}')


check('12.3K', o.parse_count('12.3', 'K'), 12300)
check('1.25M', o.parse_count('1.25', 'm'), 1250000)
check('1,234', o.parse_count('1,234'), 1234)
check('1.2万', o.parse_count('1.2', '万'), 12000)
check('3億', o.parse_count('3', '億'), 300000000)
check('1.5 plain is not a count', o.parse_count('1.5'), None)

lab = o.labelled_metrics('Liked by someone and 1,234 others\nView all 56 comments\n12.3K views')
check('labelled likes', lab['likes'][1], 1234)
check('labelled comments', lab['comments'][1], 56)
check('labelled views', lab['views'][1], 12300)
check('labelled ja', {k: v[1] for k, v in o.labelled_metrics('いいね！1,234件 1.2万回再生').items()}, {'likes': 1234, 'views': 12000})

check('bare counts skip plain ints/times', [v for _, v in o.bare_counts('12:30 2026 ¥1500 12.3K 1,024 999 4.5M')], [12300, 1024, 4500000])

grid = o.draft_rows('h', '/x/grid.png', 'D 12.3K D 8,402 D 1.1M D 640', '2026-10-11 10:00')
check('grid rows', len(grid), 3)
check('grid views', [r['views'] for r in grid], [12300, 8402, 1100000])
check('grid all draft', all(r['notes'].startswith('ocr_draft') and r['post_ref'] == 'new' for r in grid), True)

one = o.draft_rows('h', '/x/7594413916206288135.png', '1,234 likes 56 comments', '2026-10-11 10:00')
check('labelled single row', len(one), 1)
check('tiktok id from file name', one[0]['post_ref'], '7594413916206288135')
check('likes/comments', (one[0]['likes'], one[0]['comments']), (1234, 56))
check('draft marker', one[0]['notes'].startswith('ocr_draft'), True)

none = o.draft_rows('h', '/x/a.png', 'kimono fabric', '2026-10-11 10:00')
check('no numbers → one draft row without metrics', (len(none), 'views' in none[0], none[0]['notes'].startswith('ocr_draft')), (1, False, True))

ig = o.draft_rows('wagara_no_kimochi', '/x/ig_screens/7.png', 'nothing readable', '2026-10-11 10:00')
check('IG screenshot number from file name', ig[0]['post_ref'], '7')
check('IG file-name note', 'IG screenshot no.' in ig[0]['notes'], True)

if FAILS:
    print('\n'.join(FAILS))
    sys.exit(1)
print('ok — all competitor_screens_ocr parsing checks passed')
