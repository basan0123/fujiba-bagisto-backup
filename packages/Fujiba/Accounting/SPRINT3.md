# Sprint 3 — Operator Notes

This document covers Sprint 3: receipt upload, OCR, T-number registry lookup,
and the receipt → journal posting pipeline. Sprints 1 and 2 are unchanged.

---

## 1. Uploading via the UI

Sign in to `/admin`, then navigate to **経理 → 領収書 → 領収書をアップロード**
(or open `/admin/accounting/receipts/create` directly).

1. Drop or pick a `.jpg` / `.jpeg` / `.png` / `.pdf` file (≤ 10 MB).
2. Optionally pre-fill vendor/date/amount/payment method/channel — OCR will
   only fill blanks, not overwrite operator-entered values.
3. Submit. The browser is redirected to the receipt's detail page.

Behind the scenes:

- File is hashed (SHA-256) and stored under
  `storage/app/accounting/receipts/{YYYY}/{MM}/{uuid}.{ext}`.
- A row is inserted into `fujiba_receipts` with `status=uploaded`,
  `file_sha256`, `mime_type`, `file_size_bytes`, and `uploaded_by`.

The detail screen gives you three actions, gated by status:

- **OCR実行** (only when `status=uploaded`) → calls the configured OCR driver
  synchronously. On success the receipt moves to `ready_for_review` and any
  blank fields are filled from the OCR result.
- **確認して仕訳** → builds and posts the journal entry via `JournalPoster`.
  Requires `account_id` and `total_amount`. Status flips to `confirmed`,
  the new entry is linked, and `confirmed_by` / `confirmed_at` are stamped.
- **却下** → marks the receipt `rejected`. No journal entry is written.

The 仕訳 link on a confirmed receipt jumps straight to the entry detail
page added in Sprint 2 (`/admin/accounting/journal/{id}`).

### Curl walkthrough (no browser)

```bash
# 1) Upload
curl -X POST -b session.cookie -c session.cookie \
     -F file=@receipt.jpg \
     -F vendor_name='ABC Mart' \
     -F payment_method=cash \
     https://shop.example.com/admin/accounting/receipts

# 2) Run OCR (id from previous response's redirect URL)
curl -X POST -b session.cookie \
     https://shop.example.com/admin/accounting/receipts/42/ocr

# 3) Update fields (operator picks expense account)
curl -X PUT -b session.cookie -d _method=PUT \
     -d account_id=43 -d total_amount=1100 -d tax_amount=100 \
     -d tax_category=standard \
     https://shop.example.com/admin/accounting/receipts/42

# 4) Confirm — posts the journal entry
curl -X POST -b session.cookie \
     https://shop.example.com/admin/accounting/receipts/42/confirm
```

CSRF token is required for state-changing routes — easiest to script via the
admin Playwright/Cypress tests instead of raw curl.

---

## 2. Azure Document Intelligence setup

Default driver is `stub` — the system works without external services; the
operator types fields manually. To enable Azure:

```ini
# .env
FUJIBA_OCR_DRIVER=azure
FUJIBA_OCR_AZURE_ENDPOINT=https://<resource>.cognitiveservices.azure.com
FUJIBA_OCR_AZURE_KEY=<your-key>
FUJIBA_OCR_AZURE_MODEL=prebuilt-receipt          # default
FUJIBA_OCR_AZURE_API_VERSION=2024-11-30          # default
```

After changing the env, run `php artisan config:clear`.

**Cost (2026-05, S0 tier)**: ~¥1.5 per receipt analyzed (≈ $0.01 USD per
page). 500 receipts/month × ¥1.5 ≈ **¥750/month**, which is the typical
small-shop bookkeeping budget.

The driver:
- POSTs the file binary to `documentModels/prebuilt-receipt:analyze`
- Polls `Operation-Location` every 1 s for up to 60 s
- Maps `MerchantName` / `TransactionDate` / `Total` / `TotalTax` /
  `PaymentMethod` to our normalized shape
- Scans `analyzeResult.content` for `T\d{13}` to extract the 適格請求書 number

A request_id is logged with each call for Azure-side debugging.

