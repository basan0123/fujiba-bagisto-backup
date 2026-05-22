# V2 Rollback Plan

**Status:** draft, written 2026-05-12 during read-only prep. NOT EXECUTED.
**Pre-deploy master SHA:** `540dacbd3deba93b7e47a5502f5b0641e286acfc`
**v2 branch tip:** `812d2e8bcd2e8dc7186a6ce88e3e863f13592d84` (`feature/order-workflow-v2`)

> ⚠️ This document was drafted by Claude during a read-only prep session.
> It has not been validated against a real rollback. Verify each command
> against your actual deployment + tenant credentials before relying on it.

## Pre-conditions (so we can roll back at all)

Before any deploy, on every server you intend to deploy to, capture:

```bash
git rev-parse HEAD > /tmp/pre-deploy-sha.txt   # on the server
cat /tmp/pre-deploy-sha.txt                    # confirm
```

If `/tmp/pre-deploy-sha.txt` doesn't exist, you can't safely rollback to a known state. Don't deploy.

Take a mysqldump of each tenant DB *immediately before* the migrate step:

```bash
mysqldump --single-transaction --no-tablespaces \
  tenant_1bd1181f-fcd5-4238-a12d-a207d6ea4006 \
  > /var/backups/mysql/pre-v2/tenant_fujiba_$(date +%Y%m%d_%H%M%S).sql
# repeat for a6 and any other production tenant
```

If `/var/backups/mysql/pre-v2/` doesn't have a recent dump, don't deploy.

## Rollback — code only (Fujiba production)

Fujiba already has the v2 migrations (`canceled_at`, `fujiba_order_audit_log`)
applied. Code rollback only — leave the schema:

```bash
# On fujiba-server:
cd /var/www/bagisto
git fetch origin
git reset --hard $(cat /tmp/pre-deploy-sha.txt)
composer install --no-dev --no-interaction
php artisan view:clear
php artisan config:clear
php artisan route:clear
php artisan event:clear
```

The schema-ahead state (v2 columns on a tenant DB, pre-v2 code running) is
exactly the drift we have *today*. It's known to work — staff has been
operating in it for over a week.

## Rollback — code + schema (A6 production, and any tenant where you DID
## run migrations during the deploy)

If you ran `php artisan tenants:run migrate` and want to fully unwind it:

```bash
# On a6-server:
cd /var/www/bagisto
git reset --hard $(cat /tmp/pre-deploy-sha.txt)

# Drop the v2 migrations specifically. tenants:run migrate:rollback works
# only if the migrations are recorded with the right `batch` number.
# Confirm before running:
php artisan tenants:run --tenants=<a6-uuid> tinker --execute='
echo \Schema::hasColumn("orders", "canceled_at") ? "canceled_at present\n" : "canceled_at absent\n";
echo \Schema::hasTable("fujiba_order_audit_log") ? "audit_log present\n" : "audit_log absent\n";
'

# If both present, drop them — DDL is implicit-commit on MySQL so don't
# wrap in transaction (per memory feedback_ddl_in_transaction).
php artisan tenants:run --tenants=<a6-uuid> tinker --execute='
\Schema::table("orders", function ($t) {
    try { $t->dropIndex("orders_canceled_at_idx"); } catch (\Throwable $e) {}
    $t->dropColumn("canceled_at");
});
\Schema::dropIfExists("fujiba_order_audit_log");
echo "ok\n";
'

php artisan view:clear
php artisan config:clear
php artisan route:clear
php artisan event:clear
```

## Rollback — listeners that v2 added/removed/forgot

The v2 commit:

- **DELETES** `packages/Fujiba/InvoicesAdmin/src/Listeners/AutoConfirmCodPayment.php`
- **REMOVES** its `Event::listen('sales.invoice.save.after', ...)` registration from
  `InvoicesAdminServiceProvider.php`
- **ADDS** `packages/Fujiba/Notification/src/Listeners/AutoCreateInvoiceOnOrder.php`
- **ADDS** a `$this->app->booted(...)` block in `NotificationServiceProvider.php`
  that calls `forgetWebkulGenerateInvoiceListener()` — that block surgically
  removes `Webkul\Payment\Listeners\GenerateInvoice` from the
  `checkout.order.save.after` listener chain so it doesn't double-invoice.

A code-only `git reset --hard` to pre-deploy SHA restores all three states
automatically — the deleted file comes back, the registration line comes
back, the `AutoCreateInvoice` file disappears, the `forget` block
disappears. No manual listener-restoration commands needed *if* you reset
the code via git.

## Rollback — origin master if you pushed

If you ran `git push origin master` and rollback fires:

```bash
git fetch origin
git reset --hard <pre-deploy-sha>
git push origin master --force-with-lease
```

**This is destructive to shared history.** Anyone else who pulled the
post-v2 master will get a non-fast-forward on their next pull. Document
the force-push in #deploys (or wherever the team coordinates) immediately.
Don't use plain `--force`; `--force-with-lease` refuses if origin moved
since you fetched.

## Manual order-data cleanup if rollback fires mid-traffic

If customers placed orders between deploy and rollback, the v2 pipeline
may have written to `fujiba_order_audit_log` rows that no longer have a
matching code path on the rolled-back master. Those rows are harmless
(no FK to orders) but inspect for any anomalous side-effects:

```bash
php artisan tenants:run --tenants=<tenant> fujiba:audit-stock-state
```

Will fail with class-not-found after rollback (the command is in v2).
Use `RebuildOrderedInventories` instead — it's on master:

```bash
php artisan tenants:run --tenants=<tenant> fujiba:rebuild-ordered-inventories
# add --option=apply=1 only after reviewing output
```

Any orders created during the broken window need manual K-review:

```sql
SELECT id, increment_id, status, fujiba_label_printed, created_at
  FROM orders
  WHERE created_at BETWEEN '<deploy-start>' AND '<rollback-end>'
  ORDER BY id DESC;
```

## Who to ping

If production breaks and the rollback path above doesn't immediately
work, ping K. There is no second on-call engineer registered for this
deploy.
