# Sprint 7 — 甲欄 B/C payroll バックフィル

**現状: Phase A (read-only audit) のみ実装、commit 済。Phase B は人間の GO/NO-GO 待ち。**

---

## 背景

Sprint 4 (2026-04 リリース) 〜 Sprint 6 Phase 1A の間、`PayrollCalculator::withholding()`
は `withholding_class IN ('B','C')` の staff に対して `null` を返し、
`income_tax = ¥0` で finalized されていた。

Sprint 6 Phase 1A で fix 済 — でも **既に finalized された buggy slip の
所得税は ¥0 のまま**。仕訳も既に記帳済み (immutable)。

人事/税務上、不足源泉は二択:
- **--mode=staff-deduction** — 翌月給与で staff から控除する (本来の正しい
  処理。staff に通知が必要)。
- **--mode=company-bears** — 会社負担とし `7230 雑損失` で吸収する (年内に
  staff へ説明できないとき。経営判断)。

Phase B はどちらかのモードで動く前提。Phase A の audit を見て決めてもらう。

---

## Phase A — Read-only audit (この commit に含まれる)

### コマンド

```
php artisan accounting:audit-bc-payroll
  [--from=YYYY-MM]    # default: 2026-04 (Sprint 4 リリース月)
  [--to=YYYY-MM]      # default: 当月
  [--include-inactive] # 退職者の slip も含める
  [--out=/path/to/file.csv]  # default: /tmp/bc-audit-{timestamp}.csv
```

**読み取り専用。何も書き換えない。** 確認スクリプトとして勘定/finalize にも触らない。

### 出力

#### Console summary

```
================================================================
甲欄 B/C 源泉徴収バックフィル 影響調査
================================================================
 期間 : 2026-04 → 2026-10
 active B 区分 staff       :   3
 active C 区分 staff       :   2
 inactive B/C staff        :   1
 buggy 期間中 finalized slips :  18    (income_tax=0 で記帳済み)
 buggy 期間中の class 変更ログ :   0
================================================================
 推定不足源泉総額            : ¥412,580
 推定不足預り金 (2210 社保)   :     0   ← 社保は別計算なので影響なし
 仕訳訂正対象 journal entries :  18
================================================================
 詳細CSV : /tmp/bc-audit-2026-10-15-103045.csv
```

####  CSV 列 (1 staff × 1 month = 1 行)

| 列 | 意味 |
|---|---|
| `staff_id` | |
| `staff_code` | |
| `staff_name` | |
| `withholding_class` | B / C |
| `is_active` | 0/1 |
| `year_month` | |
| `slip_id` | finalized 済 slip の id |
| `slip_status` | finalized / paid |
| `journal_entry_id` | 紐づき仕訳 (改竄不可) |
| `gross_pay` | 元 slip の支給額 |
| `prev_taxable` | 前月課税給与 (Sprint 6 calculator が要求) |
| `recorded_income_tax` | DB 上 (¥0 のはず) |
| `correct_income_tax` | Sprint 6 calculator で再計算した正しい税額 |
| `shortfall` | correct - recorded (正の値が不足分) |
| `class_change_after_slip` | この slip の後で class が変更されたか (audit_log 参照) |

### GO/NO-GO に必要な情報

人間が判断するために以下を確認:

1. **不足源泉総額** — このまま放置できるか (¥10,000 未満なら税務的に微少)
2. **buggy 期間長** — 短ければ staff に説明し控除回収が現実的、長期なら会社負担検討
3. **退職者がいるか** — 退職者からの追加徴収は実務上不可能 → 会社負担固定
4. **class 変更ログ** — 期中に B → A 切替された staff は処理が複雑化 (該当期だけ訂正)
5. **税理士の判断** — 顧問税理士に CSV を見せてから決定

GO の場合: モード (`staff-deduction` / `company-bears`) と、退職者を別モード扱いにするかを指示。

### 実行例

```bash
# 全期間 + active のみ
php artisan accounting:audit-bc-payroll

# 退職者含めて 2026-04〜09 だけ
php artisan accounting:audit-bc-payroll --from=2026-04 --to=2026-09 --include-inactive

# 出力先指定
php artisan accounting:audit-bc-payroll --out=/var/audit/bc-payroll-2026-q3.csv
```

