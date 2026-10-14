#!/usr/bin/env python3
"""READ-ONLY watermark scan of every ENABLED fujiba product image.

Products: product_flat channel='default' locale='en' status=1; images: fi_catalog_images
(primary = lowest position, plus all other images). Nothing in the DB/catalogue is changed.
Writes storage/app/intel/twin/watermark_scan_enabled.csv.

    wm_scan_enabled.py [--ocr]      default template-only (script + white); OCR is 15-30 s/img
"""
import argparse, csv, json, os, sys, time
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import heavy_guard  # noqa: E402

ROOT = '/var/www/bagisto/storage/app/public/tenants/1/1bd1181f-fcd5-4238-a12d-a207d6ea4006'
OUT = '/var/www/bagisto/storage/app/intel/twin/watermark_scan_enabled.csv'
SQL = """SELECT pf.sku, pf.product_id, REPLACE(REPLACE(pf.name, '\\t', ' '), '\\n', ' '), i.path, i.position
FROM product_flat pf JOIN fi_catalog_images i ON i.product_id = pf.product_id
WHERE pf.channel='default' AND pf.locale='en' AND pf.status=1 ORDER BY pf.product_id, i.position, i.id;"""


def main():
    ap = argparse.ArgumentParser(); ap.add_argument('--ocr', action='store_true'); a = ap.parse_args()
    heavy_guard.acquire('wm_scan_enabled')
    import cv2
    import fi_usage, wm_detect
    rows = [l.split('\t') for l in fi_usage._mysql(SQL).splitlines() if l.strip()]
    seen, t0, n_det = set(), time.time(), 0
    with open(OUT + '.tmp', 'w', newline='') as f:
        w = csv.writer(f)
        w.writerow(['sku', 'product_id', 'public_name', 'image_path', 'image_role', 'score', 'method', 'detected'])
        for sku, pid, name, path, pos in rows:
            role = 'primary' if pid not in seen else 'other'
            seen.add(pid)
            img = cv2.imread(os.path.join(ROOT, path))
            if img is None:
                w.writerow([sku, pid, name, path, role, '', 'unreadable', '']); continue
            r = wm_detect.detect(img, use_ocr=a.ocr)
            cands = [('script', r['script']['score'], r['script']['hit']),
                     ('white', r['white']['score'], r['white']['hit'])]
            if a.ocr:
                cands.append(('ocr', len(r['ocr']['reads']), r['ocr']['hit']))
            hits = [c for c in cands if c[2]]
            m = hits[0] if hits else max(cands[:2], key=lambda c: c[1] / (wm_detect.SCRIPT_THRESH if c[0] == 'script'
                                                                          else wm_detect.WHITE_THRESH))
            n_det += bool(hits)
            w.writerow([sku, pid, name, path, role, m[1], '+'.join(c[0] for c in hits) or m[0] + '(below)',
                        int(bool(hits))])
            f.flush()
    os.replace(OUT + '.tmp', OUT)
    print(json.dumps({'csv': OUT, 'images': len(rows), 'products': len(seen), 'detected_images': n_det,
                      'ocr': a.ocr, 'sec': round(time.time() - t0)}))


if __name__ == '__main__':
    sys.exit(main())
