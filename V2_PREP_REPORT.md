# V2 Deploy Prep Report (READ-ONLY)

Written 2026-05-12 by Claude during read-only prep, after K's audit of
fictional "shipped" claims earlier today. **Master was NOT modified.
Origin was NOT pushed. No server was touched.**

## TL;DR

- **You should not deploy v2 tonight.** The merge surfaces 4 real conflicts
  AND deletes 23 POS files that landed on master AFTER v2 was branched.
  Merging as-is would lose POS Merchant F2/F3/F4/F6 work.
- The merge is doable but it's not a clean automatic merge. A human needs
  to resolve conflicts and decide whether to rebase v2 onto current master
  first.
- The factory-based "automated verification" in the deploy plan won't run
  on a fresh tenant — Bagisto's OrderFactory doesn't seed enough fixture
  data. Auto-rollback would fire on a false negative.
- Master HEAD is still `540dacbd3deba93b7e47a5502f5b0641e286acfc`,
  unchanged. The throwaway test branch has been deleted.
- Rollback plan: `V2_ROLLBACK_PLAN.md` (in repo root). Not committed yet.

## SHAs

| What | SHA |
|---|---|
| master HEAD (pre-deploy / rollback target) | `540dacbd3deba93b7e47a5502f5b0641e286acfc` |
| feature/order-workflow-v2 tip | `812d2e8bcd2e8dc7186a6ce88e3e863f13592d84` |
| Merge base of master ↔ v2 (where they diverged) | run `git merge-base master feature/order-workflow-v2` |

## What v2 contains (real numbers)

Diff against master:

```
88 files changed, 4772 insertions(+), 3002 deletions(-)
```

- **NEW** (canonical pipeline): `OrderUpdateService.php`, DTOs
  (`OrderChangeIntent`, `OrderDiff`), `OrderChanged` event, 3 listeners
  (`RefreshInventoryIndices`, `DispatchFujibaNotifications`,
  `LogOrderAudit`), `RevertBlockedByWaybillException`, `FujibaPayment`
  storefront filter, `OrderMarkDeliveredController`.
- **NEW** (audit / ops): `AuditStockState`, `FixStuckOrders`,
  `SnapshotStockBaseline`, `StatusCountsAudit`, `BackfillChatpionInvoices`
  artisan commands.
- **NEW** (schema): `2026_05_11_000001_add_canceled_at_to_orders_table.php`,
  `2026_05_11_000002_create_fujiba_order_audit_log_table.php`.
  **Note: these migrations have already been applied to Fujiba production
  tenant DB.** Schema drift is real.
- **NEW** (tests): 4 unit tests + 1 feature test in
  `packages/Fujiba/OrdersAdmin/tests/`.
- **MODIFIED** (pipeline migration): 9 controllers in
  `packages/Fujiba/OrdersAdmin/src/Http/Controllers/` (Status, Address,
  Fulfillment, ItemEdit, LabelPrinted, List, PaymentConfirm, Payment,
  Tracking).
- **MODIFIED** (listener rewire): `InvoicesAdminServiceProvider.php`,
  `NotificationServiceProvider.php`, `OrderEventListener.php`.
- **DELETED**: `Fujiba/InvoicesAdmin/src/Listeners/AutoConfirmCodPayment.php`.

## What v2 also deletes — possibly accidentally

The diff includes **23 POS file deletions** that look like they shouldn't
be part of v2:

```
packages/Webkul/Pos/src/Database/Migrations/2026_05_11_100001_create_pos_stock_movement_table.php
packages/Webkul/Pos/src/Database/Migrations/2026_05_11_100002_create_pos_refund_log_table.php
packages/Webkul/Pos/src/Database/Migrations/2026_05_11_110001_add_pin_to_pos_users_table.php
packages/Webkul/Pos/src/Helpers/ImageUploader.php
packages/Webkul/Pos/src/Helpers/RoleGate.php
packages/Webkul/Pos/src/Mutations/Outlet/CategoryMutation.php
packages/Webkul/Pos/src/Mutations/Outlet/ProductMutation.php
packages/Webkul/Pos/src/Mutations/Outlet/RefundMutation.php
packages/Webkul/Pos/src/Mutations/Outlet/StaffMutation.php
packages/Webkul/Pos/src/Mutations/Outlet/StockMutation.php
packages/Webkul/Pos/src/Queries/Outlet/CategoryQuery.php
packages/Webkul/Pos/src/Queries/Outlet/StaffQuery.php
packages/Webkul/Pos/src/Resources/lang/en/app.php (80-line deletion)
packages/Webkul/Pos/src/graphql/outlet/categories/manage.graphql
packages/Webkul/Pos/src/graphql/outlet/orders/manage.graphql
packages/Webkul/Pos/src/graphql/outlet/products/manage.graphql
packages/Webkul/Pos/src/graphql/outlet/staff/manage.graphql
packages/Webkul/Pos/src/graphql/outlet/stock/manage.graphql
```

