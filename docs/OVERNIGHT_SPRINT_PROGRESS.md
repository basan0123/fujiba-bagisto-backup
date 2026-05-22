# Overnight Sprint Progress

Started: 2026-05-17 (overnight, user sleeping)

## Phase 1.1: Pre-flight — DONE
- Disk: 70G free of 96G (27% used) — OK
- Fujiba prod tenant 1bd1181f-fcd5-4238-a12d-a207d6ea4006: confirmed
- postest tenant 47612f52-e1b1-4253-bfa0-6cbed258cda9: confirmed
- Channels: id=1 default/kimono (370 products), id=2 mmfood (535 products)
- pos-native repo: /var/www/pos-native
- nginx wildcard `*.snssale.com` already serves bagisto — ttest.snssale.com will resolve
- postest image dirs are SYMLINKS to Fujiba prod (existing pattern); spec wants FULL COPY for Ttest — will rsync

## Phase 1.2: Provision Ttest — IN PROGRESS
- Ttest UUID: f05ea01d-259b-4566-9844-f2b60b98169c
- Subdomain: ttest.snssale.com
- DB: tenant_f05ea01d-259b-4566-9844-f2b60b98169c

## Phase 1: COMPLETE — Ttest tenant fully operational

- **UUID**: f05ea01d-259b-4566-9844-f2b60b98169c
- **Subdomain**: ttest.snssale.com
- **Login**: ttest@snssale.com / PosTest2026
- **Products**: kimono=370 (366 saleable via pos_outlet_product), mmfood=535
- **Images**: 1631 files copied (~499 MB), all serve HTTP 200
- **End-to-end**: gateway login → JWT → GraphQL getOutletProducts → image fetch all 200
- **Outlets**: id=1 Ttest Kimono, id=2 Ttest MMFood (matching pos_outlet_product mapping)
- **POS user**: ttest (id=1) attached to outlet=1 (kimono default)
- **Note**: outlet IDs were re-aligned to 1/2 (not 3/4) so pos_outlet_product rows copied from Fujiba prod resolve correctly

## Phase 1.11: Delete postest — in progress

## Phase 7 — Channel switcher (Phase 7-lite shipped)

Approach: spec called for "Option B' tap-to-pick channel_id stored in app state". The Bagisto POS resolver (`Webkul\Pos\Queries\Home\HomeQuery@getProducts`) filters by `pos_outlet_product.outlet_id = $agent->outlet_id` — outlet is hard-pinned to the authenticated `pos_user`. A runtime channel switch without re-issuing a JWT would require either:

  - a `switchOutlet` GraphQL mutation that updates the pos_user row, OR
  - a per-request `X-Outlet-Id` header honored by the resolver

Both need backend work (out of scope per "no schema migrations" constraint).

**Shipped instead (Phase 7-lite):**
- Created `ttest-mmfood@snssale.com` as a second pos_user on Ttest (outlet_id=2), registered with the auth gateway. Same tenant DB.
- New `StoreSwitchScreen` (Settings → "店舗を切り替え") shows both outlets, logs out + routes to Login pre-filled with the chosen email.
- `LoginScreen` now reads `route.params.prefillEmail` to seed the form.
- Hardcoded `TTEST_STORES` list (kimono/mmfood) — when a real merchant has multi-outlet, this should be replaced by a future `/api/auth/me/outlets` gateway endpoint.

Tested: both gateway logins return JWTs; `getOutletProducts` with kimono JWT returns 366 products, with mmfood JWT returns 534.
