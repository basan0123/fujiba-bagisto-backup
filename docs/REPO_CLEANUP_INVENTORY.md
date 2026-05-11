# Repo cleanup inventory — 2026-05-11

Snapshot at start of cleanup. 50 modified files + 37 untracked items =
87 total entries from `git status --short`.

Sprint scope: cluster, decide commit/branch/discard per item, leave
master + feature branches in a clean state. **No production tenant
work.** All schema state below is read-only.

---

## Cluster 1 — Order Workflow Refactor v2

**Memory:** `project_order_workflow_refactor_v2.md` ("in flight 2026-05-11")
**State:** partial — design doc + extensive test coverage + new service
exist, but the controllers still mix old and new patterns (see DESIGN.md
notes about "scattered direct-DB-write controllers"). The migrations are
already applied to **Fujiba production** but **NOT** to A6, tenant1,
tenant2, or demo-store.

**Schema state (read-only check):**

| Tenant     | `orders.canceled_at` | `fujiba_order_audit_log` | Verdict          |
|------------|----------------------|--------------------------|------------------|
| Fujiba     | ✅ present           | ✅ present               | Schema ahead     |
| A6         | ❌                    | ❌                        | Repo ahead       |
| tenant1    | ❌                    | ❌                        | Repo ahead       |
| tenant2    | ❌                    | ❌                        | Repo ahead       |
| demo-store | ❌                    | ❌                        | Repo ahead       |

Files (52 entries):

**Modified:**
- `packages/Fujiba/OrdersAdmin/src/Http/Controllers/OrderAddressController.php`
- `packages/Fujiba/OrdersAdmin/src/Http/Controllers/OrderFulfillmentController.php`
- `packages/Fujiba/OrdersAdmin/src/Http/Controllers/OrderItemEditController.php` (+334)
- `packages/Fujiba/OrdersAdmin/src/Http/Controllers/OrderLabelPrintedController.php` (~118)
- `packages/Fujiba/OrdersAdmin/src/Http/Controllers/OrderListController.php`
- `packages/Fujiba/OrdersAdmin/src/Http/Controllers/OrderPaymentConfirmController.php`
- `packages/Fujiba/OrdersAdmin/src/Http/Controllers/OrderPaymentMethodController.php`
- `packages/Fujiba/OrdersAdmin/src/Http/Controllers/OrderStatusController.php` (~258)
- `packages/Fujiba/OrdersAdmin/src/Http/Controllers/OrderTrackingController.php`
- `packages/Fujiba/OrdersAdmin/src/Providers/OrdersAdminServiceProvider.php`
- `packages/Fujiba/OrdersAdmin/src/Resources/views/admin/orders-view.blade.php`
- `packages/Fujiba/OrdersAdmin/src/Routes/admin-api.php`
- `packages/Fujiba/OrdersAdmin/src/Services/FulfillmentRecalculator.php` (+275)
- `bootstrap/app.php` (+13 — scheduler entry for fujiba:audit-stock-state)
- `phpunit.xml` (+8 — OrdersAdmin testsuites)

