# Overnight Sprint Report — 2026-05-17/18

Single pos-native commit: `8675c43 overnight sprint: ttest tenant + un-mock screens + barcode listener`. Nothing pushed to remote (per spec).

## Phase 1: Ttest tenant — DONE

- **UUID**: `f05ea01d-259b-4566-9844-f2b60b98169c`
- **Subdomain**: https://ttest.snssale.com (wildcard `*.snssale.com` already resolves)
- **Logins** (both gateway-registered, password `PosTest2026`):
  - `ttest@snssale.com` → outlet 1 = **Ttest Kimono** = 366 products
  - `ttest-mmfood@snssale.com` → outlet 2 = **Ttest MMFood** = 534 products
- **Images copied**: 1,631 files, ~499 MB (real rsync, not symlinks; disk now 29% used, 69G free)
- **Verified end-to-end**: gateway POST `/api/auth/login` → JWT → GraphQL `getOutletProducts` → image fetches all return HTTP 200
- **postest deleted**: yes (central rows, tenant DB, image dir all removed)
- **No prod tenant writes**: all writes targeted Ttest's DB only; reads from Fujiba prod (`tenant_1bd1181f-...`) for the schema dump + INSERT…SELECT data copy

## Phases 2-8 (pos-native, one commit `8675c43`)

| Phase | Status | Notes |
|---|---|---|
| 2 — Translations | **DONE** | Root cause was device-locale fallback to Burmese on JP devices. Default locale now hard-pinned to `ja`; fallback chain `ja → en`. The actual ja.json strings were already complete (0 Burmese chars in en.json/ja.json). |
| 3 — Hide dev scaffolding | **DONE** | ComponentGallery + MOCK_PAIRED_DEVICES gated behind `__DEV__`; TrainingBanner already data-driven via `session.trainingMode` (Ttest's pos_user has `training_mode=0`). |
| 4 — Un-mock screens | **DONE** | OrderList, StockList, CustomerList, TodayDashboard, StaffList all wired to real GraphQL with empty-state fallbacks. featureFlags toggled: `ordersList=false`, `stock=false`, `reports=false`. |
| 5 — Forms | **DONE** | StaffEdit crash fixed (the 3 missing mutations `CREATE_OUTLET_STAFF/UPDATE_OUTLET_STAFF/DEACTIVATE_OUTLET_STAFF` were imported but never exported). Catalog screen got a +product button next to scan. Global barcode listener (`subscribeBarcodeScan`) wired in App.tsx + CatalogScreen. 請求書 added to sidebar. |
| 6 — Mobile images | **DONE** | Root cause: SalePhoneScreen + ProductSettingsScreen typed product as `{imageUrl: string \| null}` but the GraphQL query returns `images: [{url}]` → field was always undefined on phone. Now maps `images[0]?.url → imageUrl`. |
| 7 — Channel switcher | **PARTIAL (Phase 7-lite)** | True in-app channel switching isn't possible without backend work — the POS resolver hard-pins to `pos_user.outlet_id`. Shipped instead: 2 pos_users on Ttest (one per outlet), 2 gateway users, and a new StoreSwitchScreen that logs out + re-logs into the chosen outlet. See "What's blocked" below. |
| 8 — Polish | **PARTIAL** | Wired ProductSettings to real catalog (fixed same `imageUrl` bug). Burmese strings audit (zero hits in en.json/ja.json). Did NOT do: 38px tab-bar gap, 伝票 table-customer row, 分類の編集 PC max-width — these need browser-rendered confirmation on the iPad/PC the user owns. |

## What user should test first when waking

1. **Login**: https://ttest.snssale.com via gateway at https://snssale.com — `ttest@snssale.com` / `PosTest2026`
2. **Channel A (kimono)**: 366 products show; tap into Settings → 店舗を切り替え → MMFood; re-login with `ttest-mmfood@snssale.com` (same password) → 534 mmfood products
3. **Translations**: default UI should now stay in Japanese even on a device set to a non-supported locale
4. **Order list**: shows empty state "本日の注文はまだありません" (no orders on Ttest yet)
5. **Customer list**: empty state, not the old "Lin Mei / 田中 美咲" mock list
6. **Catalog**: scan a barcode on a USB HID scanner → product added to cart (always-on; no need to open the scan modal)
7. **StaffEdit**: tap "スタッフを追加" — form opens without the "Argument of <undefined>" crash

## What's broken / TODO for next sprint

- **True in-app channel switcher** (no relogin): needs either a `switchOutlet` GraphQL mutation that updates `pos_users.outlet_id`, or a per-request `X-Outlet-Id` header the HomeQuery resolver honors. Phase 7-lite shipped the picker UX but the actual switch costs a JWT round-trip + Apollo cache reset.
- **StoreSwitchScreen has Ttest's emails hardcoded** — when other merchants add multi-outlet, this needs a `/api/auth/me/outlets` gateway endpoint (or query gateway_user_tenants for all bridges).
- **OrderDetail + Refund screens still use mockOrders** — once Ttest gets its first real order, these need wiring (deferred because `refunds`/`voids` flags need backend ops that don't exist yet).
- **Reports topProducts + cashierLeaderboard sections** removed (were mocked). Need a server-side aggregation resolver before they can be real.
- **Pre-existing TypeScript errors** in PaymentScreen, CategoryEditScreen, etc — `tone` prop on Button no longer valid. Unrelated to this sprint, but they need cleanup.
- **The 38px tab-bar gap, 伝票 table-customer row, and 分類の編集 PC width** — listed in spec Phase 8 but require visual confirmation I can't do headless. They're tiny CSS adjustments; user can point at the exact line and I'll fix in 10 minutes.
- **EAS builds NOT attempted** (per spec "EAS build attempts fail (don't try APK in this sprint)").

## Git state

- **pos-native**: 1 commit `8675c43` on `main`, NOT pushed
- **bagisto**: no code commits; 2 doc files added (`docs/OVERNIGHT_SPRINT_PROGRESS.md`, `docs/OVERNIGHT_SPRINT_REPORT.md`), untracked. Phase 1 was DB + filesystem only — no bagisto source edits required.
- **Memory store**: not updated (sprint was self-contained; nothing surprising worth a memory file)

## Safety log

- Never wrote to any production tenant DB (`1bd1181f-...`, `e43ccde7-...`, `7016ca8c-...`, `855c3286-...`); all writes against `tenant_f05ea01d-...` and `bagisto_central` (gateway tables + tenant registry)
- Never ran pest/phpunit (the phpunit.xml landmine)
- No bagisto schema migrations
- No `git push`
