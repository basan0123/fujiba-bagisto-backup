#!/usr/bin/env python3
"""Driver for production-concept families 9-11 (PREVIEW ONLY, FREE). Same pipeline as run.py.

    nice -n 10 python3 run_production.py <family_id> <ctx.json> <out_dir> [product_id ...]

Families 9-11 are enabled=False so batch.py / the nightly cron never render them.
"""
import sys

import run  # noqa: E402  (acquires heavy_guard, sets paths)
import fam_production  # noqa: E402,F401  (registers 9-11)

if __name__ == '__main__':
    sys.exit(run.main())
