# Sprint 5 — Reports / 決算書 (Operator Runbook)

Read-only financial reports built on top of the journal data already
posted by Sprints 2–4. No schema changes; no new posting logic.

---

## 1. Reports shipped

| Path | Report | Period input | Exports |
|---|---|---|---|
| `/admin/accounting/reports/trial-balance?year_month=YYYY-MM` | 試算表 (monthly) | year_month | PDF + CSV |
| `/admin/accounting/reports/trial-balance?fiscal_year=YYYY` | 試算表 (annual) | fiscal_year | PDF + CSV |
| `/admin/accounting/reports/pl?from=YYYY-MM-DD&to=YYYY-MM-DD` | 損益計算書 (P/L) | from / to dates | PDF + CSV |
| `/admin/accounting/reports/bs?as_of=YYYY-MM-DD` | 貸借対照表 (B/S) | as_of date | PDF + CSV |
| `/admin/accounting/reports/journal?from=&to=&account_code=` | 仕訳帳 | date range + optional account | CSV only |
| `/admin/accounting/reports/ledger?account_code=&from=&to=` | 総勘定元帳 | account + date range | PDF + CSV |

Each report has:
- HTML view (Tailwind, dark mode, sticky header)
- `?format=pdf` link → dompdf-rendered A4 PDF
- `?format=csv` link → UTF-8 BOM-prefixed CSV (Excel-friendly)

Heavy aggregation queries are cached for `accounting.dashboard.cache_ttl_seconds`
(default 3600 = 1 hour). Override per env with `FUJIBA_DASH_CACHE_TTL`.

---

## 2. Dashboard widget (added to existing dashboard)

`/admin/accounting` now shows a "当月サマリー" card alongside the
Sprint 1 placeholder cards:

- 当月売上 (this month's `revenue` credit total)
- 当月仕入 (this month's `cogs` debit total)
- 当月粗利 = 売上 − 仕入
- 前月比 % vs prior month for each
- 未承認タイムカード件数 (Sprint 4 link)

Cache TTL is the same `accounting.dashboard.cache_ttl_seconds` (1 h default).

---

## 3. Period semantics

`ReportPeriodResolver` normalizes the three input shapes into a
`{from, to}` date pair:

| Input | Resolved range |
|---|---|
| `year_month=2026-04` | `2026-04-01 → 2026-04-30` |
| `fiscal_year=2026` | start_date → end_date of `fujiba_fiscal_periods` row |
| `from=… to=…` | as supplied |
| `as_of=2026-09-30` | epoch → 2026-09-30 (B/S only) |

If a period falls across a closed fiscal period boundary, all journal
entries in scope (regardless of fiscal_period_id status) are included —
closed-period filtering is the bookkeeper's job, not the report's.

---

## 4. CSV format

UTF-8 with BOM, comma-separated, double-quoted strings. First column is
always a logical key (account code, entry_no, etc.). Numeric columns are
plain integers (no thousands separators) so Excel/LibreOffice imports
cleanly.

Filenames: `{report}_{period}.csv` e.g. `trial-balance_2026-04.csv`.

---

## 5. PDF format

dompdf (already in vendor — no new composer dep). A4 portrait, 10pt
font, 国税庁-style title block (会社名 / 期間 / 出力日時 / ページ番号).

Templates live under
`packages/Fujiba/Accounting/src/Resources/views/admin/reports/pdf/*.blade.php`.

---

## 6. Verification SQL

After posting some entries:

```sql
-- Trial balance for 2026-04 should reconcile DR == CR across all accounts
SELECT a.code, a.name_ja,
       SUM(jl.debit)  AS dr,
       SUM(jl.credit) AS cr,
       SUM(jl.debit) - SUM(jl.credit) AS balance
FROM   fujiba_journal_lines  jl
JOIN   fujiba_journal_entries je ON je.id = jl.journal_entry_id
JOIN   fujiba_accounts        a  ON a.id  = jl.account_id
WHERE  je.entry_date BETWEEN '2026-04-01' AND '2026-04-30'
  AND  je.status = 'posted'
GROUP  BY a.code, a.name_ja
ORDER  BY a.code;

-- Aggregate DR vs CR (should always be equal across the period)
SELECT SUM(jl.debit)  AS dr_total,
       SUM(jl.credit) AS cr_total
FROM   fujiba_journal_lines  jl
JOIN   fujiba_journal_entries je ON je.id = jl.journal_entry_id
WHERE  je.entry_date BETWEEN '2026-04-01' AND '2026-04-30'
  AND  je.status = 'posted';
```

---

## 7. Known limitations / Sprint 6 candidates

Carried forward from SPRINT4.md, untouched this sprint:
- 賞与 / 年末調整 / 住民税 import / pay-stub PDF / Zengin export / 甲欄 B/C
  brackets / Kiosk 4-digit PIN

New limitations introduced by Sprint 5:
- B/S equity calc uses retained earnings + capital only — no comprehensive
  income / minority interest. Adequate for SME 中小企業 books.
- P/L doesn't yet split 営業外 by recurring vs special items beyond what
  the COA `sub_type` already encodes. 特別損益 group is honored.
- Inventory snapshots (期首/期末 棚卸) come from the `5210/5220` accounts
  if posted. If your shop relies on Bagisto's product_inventories instead,
  Sprint 6 needs a sync job to push period-end snapshots into journal entries.
- Cache invalidation: when a journal is posted (Sprint 2 cron, manual entry,
  receipt confirm, payroll finalize), we DON'T proactively clear report
  caches. They expire naturally within 1 h. For "see numbers immediately"
  workflows the operator can pass `?nocache=1` on any report URL.
