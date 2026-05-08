# Sprint 4 — Operator Notes

This document covers Sprint 4: staff management, the tablet timecard kiosk,
the payroll calculator, and the payroll → journal pipeline. Sprints 1–3 are
unchanged.

---

## 1. Setting up a new staff member

`/admin/accounting/staff` → **+ スタッフを追加**

1. Fill in 氏名 (required). Everything else is optional but for full payroll
   accuracy you want at minimum: 雇用区分, 時給 OR 月給, 社保加入フラグ.
2. Submit. `staff_code` and `qr_token` are auto-generated if blank.
3. Click **QR印刷** (列右端 or detail page) → opens a printable card with the
   staff name and their QR code. Print, laminate, hand to staff.

The 4 special bits to get right for payroll:
- **時給 / 月給** — the calculator uses 月給 if set, otherwise 時給×regular_minutes/60.
- **扶養人数** — shifts withholding brackets ¥31,667 per dependent.
- **社保加入** — three independent checkboxes. Only enrolled deductions are taken.
- **源泉徴収区分** (甲/乙/丙) — drives which `withholding_*` rate applies. The
  calculator currently uses `withholding_a` brackets for class A; B/C will be
  added in Sprint 5 as the brackets stabilize.

---

## 2. Tablet kiosk

Mount a tablet near the staff entrance. Open `/accounting/kiosk` in fullscreen.

- The screen shows a giant clock and an autofocused input.
- Staff scan their QR card with a USB-HID barcode scanner (or type the token);
  Enter is sent automatically by the scanner.
- First scan of the day → 出勤 (clock_in)
- Second scan → 退勤 (clock_out, work_minutes computed)
- Re-scan within 30 s of an existing punch → idempotent ack (so a stuck
  trigger or a double-pull doesn't double-clock)

The kiosk uses the `web` middleware group only — no admin auth. The qr_token
is the secret. For deployments outside a controlled-staff-only area, add a
4-digit PIN field (Sprint 5).

### curl test

```bash
curl -c c -X POST -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"token":"<qr-token>"}' \
  https://shop.example.com/accounting/kiosk/punch
# → {"ok":true,"idempotent":false,"message":"出勤を記録しました","staff_name":"...","time":"09:32"}
```

(Note: the kiosk page must be visited first to mint a CSRF cookie; the
production deploy will whitelist `/accounting/kiosk/punch` from CSRF if
needed.)

---

## 3. Editing & approving timecards

`/admin/accounting/timecards`

- Filter by date range, staff, status.
- Click into any row to fix typos in clock_in/clock_out, override
  work_minutes, or mark overtime/night/holiday minutes.
- "承認" promotes the card to `status=approved`. Only approved cards feed
  the payroll calculator.

Status flow:
```
open  ─ kiosk: clock-in only        (no clock_out yet)
closed ─ kiosk: clocked out
edited ─ admin saved changes        (recompute applied)
approved ─ admin approved           (locked from further edits unless reopened)
```

The card is uniqued on `(staff_id, work_date)`. There is intentionally no
"split shift" support; if a staff member works multiple shifts in one day,
edit `work_minutes` directly to reflect the total.

---

## 4. Running payroll

`/admin/accounting/payroll?year_month=YYYY-MM`

- Top form: pick year_month + pay_date → "集計実行" calls
  `PayrollCalculator` for every active staff member. Slips are created as
  `status=draft`.
- Each row: green "確定" button posts the journal entry and flips status
  to `finalized` (equivalent of `accounting:run-payroll --finalize`).
- Click any name to see the per-staff slip with full earnings + deductions.

### Console alternative

```bash
# Calculate (or recompute) drafts for last month, every active staff:
php artisan accounting:run-payroll

# Specific month + finalize in one shot:
php artisan accounting:run-payroll --year-month=2026-04 --finalize

# Just one person, dry-run first:
php artisan accounting:run-payroll --year-month=2026-04 --staff=42 --dry-run
```

### Posted journal shape

```
DR 6120 給料手当            ¥gross
   CR 2210 預り金(社保)     ¥(HI + pension + EI)
   CR 2220 預り金(源泉)     ¥income_tax
   CR 2230 預り金(住民税)   ¥resident_tax        (omitted if 0)
   CR 2240 預り金(その他)   ¥other_deduction      (omitted if 0)
   CR 1130 普通預金 振込    ¥net_pay
```

Idempotent on `payroll-{slip_id}`. Re-running finalize on a slip that's
already `finalized` is rejected at controller level; the JournalPoster
itself would just return the existing entry.

---

## 5. Common verification SQL

```sql
-- Slip & journal sanity for a month:
SELECT s.staff_code, st.name_ja, s.gross_pay, s.total_deduction, s.net_pay,
       s.status, s.journal_entry_id, je.entry_no
FROM   fujiba_payroll_slips s
JOIN   fujiba_staff st       ON st.id = s.staff_id
LEFT JOIN fujiba_journal_entries je ON je.id = s.journal_entry_id
WHERE  s.year_month = '2026-04'
ORDER BY st.staff_code;

-- Approved-vs-counted minutes (cross-check the calculator):
SELECT staff_id, SUM(work_minutes), SUM(overtime_minutes)
FROM   fujiba_timecards
WHERE  work_date BETWEEN '2026-04-01' AND '2026-04-30'
   AND status = 'approved'
GROUP  BY staff_id;

-- Stuck "open" timecards (never clocked out — chase them up):
SELECT t.id, st.name_ja, t.work_date, t.clock_in
FROM   fujiba_timecards t
JOIN   fujiba_staff     st ON st.id = t.staff_id
WHERE  t.status = 'open'
   AND t.work_date < CURDATE();
```

---

## 6. What's intentionally out of scope

- **賞与** (bonus calculations) — separate from monthly slips; coming in
  Sprint 5 alongside 年末調整.
- **年末調整** (year-end tax adjustment) — Sprint 5.
- **住民税** auto-import from city special-collection notices — Sprint 5.
  For now, operator types `resident_tax` per slip.
- **Pay-stub PDF** — Sprint 5.
- **Zengin format** bank-transfer file export — later.
- **Holiday auto-detection** — operator manually marks holiday minutes per
  card. Adding 公休/祝日 dictionary is a small Sprint 5 addition.

---

## 7. Decisions baked in (so Sprint 5 doesn't undo them)

- 月給 staff: notional hourly = `monthly_salary / 160`. This is the
  industry-default base for overtime premium calc; can be overridden in
  Sprint 5 via per-staff `notional_hourly_rate` field if needed.
- All amounts are integer JPY; `floor()` everywhere. Any ¥1 drift is
  captured by the bookkeeper at month-end via 雑損益 — this never causes
  unbalanced entries because `total_deduction` and `net_pay` are computed
  consistently.
- Withholding A class only for now. B/C in Sprint 5.
- Audit log captures every PayrollSlip create/update through the
  AuditObserver registered in Sprint 1.