**Untracked / new:**
- `packages/Fujiba/OrdersAdmin/DESIGN.md` (339 lines)
- `packages/Fujiba/OrdersAdmin/src/Console/Commands/AuditStockState.php`
- `packages/Fujiba/OrdersAdmin/src/Console/Commands/BackfillChatpionInvoices.php`
- `packages/Fujiba/OrdersAdmin/src/Console/Commands/FixStuckOrders.php`
- `packages/Fujiba/OrdersAdmin/src/Console/Commands/SnapshotStockBaseline.php`
- `packages/Fujiba/OrdersAdmin/src/Console/Commands/StatusCountsAudit.php`
- `packages/Fujiba/OrdersAdmin/src/Config/payment_methods.php`
- `packages/Fujiba/OrdersAdmin/src/DTO/OrderChangeIntent.php`
- `packages/Fujiba/OrdersAdmin/src/DTO/OrderDiff.php`
- `packages/Fujiba/OrdersAdmin/src/Events/OrderChanged.php`
- `packages/Fujiba/OrdersAdmin/src/Exceptions/RevertBlockedByWaybillException.php`
- `packages/Fujiba/OrdersAdmin/src/Http/Controllers/OrderMarkDeliveredController.php`
- `packages/Fujiba/OrdersAdmin/src/Listeners/DispatchFujibaNotifications.php`
- `packages/Fujiba/OrdersAdmin/src/Listeners/LogOrderAudit.php`
- `packages/Fujiba/OrdersAdmin/src/Listeners/RefreshInventoryIndices.php`
- `packages/Fujiba/OrdersAdmin/src/Services/FujibaPayment.php`
- `packages/Fujiba/OrdersAdmin/src/Services/InventoryReconcileGuard.php`
- `packages/Fujiba/OrdersAdmin/src/Services/OrderUpdateService.php` (836 lines)
- `packages/Fujiba/OrdersAdmin/src/Database/Migrations/2026_05_11_000001_add_canceled_at_to_orders_table.php`
- `packages/Fujiba/OrdersAdmin/src/Database/Migrations/2026_05_11_000002_create_fujiba_order_audit_log_table.php`
- `packages/Fujiba/OrdersAdmin/tests/TestCase.php`
- `packages/Fujiba/OrdersAdmin/tests/Unit/OrderChangeIntentTest.php`
- `packages/Fujiba/OrdersAdmin/tests/Unit/OrderItemEditBridgeTest.php`
- `packages/Fujiba/OrdersAdmin/tests/Unit/FulfillmentRecalculatorTest.php`
- `packages/Fujiba/OrdersAdmin/tests/Unit/InventoryReconcileGuardTest.php`
- `packages/Fujiba/OrdersAdmin/tests/Unit/OrderUpdateServiceSurfacesErrorsTest.php`
- `packages/Fujiba/OrdersAdmin/tests/Unit/OrderUpdateServiceGuardsTest.php`
- `packages/Fujiba/OrdersAdmin/tests/Feature/OrderUpdateServiceShippedTest.php`
- `packages/Fujiba/Notification/src/Listeners/OrderEventListener.php` (+40, audit-log integration)
- `packages/Fujiba/Notification/src/Providers/NotificationServiceProvider.php` (+45, event wiring)

**Action:** branch `feature/order-workflow-v2`. Includes bootstrap +
phpunit + OrderEventListener tweaks since they reference v2-only commands /
events. Do NOT merge to master. Schema-ahead-of-repo on Fujiba is the
single most fragile piece in the whole repo — branch isolates the risk.

---

## Cluster 2 — Bot Flow Manager (LiveChat)

**Memory:** `project_bot_flow_manager.md`
**State:** partial — admin UI exists (817-line blade), controller +
validator + BotFlowSender extensions present. The ChatPion-side patch
files in the package root (`chatpion_api_flow_manager.php` etc.) are
**not Laravel code**; they're snapshots intended for deployment to the
ChatPion host on `onamae` (project memory confirms ChatPion is a separate
host). They don't belong in this Laravel repo's autoload tree.

Files:
- `packages/Fujiba/LiveChat/Config/admin-menu.php` (+6, /admin/fujiba-livechat/flows menu item)
- `packages/Fujiba/LiveChat/Routes/admin.php` (+34, flows routes)
- `packages/Fujiba/LiveChat/src/Http/Controllers/Admin/LiveChatController.php` (+80)
- `packages/Fujiba/LiveChat/src/Resources/lang/en/app.php` (+1)
- `packages/Fujiba/LiveChat/src/Resources/lang/ja/app.php` (+1)
- `packages/Fujiba/LiveChat/src/Resources/views/admin/livechat/index.blade.php` (~18)
- `packages/Fujiba/LiveChat/src/Resources/views/admin/settings/form.blade.php` (~2)
- `packages/Fujiba/LiveChat/src/Services/BotFlowSender.php` (+65)
- **NEW** `packages/Fujiba/LiveChat/src/Http/Controllers/Admin/FlowManagerController.php` (282 lines)
- **NEW** `packages/Fujiba/LiveChat/src/Resources/views/admin/flows/index.blade.php` (817 lines)
- **NEW** `packages/Fujiba/LiveChat/src/Services/FlowValidator.php` (267 lines)
- **ChatPion-side (NOT autoloaded Laravel code):**
  - `packages/Fujiba/LiveChat/chatpion_api_flow_manager.php` (482 lines)
  - `packages/Fujiba/LiveChat/chatpion_api_flow_manager_v2.php` (525 lines)
  - `packages/Fujiba/LiveChat/chatpion_api_live_endpoints.php` (194 lines)

**Action:** branch `feature/bot-flow-manager`. ChatPion-side files
included — they're version-controlled alongside the Laravel side that
calls them, which is the existing convention (memory confirms ChatPion
patches live under `packages/Fujiba/LiveChat/` for handoff).

---

## Cluster 3 — Address Relax (channel-agnostic gating)

**Memory:** `feedback_address_relax_channel_default.md` — exact match.
The rule is "gate on `customers.chatpion_subscribe_id` not channel
code". The diffs implement that rule exactly.

