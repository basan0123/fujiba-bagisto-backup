# Sprint 2 — Operator Notes

This document covers the Sprint 2 deliverables: `JournalPoster`, the
`accounting:post-orders` / `accounting:post-refunds` cron commands, and the
read-only Journal UI. Sprint 1 (schema, COA, dashboard) is unchanged.

---

## 1. Manual triggers

Run interactively from the application root (`/var/www/bagisto`):

```bash
# Inspect what would be posted — no DB writes
php artisan accounting:post-orders --since=2026-04-01 --dry-run

# Post for real, all channels, since the last successful run
php artisan accounting:post-orders

# Restrict to a single channel (1=kimono, 2=mmfood)
php artisan accounting:post-orders --channel=2

# Refunds work the same way
php artisan accounting:post-refunds --since=2026-04-01 --dry-run
php artisan accounting:post-refunds
```

The `--since` value, when omitted, falls back to:
1. `fujiba_settings.last_order_post_run_at` (or `…_refund_…`); else
2. `now() - 30 days` (first-run safety net).

After a successful run the cursor in `fujiba_settings` is updated.

---

## 2. Verification SQL

After posting, balance-check every entry. The first query should always return
zero rows — a non-empty result is a bug:

```sql
-- Unbalanced entries (should be empty)
SELECT
  je.id, je.entry_no, je.entry_date, je.source_type,
  SUM(jl.debit)  AS dr,
  SUM(jl.credit) AS cr
FROM fujiba_journal_entries je
JOIN fujiba_journal_lines jl ON jl.journal_entry_id = je.id
GROUP BY je.id
HAVING dr <> cr;

-- Posted volumes by source
SELECT source_type, COUNT(*) AS entries,
       SUM(jl.debit) AS total_debit
FROM   fujiba_journal_entries je
JOIN   fujiba_journal_lines  jl ON jl.journal_entry_id = je.id
GROUP  BY source_type
ORDER  BY entries DESC;

-- Today's order postings
SELECT je.entry_no, je.reference_no, je.channel_id, je.memo,
       SUM(jl.debit) AS amount
FROM   fujiba_journal_entries je
JOIN   fujiba_journal_lines  jl ON jl.journal_entry_id = je.id
WHERE  je.source_type = 'order'
  AND  DATE(je.posted_at) = CURDATE()
GROUP  BY je.id
ORDER  BY je.id DESC;
```

The audit trail records every cron post:

```sql
SELECT id, table_name, record_id, action, user_name, created_at
FROM   fujiba_audit_log
WHERE  user_name LIKE 'cron:accounting:%'
ORDER  BY id DESC
LIMIT  20;
```

---

## 3. Backing out a bad run

If a cron run produced wrong entries (e.g. wrong account mapping, currency
glitch, partial deploy), back them out *atomically* and let the cron re-post
them next cycle. Only the bookkeeper or sysadmin should run these.

```sql
-- 1. Identify candidates first (read-only, NEVER skip this step)
SELECT je.id, je.entry_no, je.source_type, je.source_id, je.posted_at,
       SUM(jl.debit) AS amount
FROM   fujiba_journal_entries je
JOIN   fujiba_journal_lines  jl ON jl.journal_entry_id = je.id
WHERE  je.source_type = 'order'
  AND  je.posted_at BETWEEN '2026-05-08 12:00:00' AND '2026-05-08 13:00:00'
GROUP  BY je.id;

-- 2. Sanity check — must NOT include a fiscal-locked period.
SELECT DISTINCT je.fiscal_period_id, fp.status
FROM   fujiba_journal_entries je
JOIN   fujiba_fiscal_periods  fp ON fp.id = je.fiscal_period_id
WHERE  je.source_type = 'order'
  AND  je.posted_at BETWEEN '2026-05-08 12:00:00' AND '2026-05-08 13:00:00';
-- expected: status = 'open'. If 'closed' or 'locked', STOP — do not delete.

-- 3. Wrap the cleanup in a transaction. The journal_lines FK has
--    ON DELETE CASCADE so deleting the entry is sufficient.
START TRANSACTION;

DELETE FROM fujiba_journal_entries
WHERE  source_type = 'order'
  AND  posted_at BETWEEN '2026-05-08 12:00:00' AND '2026-05-08 13:00:00';

-- Verify
SELECT COUNT(*) FROM fujiba_journal_entries
WHERE source_type = 'order' AND posted_at BETWEEN '2026-05-08 12:00:00' AND '2026-05-08 13:00:00';

-- If correct, COMMIT; otherwise ROLLBACK and investigate.
COMMIT;

-- 4. Reset the cron cursor so the next run re-picks them up:
UPDATE fujiba_settings
SET    value      = '2026-05-08 11:55:00',
       updated_at = NOW()
WHERE  `key`      = 'last_order_post_run_at';
```

The audit log retains the original `created` rows even after deletion — it is
append-only by design, so the chain of custody is preserved.

---

## 4. Cron schedule

Both commands are registered in
[src/Providers/AccountingServiceProvider.php](src/Providers/AccountingServiceProvider.php)
and run automatically when Laravel's scheduler is wired into the system cron
(typically `* * * * * cd /var/www/bagisto && php artisan schedule:run`).

| Command | Cadence | Concurrency |
|---|---|---|
| `accounting:post-orders` | hourly (`:00`) | `withoutOverlapping(10)` + `onOneServer()` |
| `accounting:post-refunds` | hourly (`:15`) | `withoutOverlapping(10)` + `onOneServer()` |

Both append to `storage/logs/accounting-cron.log`. Tail it for live status:

```bash
tail -f /var/www/bagisto/storage/logs/accounting-cron.log
```

To temporarily disable a cron without removing the code, raise the cursor far
into the future:

```sql
UPDATE fujiba_settings
SET    value = '2099-01-01 00:00:00', updated_at = NOW()
WHERE  `key` = 'last_order_post_run_at';
```

(Or just `php artisan schedule:list` to confirm it's registered, and use OS
cron disable.)

---

## 5. Edge cases the cron silently *skips* (not errors)

These conditions log INFO/WARNING and increment `skipped`, but do **not**
flag the run as failed — important so one bad order doesn't block the queue:

- Order has `channel_id IS NULL` (corporate-level posting not implemented).
- Order date predates every open fiscal period (`ClosedPeriodException`).
- Refund references an order that has no anchor entry (税理士 may have
  manually entered it; we don't invent reversals).
- Refund references a missing order row (deleted/archived).

A non-zero exit code is reserved for *hard* errors (DB down, account code
missing from chart, balance mismatch). The cron monitor should alert on those.

---

## 6. Money convention

Internally, JPY is whole-yen integer. The schema stores `DECIMAL(15,2)` for
forward-portability with non-JPY books, but JournalPoster casts to int on
read and writes `N.00` on the wire. Any non-zero decimal in the DB is a bug.

The `ConsumptionTaxCalculator` applies the Japanese standard rounding for
内税: `tax = floor(gross × rate / (100 + rate))`, then `net = gross - tax`.
This rounds tax DOWN, matching how 国税庁 expects 内税 to settle.
