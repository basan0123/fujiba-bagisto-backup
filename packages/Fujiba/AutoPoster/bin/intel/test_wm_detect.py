#!/usr/bin/env python3
"""Unit test for wm_detect / photo_audit watermark_present on the FV-89 colour set.
Read-only. Skips (exit 0) if the product photos are not on disk.

    python3 test_wm_detect.py
"""
import os, sys
import cv2
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import wm_detect  # noqa: E402

ROOT = '/var/www/bagisto/storage/app/public/tenants/1/1bd1181f-fcd5-4238-a12d-a207d6ea4006/product'
POS = {'FV-89-B': f'{ROOT}/2075/FV-89-B.jpeg', 'FV-89-C': f'{ROOT}/2108/FV-89-C.jpeg',
       'FV-89-D': f'{ROOT}/2077/FV-89-D.jpeg',
       'FV-61-C': f'{ROOT}/2195/FV-61-C.jpeg'}   # faint blue mark at ~-60 deg
# known watermark-free fabric windows inside those photos (x, y, w, h)
NEG = {'FV-89-B': (980, 1400, 620, 480), 'FV-89-D': (1000, 1450, 600, 450)}


def main():
    if not all(os.path.exists(p) for p in POS.values()):
        print('SKIP: photos missing'); return 0
    fails = 0
    for code, p in POS.items():
        img = cv2.imread(p)
        r = wm_detect.detect(img)
        ok = r['watermark_present']
        print(f"{code}: present={ok} script={r['script']['score']} ocr={r['ocr']['hit']} boxes={r['boxes']}")
        fails += not ok
        if code in NEG:
            x, y, w, h = NEG[code]
            n = wm_detect.detect(img[y:y + h, x:x + w])
            print(f"{code} clean window: present={n['watermark_present']} script={n['script']['score']}")
            fails += n['watermark_present']
        rect, _, rc = wm_detect.wm_free_crop(img, 0.75, wm=r)
        print(f"{code} wm-free crop 3:4 = {rect} ocr_recheck_hit={rc and rc['ocr_hit']}")
        fails += rect is None or any(wm_detect._overlaps(rect, b) for b in r['boxes'])
    print('PASS' if not fails else f'FAIL ({fails})')
    return 1 if fails else 0


if __name__ == '__main__':
    sys.exit(main())