**State:** complete and isolated. Three FormRequests + four blade views.
Logic is self-contained — no new service, no migration, no provider edit.

Files:
- `packages/Webkul/Admin/src/Http/Requests/CartAddressRequest.php` (+34, admin order edit)
- `packages/Webkul/Shop/src/Http/Requests/CartAddressRequest.php` (+29, storefront checkout)
- `packages/Webkul/Shop/src/Http/Requests/Customer/AddressRequest.php` (+26, account address)
- `packages/Webkul/Shop/src/Resources/views/checkout/onepage/address/form.blade.php` (+36)
- `packages/Webkul/Shop/src/Resources/views/customers/account/addresses/create.blade.php` (+14)
- `packages/Webkul/Shop/src/Resources/views/customers/account/addresses/edit.blade.php` (+14)

**Action:** commit to **master**. One commit, clear message, isolated
behavior change.

---

## Cluster 4 — Catalog Download (Fujiba/CatalogAdmin)

**State:** new feature, complete-looking — controller (593 lines), three
blade views, routes, config, ACL entries, lang. No migration.

Files:
- `packages/Fujiba/CatalogAdmin/src/Providers/CatalogAdminServiceProvider.php` (+8)
- `packages/Fujiba/CatalogAdmin/src/Resources/lang/en/app.php` (+60)
- `packages/Fujiba/CatalogAdmin/src/Resources/lang/ja/app.php` (+60)
- **NEW** `packages/Fujiba/CatalogAdmin/src/Config/acl.php`
- **NEW** `packages/Fujiba/CatalogAdmin/src/Config/admin-menu.php`
- **NEW** `packages/Fujiba/CatalogAdmin/src/Routes/admin.php`
- **NEW** `packages/Fujiba/CatalogAdmin/src/Http/Controllers/Admin/CatalogDownloadController.php` (593 lines)
- **NEW** `packages/Fujiba/CatalogAdmin/src/Resources/views/admin/catalog-download/index.blade.php` (107 lines)
- **NEW** `packages/Fujiba/CatalogAdmin/src/Resources/views/admin/catalog-download/pdf.blade.php` (69 lines)
- **NEW** `packages/Fujiba/CatalogAdmin/src/Resources/views/admin/catalog-download/stock-preview.blade.php` (87 lines)

**Action:** branch `feature/catalog-download`. Whole feature lives in one
package, no schema, but I haven't smoke-tested it — branching is safer.

---

## Cluster 5 — Smaller feature packages (Invoices/Inventory/Notification)

### 5a. Invoices Admin DataGrid override

**Memory:** `feedback_bagisto_datagrid_override_via_bind.md`
**State:** complete and isolated. Subclass + container bind.

Files:
- `packages/Fujiba/InvoicesAdmin/src/Providers/InvoicesAdminServiceProvider.php` (+31)
- **NEW** `packages/Fujiba/InvoicesAdmin/src/DataGrids/FujibaInvoiceDataGrid.php` (85)
- **NEW** `packages/Fujiba/InvoicesAdmin/src/DataGrids/FujibaCustomerDataGrid.php` (82)
- **NEW** `packages/Fujiba/InvoicesAdmin/src/Reporting/FujibaProductReporting.php` (85)

**Action:** commit to **master**. One commit.

### 5b. Inventory observer (realtime inventory→cart sync)

**Memory:** `feedback_inventory_index_realtime_observer.md`
**State:** complete. Observer + provider wiring.

Files:
- `packages/Fujiba/InventoryControl/src/Providers/InventoryControlServiceProvider.php` (+21)
- **NEW** `packages/Fujiba/InventoryControl/src/Observers/ProductInventoryObserver.php`
- **NEW** `packages/Fujiba/InventoryControl/src/Observers/ProductOrderedInventoryObserver.php`

**Action:** commit to **master**.

### 5c. AutoConfirmCodPayment → AutoCreateInvoiceOnOrder rename

**State:** appears complete — old listener deleted, new listener
exists. But `OrderEventListener.php` is **shared** with cluster 1 (also
modified for v2 audit-log integration). Need to check if the same hunks
are in both stories or if they're independent.

Files:
- **DELETED** `packages/Fujiba/InvoicesAdmin/src/Listeners/AutoConfirmCodPayment.php` (-39)
- **NEW** `packages/Fujiba/Notification/src/Listeners/AutoCreateInvoiceOnOrder.php` (73)
- `packages/Fujiba/Notification/src/Providers/NotificationServiceProvider.php` — modified (shared with cluster 1)
- `packages/Fujiba/Notification/src/Listeners/OrderEventListener.php` — modified (shared with cluster 1)