These match the recent master commits the developer's audit named:

```
8af7f27 docs: SPRINT_MERCHANT_WIRING_REPORT.md
329ee1a feat(pos): refundOrder + voidOrder mutations (Merchant F6 backend)
4459657 feat(pos): Staff CRUD + PIN management (Merchant F4)
... (Category F2, Stock F3 etc.)
```

**Almost certainly:** `feature/order-workflow-v2` was branched from master
BEFORE the POS Merchant work landed. Merging v2 into current master via
`git merge` would auto-resolve these as deletions, **silently removing the
POS work from master**. That's catastrophic.

**Recommended fix:** rebase `feature/order-workflow-v2` onto current master
first (`git rebase master` on the v2 branch), resolve any conflicts that
surface during the rebase, then merge the rebased v2 into master. The
deletions disappear once v2 sits on top of the POS commits instead of
behind them.

## Merge conflicts surfaced by the throwaway test

I ran `git merge feature/order-workflow-v2 --no-commit --no-ff` on a
throwaway branch (`throwaway/v2-merge-test-2026-05-12`, deleted after) to
surface conflicts without touching master.

```
CONFLICT (add/add):   packages/Fujiba/OrdersAdmin/src/Config/payment_methods.php
CONFLICT (content):   packages/Fujiba/OrdersAdmin/src/Http/Controllers/OrderPaymentMethodController.php
CONFLICT (content):   packages/Fujiba/OrdersAdmin/src/Providers/OrdersAdminServiceProvider.php
CONFLICT (add/add):   packages/Fujiba/OrdersAdmin/src/Services/InventoryReconcileGuard.php
```

Both `payment_methods.php` and `InventoryReconcileGuard.php` were created on
both sides (commit `f8cd5a8` on master added them as stubs; v2 added them
as full pipeline pieces). The conflicts are mechanical and easy to
resolve — keep the v2 version in both cases, the f8cd5a8 versions were
intentionally stub-shaped pending v2.

`OrderPaymentMethodController.php` and `OrdersAdminServiceProvider.php`
conflict because `f8cd5a8` and v2 both modify them in overlapping
regions. The v2 version supersedes f8cd5a8's stub in both files.

These 4 conflicts plus the POS-deletion issue mean: **the merge is not
"clean automatic," and it should not run unattended.**

## What `git status` actually looks like right now

After the throwaway test was cleaned up and stash restored:

```
 M packages/Fujiba/PwaFrontend/src/Http/Controllers/Api/PaymentController.php
 M packages/Fujiba/SagawaDelivery/src/DTO/SagawaShipmentPayload.php
 M packages/Fujiba/SagawaDelivery/src/Database/Migrations/2026_05_04_000001_add_daibiki_columns_to_sagawa_credentials.php
 D packages/Fujiba/SagawaDelivery/src/Exceptions/SagawaCodNotConfiguredException.php
 M packages/Fujiba/SagawaDelivery/src/Models/Credential.php
 M packages/Fujiba/SagawaDelivery/src/Services/SagawaShipmentBuilder.php
 M packages/Fujiba/SagawaDelivery/tests/Unit/SagawaShipmentBuilderCodTest.php
 M packages/Webkul/Payment/src/Payment.php
 M packages/Webkul/Shop/src/Http/Controllers/API/OnepageController.php
?? docs/ACCOUNTING-STATE-2026-05-12.md
```

