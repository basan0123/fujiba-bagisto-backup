import os
import sys
import tempfile
import unittest

import numpy as np
from PIL import Image

HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, HERE)
import families  # noqa: E402
import fam_collection as C  # noqa: E402
import fabric_gate as fg  # noqa: E402  (collection puts bin/intel on sys.path)


class Catalog(unittest.TestCase):
    def test_concatenated_description(self):
        d = 'Product code: FV-89-BColor: B (Black)Pattern: Kaika (blooming flower) printFabric: Hosonuno (100% cotton)'
        r = C.catalog_fields(d, d)
        self.assertEqual(r['colour_en'], 'Black')
        self.assertIsNone(r['colour_ja'])                      # JA line has no Japanese -> EN only
        self.assertEqual(r['pattern'], 'Kaika (blooming flower) print')
        self.assertEqual(r['fabric'], C.HOSONUNO)

    def test_japanese_name_only_from_ja_catalog(self):
        r = C.catalog_fields('Color: C (Red)', 'Color: C (赤)')
        self.assertEqual((r['colour_en'], r['colour_ja']), ('Red', '赤'))

    def test_missing_fields(self):
        r = C.catalog_fields('', '')
        self.assertEqual(r, {'colour_en': None, 'colour_ja': None, 'pattern': None, 'fabric': None})


class Washi(unittest.TestCase):
    def test_deterministic_and_plain(self):
        a = np.asarray(C.washi(200, 160, 5)).astype(int)
        b = np.asarray(C.washi(200, 160, 5)).astype(int)
        self.assertTrue((a == b).all())
        self.assertLess(np.abs(a.mean((0, 1)) - np.array(C.PAPER)).max(), 8)


class Registry(unittest.TestCase):
    def test_registered(self):
        self.assertIn(8, families.REGISTRY)
        self.assertIn(8, families.ORDER)
        self.assertIn(8, families.enabled_ids())
        self.assertEqual(families.get(8).name, 'collection')


class Geometry(unittest.TestCase):
    """Folded flap + circle on a synthetic photo: gate master passes, inverse warp passes."""

    def test_fold_and_circle_gate(self):
        rng = np.random.default_rng(1)
        photo = np.asarray(Image.fromarray(rng.integers(0, 255, (60, 80, 3), dtype=np.uint8)).resize((800, 600), Image.BICUBIC))
        with tempfile.TemporaryDirectory() as td:
            src = os.path.join(td, 'p.png')
            Image.fromarray(photo).save(src)
            fam = C.Collection('C')
            fam.begin(C.PAPER)
            fam.crops, fam._pending_flaps = [], []
            fam.safe_rect = lambda path, aspect: (20, 20, 700, int(700 / aspect))
            fam.folded(src, 60, 200, 330, 50, 240)
            fam.circle(src, (100, 100, 200, 200), 430, 220, 250)
            out = os.path.join(td, 'm.png')
            inv = fam.verify_flaps(os.path.join(td, 'm'))
            fam.texts.append({'text': 'x', 'size': 30})
            fam.finish(out, {'product_id': 1, 'name': 'T', 'price': 1})
            self.assertTrue(inv['pass'], inv)
            g = fg.gate_master(out, {**fg.DEFAULTS, **fg.MASTER_DEFAULTS})
            fab = [c for c in g['checks'] if c['check'].startswith('fabric')]
            self.assertEqual(len(fab), 2)
            self.assertTrue(all(c['pass'] for c in fab), fab)


if __name__ == '__main__':
    unittest.main()