**Action:** cluster 5c stays bundled with **cluster 1** because the
NotificationServiceProvider + OrderEventListener edits are entangled.
Listing here for transparency.

---

## Cluster 6 — Misc improvements

### 6a. Top-selling reports: thumbnails

**State:** complete, isolated cohesive change.

Files:
- `packages/Webkul/Admin/src/Helpers/Reporting.php` (+8)
- `packages/Webkul/Admin/src/Resources/views/reporting/products/top-selling-by-quantity.blade.php` (~23)
- `packages/Webkul/Admin/src/Resources/views/reporting/products/top-selling-by-revenue.blade.php` (~23)
- `packages/Webkul/Admin/src/Resources/views/reporting/view.blade.php` (~23, `column.type === 'image'` support)

**Action:** commit to **master**.

### 6b. Customers index: avatar + last cart

**State:** complete, isolated.

Files:
- `packages/Webkul/Admin/src/Resources/views/customers/customers/index.blade.php` (+22)

**Action:** commit to **master**.

### 6c. Receipt totals fix (shipping/subtotal incl-tax fallback)

**State:** complete, isolated bugfix. Per the memory
`feedback_view_render_event_escaping.md` neighborhood — this is a
different bug (inclusive-tax mirror falls back to base when tax=0 to
avoid stale drift after recalc).

Files:
- `packages/Webkul/Admin/src/Resources/views/sales/orders/receipt.blade.php` (+8)

**Action:** commit to **master**.

### 6d. Inventory-source + shipped email templates: array access fix

**State:** complete, isolated bugfix. `$additionalDetails->title`
crashes when the variable arrives as an array; switched to
`$additionalDetails['title']`.

Files:
- `packages/Webkul/Admin/src/Resources/views/emails/orders/inventory-source.blade.php` (+4)
- `packages/Webkul/Admin/src/Resources/views/emails/orders/shipped.blade.php` (+4)
- `packages/Webkul/Shop/src/Resources/views/emails/orders/shipped.blade.php` (+4)

**Action:** commit to **master**.

---

## Cluster 7 — Tracked artifacts (chatpion-sync log timestamps)

`storage/chatpion-sync/last_run_*.txt` — 6 files, +2/-2 each. These are
cron-log timestamps that bump on every hourly sync.

**Action:** add a `last_run_*.txt` ignore pattern to `.gitignore` since
they're operational state, not source. (Memory
`project_chatpion_hourly_cron.md` confirms these are cron output.)

---

## Cluster 8 — Untracked operational + audit files

- `UI-POLISH-AUDIT.md` (89 lines, in repo root) — Phase A audit doc;
  memory entry `project_ui_polish_phase_b_handoff.md` notes Phase A done
  and refers to this file. Move to `docs/` and commit so it's findable.
- `storage/chatpion-deploy/` — 4 PHP backup snapshots (~340 KB total)
  + 2 small JSON files. These are deployment backups from the Bot Flow
  Manager rollout to ChatPion. **Operational state, not source.**
  → Add `/storage/chatpion-deploy/` to `.gitignore`. Files stay on disk.
- `storage/chatpion-sync/cron.log.1` — rotated cron log.
  → Already-existing `/storage/logs/*` ignore covers this kind of thing;
  add explicit `/storage/chatpion-sync/cron.log*` entry to make it
  unambiguous.

---

## Summary

| Cluster                              | Action          | Branch / target              |
|--------------------------------------|-----------------|------------------------------|
| 1. Order Workflow Refactor v2 (+5c) | branch          | `feature/order-workflow-v2`  |
| 2. Bot Flow Manager                  | branch          | `feature/bot-flow-manager`   |
| 3. Address Relax                     | **commit**      | `master`                     |
| 4. Catalog Download                  | branch          | `feature/catalog-download`   |
| 5a. Invoices DataGrid override       | **commit**      | `master`                     |
| 5b. Inventory observer               | **commit**      | `master`                     |
| 6a. Reports thumbnails               | **commit**      | `master`                     |
| 6b. Customers avatar                 | **commit**      | `master`                     |
| 6c. Receipt totals fix               | **commit**      | `master`                     |
| 6d. Email array-access fix           | **commit**      | `master`                     |
| 7. chatpion-sync stamps              | `.gitignore`    | n/a                          |
| 8. operational backups + audit       | move + ignore   | `master` (audit doc only)    |

Net result: master gets a stack of small, isolated, complete bugfixes
and feature additions; three feature branches isolate the in-flight
work; `.gitignore` updated to stop tracking cron output.