**This is more than the "3 storefront filter files"** referenced in the
deploy plan. The 9 modified + 1 deleted touch Sagawa, PWA, Payment,
Shop checkout, and an accounting doc. Some of this looks like ongoing
work I have no context for (Sagawa Credentials, SagawaShipmentBuilder).

**The PaymentController + Payment + OnepageController triplet** does
match `feedback_admin_only_payment_methods.md` (the storefront filter
that gates `cash` off the shop). Those three are intentional. The
Sagawa changes I can't categorise.

Before deploying v2, decide what to do with each:
- Storefront filter triplet → commit on master BEFORE v2 merge (the
  deploy plan was right about this).
- Sagawa changes → ask K what they are. Not safe to lump into the v2
  merge commit.
- Accounting doc → harmless to leave or to commit separately.

## Why the auto-verification in the deploy plan won't work

Phase 2.2 of the deploy plan uses `App\Models\Order::factory()->create()`
in a Tinker block. Bagisto's `OrderFactory` (in
`packages/Webkul/Sales/src/Database/Factories/OrderFactory.php`) is
minimal — it sets only the order-row scalar columns. It does NOT seed:

- `order_items` (the Order has no items → `qty_to_invoice = 0` → service
  bails)
- `order_payment` (the Order has no payment → COD detection fails)
- `order_address` (Shop layout will null-deref shipping_address)
- a `customer` + `product` + `inventory_indices` + `channel` (`channel_id`
  defaults to `1`, which may not exist on a fresh tenant)

The test would crash on the very first `$order->items->first()` access
because `->items` is empty. The auto-rollback in Phase 2.2 would then
fire on a false negative and undo a perfectly-working v2 deploy.

A real automated test would either:
- Use a hand-built fixture order (insert via DB::table to bypass
  factories), or
- Run against the existing dataset on tenant1/tenant2 staging where
  real orders already exist (but then "auto-rollback" wipes test data
  too).

The integration test that already exists in v2
(`OrderUpdateServiceShippedTest.php`) does the right thing — uses
`DatabaseTransactions` against a live tenant DB — but it self-skips on
the central context, and the deploy plan doesn't run it.

## Recommended path (not for tonight)

1. **Don't deploy tonight.** Sleep. Production is running. The bugs are
   known. Schema/code drift has been stable for a week.

2. **Tomorrow with clear head:** rebase v2 onto current master. The
   conflict count probably stays small; the POS deletions go away.

3. **Hand the rebased v2 + the rollback plan + this prep report to a
   second reviewer.** Not K, not the developer who wrote v2. Someone
   outside this loop, even a freelance Laravel reviewer for half a day.

4. **Deploy to tenant1 first**, manually verify (not factory-based) by
   logging into the staging admin and clicking through:
   - Change shipping method on an order → does it save?
   - Mark Shipped → does status advance + stock release?
   - Set order to pickup → does cash payment appear in dropdown?
   - Storefront checkout → is cash absent?

5. **Then Fujiba.** Schema already there, code-only deploy.

6. **Then A6.** Needs migration + code.

## Files in this report

- `V2_PREP_REPORT.md` (this file) — read-only evidence + recommendation
- `V2_ROLLBACK_PLAN.md` — drafted rollback procedure if/when v2 ships

Neither is committed. K to commit or discard tomorrow.

## What I did NOT do

- Did not push to origin
- Did not merge to master
- Did not modify master in any way (`master` is still `540dacbd`)
- Did not SSH to any server
- Did not run migrations on any tenant DB
- Did not run `composer dump-autoload` (just `git stash` / `git stash pop`)
- Did not commit the 3 storefront filter files (or any of the 9 dirty
  files in the working tree)
- Did not run the test suite — it would have crashed on master since
  `OrderUpdateService` doesn't exist there, and running it on the
  throwaway-merge branch with unresolved conflicts is meaningless

## What CAN safely be done now (still read-only)

- Show K (or a second reviewer) `V2_PREP_REPORT.md` + `V2_ROLLBACK_PLAN.md`
- Have them read `packages/Fujiba/OrdersAdmin/src/Services/OrderUpdateService.php`
  on the v2 branch directly via `git show feature/order-workflow-v2:<path>`
- Decide rebase-then-merge vs. abandon-v2-rewrite-on-master
- Sleep.