---

## 3. 国税庁 適格請求書発行事業者公表サイト API setup

The system captures T-numbers regardless — the API just **validates** them
against the public registry. To enable verification:

1. Apply at <https://www.invoice-kohyo.nta.go.jp/web-api/>. Free, ~1 day approval.
2. Save the `app_id` returned to you.
3. Set in `.env`:

```ini
FUJIBA_NTA_INVOICE_API_ID=<your-app-id>
# FUJIBA_NTA_INVOICE_API_URL is preset to the production endpoint.
```

Lookups cache for 30 days in `fujiba_invoice_registrants`. Within that
window, repeated lookups don't hit the API. Override with
`config('accounting.nta_invoice_api.cache_days')` if you need fresher data.

The verify button on the receipt detail page calls
`GET /admin/accounting/api/invoice-registrants/{T...}` and shows ✓ name on
match, ⚠ on no match, ✕ on bad format.

---

## 4. Backfilling existing paper receipts

Operator workflow for digitizing a stack of historical receipts:

1. Photograph each receipt (one file per page).
2. Upload through the UI in batch (multi-tab works fine).
3. Run OCR on each (the cost is ¥1.5/each on Azure; or skip and type by hand
   on the stub driver).
4. Audit each on the detail screen, pick the expense account, confirm.
5. Confirmed entries appear in `/admin/accounting/journal` filtered by
   `source_type=receipt`.

A future sprint will add a bulk-zip importer; for now per-receipt clicks are
the safer ergonomics for evidence integrity.

---

## 5. Storage growth

Rule-of-thumb planning numbers:

- Average JPG receipt photo ≈ 500 KB (mobile camera, normal compression).
- 500 receipts/month → ~250 MB/month → ~3 GB/year.
- PDF invoices average ~250 KB each.

These files are immutable — never deleted unless the receipt was rejected
or removed before confirm. The SHA-256 hash is preserved on the row, so
moving them to cold storage later (S3 Glacier, etc.) is safe — just keep
the `storage_path` and disk config in sync.

---

## 6. 電子帳簿保存法 evidence trail

Sprint 3 records:

- SHA-256 of each uploaded file (`fujiba_receipts.file_sha256`).
- Append-only `fujiba_audit_log` rows for create / update / confirm /
  reject / delete.
- The original file at the original storage path (filename is a UUID, so
  no collisions / overwrites).

This is good-faith compliance — not a certified Time-Stamp Authority
timestamp. For 電子帳簿保存法 タイムスタンプ認証 a future sprint can wire
in a JIPDEC-licensed TSA (e.g. SECOM, AmanoTime); the SHA-256 already
recorded is exactly the input that a TSA would sign.

---

## 7. Verifying / debugging in SQL

```sql
-- State of the world
SELECT status, COUNT(*) FROM fujiba_receipts GROUP BY status;

-- Receipts that have been confirmed but not yet linked (should be empty)
SELECT id, vendor_name, status, journal_entry_id
FROM   fujiba_receipts
WHERE  status = 'confirmed' AND journal_entry_id IS NULL;

-- Find the journal entry posted from a receipt
SELECT je.entry_no, je.entry_date, SUM(jl.debit) AS amount
FROM   fujiba_journal_entries je
JOIN   fujiba_journal_lines  jl ON jl.journal_entry_id = je.id
WHERE  je.source_type = 'receipt'
  AND  je.source_id   = ?
GROUP  BY je.id;

-- Find duplicate uploads (same SHA-256 → same file content)
SELECT file_sha256, COUNT(*) AS c, GROUP_CONCAT(id)
FROM   fujiba_receipts
WHERE  file_sha256 IS NOT NULL
GROUP  BY file_sha256
HAVING c > 1;
```

---

## 8. Out of scope (deliberately)

- Bulk zip / CSV import — coming later.
- Email-to-receipt ingestion (forward photos to a mailbox) — later.
- Mobile capture app — later.
- Auto-categorization ML (suggest expense account) — later. Operator picks
  manually for now.
- Certified TSA timestamps — out of scope (good-faith compliance only).
