#!/usr/bin/env python3
"""Unit tests for styling_concept QC thresholds + Burmese label. No network, no images.
    python3 test_styling_concept.py
"""
import os, sys
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import styling_concept as S  # noqa: E402

CASES = [  # (dE, inliers, expected_pass)
    (2.0, 400, True), (4.0, 250, True),        # boundaries pass
    (4.01, 400, False), (2.0, 249, False),      # just over / under
    (6.0, 100, False), (None, 400, False), (2.0, None, False),
]


def main():
    fails = 0
    for de, inl, exp in CASES:
        r = S.qc_decide(de, inl)
        ok = r['passed'] == exp and r['status'] == ('PASS' if exp else 'REJECTED')
        print(f'qc({de},{inl}) -> {r["status"]} reasons={r["reasons"]} {"ok" if ok else "FAIL"}')
        fails += not ok
    r = S.qc_decide(5.0, 10)
    fails += len(r['reasons']) != 2   # both failures recorded
    fails += not S.is_unicode_burmese(S.LABEL_MY)
    fails += S.is_unicode_burmese('ေက')      # Zawgyi-style leading e-vowel
    fails += S.is_unicode_burmese('AI concept')         # no Myanmar text
    print('PASS' if not fails else f'FAIL ({fails})')
    return 1 if fails else 0


if __name__ == '__main__':
    sys.exit(main())
