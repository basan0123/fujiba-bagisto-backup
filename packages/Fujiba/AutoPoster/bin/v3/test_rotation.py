import os
import sys
import unittest

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from rotation import next_family  # noqa: E402

ORDER = [1, 2, 3, 4, 5, 6]


class T(unittest.TestCase):
    def test_skips_disabled_and_wraps(self):
        en = {3, 4, 5, 6}
        self.assertEqual(next_family(ORDER, en, None), 3)
        self.assertEqual(next_family(ORDER, en, 3), 4)
        self.assertEqual(next_family(ORDER, en, 6), 3)

    def test_never_repeats(self):
        prev = None
        for _ in range(20):
            nxt = next_family(ORDER, {3, 5}, prev)
            self.assertNotEqual(nxt, prev)
            prev = nxt

    def test_only_previous_enabled(self):
        self.assertIsNone(next_family(ORDER, {3}, 3))
        self.assertIsNone(next_family(ORDER, set(), None))


if __name__ == '__main__':
    unittest.main()