### Multi-tenant 注意

Sprint 1-6 で fujiba_accounting tables は **central DB のみ** に置かれた。
`tenants:run` 経由で各 tenant DB 上の accounting を audit する設計には
**なっていない**。本実装は `config('database.default')` で動く前提。
将来 tenant ごとに accounting を分けた場合は `tenants:run accounting:audit-bc-payroll`
で全 tenant にまわす方針 (Sprint 8 候補)。

---

## Phase B — Backfill (実装前、本書では設計だけ提示)

### 設計選択 (SPRINT7.md で記述、コードは未実装)

#### B-1. correction slip の置き場所

**採用: 既存 fujiba_payroll_slips テーブル + status='correction' の追加**

理由:
- 別テーブル `fujiba_payroll_corrections` 案も検討したが、payslip PDF
  生成・Zengin export・dashboard 集計が二重になる
- (staff_id, year_month, is_bonus) unique は **削除 or 緩和**:
  Phase 3A で導入した `(staff_id, year_month, is_bonus)` を
  `(staff_id, year_month, is_bonus, status)` に変更。
  status='correction' なら同月複数許容 (再訂正もありえる)
- correction slip は `corrected_from_slip_id` で元 slip に紐づく
- correction の `gross_pay` は ¥0 (差分のみ収載)、`income_tax` は不足分、
  `total_deduction` と `net_pay` は意味が変わるが、用途別に整合性を取る

migration が 1 本必要 (status enum 拡張 + corrected_from_slip_id 列追加 +
unique 緩和)。

#### B-2. 仕訳パターン

**staff-deduction モード:**
```
DR 1130 普通預金     ¥shortfall      (来月給与で控除する権利)
   CR 2220 預り金(源泉)               ¥shortfall
```
ではなく実際は「**来月以降の給与 slip で income_tax を上乗せして控除**」。
correction slip 自体は仕訳を起票せず、来月通常 slip の calc_meta に
"prior_period_correction: ¥N" を載せて controller で income_tax 加算。

**company-bears モード:**
```
DR 7230 雑損失       ¥shortfall
   CR 2220 預り金(源泉)               ¥shortfall
```
correction slip は仕訳を持つ (新規 J-XXXXXX-XXXXX)、`source_type='payroll_correction'`。

#### B-3. UI (詳細ページ)

`/admin/accounting/payroll/{slip}` 詳細で、その slip に対する correction が
あれば 「⚠️ 訂正 J-XXXXXX より +¥N 不足源泉を計上」 バナー表示。
クリックで correction slip 詳細へ。

#### B-4. PDF 通知

staff-deduction モードでは staff へ「次月控除のお知らせ」PDF を生成
(Phase 6 の slip PDF テンプレートに correction notice ブロックを追加)。

#### B-5. コマンド分離

audit と backfill は **別ファイル**:
- `Console/Commands/AuditBcPayroll.php` (Phase A、本 commit に含まれる)
- `Console/Commands/BackfillBcPayroll.php` (Phase B、未実装)

backfill コマンドは `--mode=staff-deduction|company-bears`、
`--dry-run` (default), `--confirm` (実行)、`--from`、`--to`、
`--staff=ID,ID,...`、`--inactive-mode=company-bears` (退職者は強制会社負担)
を受ける。

#### B-6. Tests (Phase B 実装時)

- audit_bc_payroll_test.php (Phase A、本 commit) ✓
- backfill_bc_payroll_dry_run.php
- backfill_bc_payroll_confirm_staff_deduction.php
- backfill_bc_payroll_confirm_company_bears.php
- correction_slip_journal_balanced.php
- correction_slip_idempotent.php (再実行で重複生成しない)
- ui_displays_correction_banner.php
- staff_deduction_carries_to_next_slip.php (next month slip income_tax += shortfall)

---

## Constraints (Sprint 7 全体)

- finalized payroll_slips は immutable (Phase A も Phase B も上書きしない)
- 既存 J-... 仕訳もそのまま、訂正は新規仕訳起票
- audit と backfill は別コマンドで誤実行防止
- Phase A → Phase B は **必ず人間が GO を出してから**
- multi-tenant: central DB のみ動作、tenant 分離は Sprint 8 課題
