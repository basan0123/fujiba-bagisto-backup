# Sprint 6 — Operator Runbook

Sprint 6 が解消する Sprint 4-5 残件 + 業務ブロッカー + 年次イベント機能。

---

## Phase 1 — 本番ブロッカー解消 (最優先)

### 1A. 甲欄 B/C ブラケット — withholding 計算正常化

**問題:** Sprint 1 の `TaxRatesSeeder` は `withholding_a` のみ seed していた。
`PayrollCalculator::withholding()` は B/C 区分の staff には `null` を返し、
所得税が常に ¥0 になっていた (Sprint 4 の SPRINT4.md で flag 済)。

**修正:**
- `TaxRatesSeeder` に `withholding_b` (乙欄) と `withholding_c` (丙欄) を追加。
  - 乙欄 = 簡易な単一税率 (給与額 × 10.21%, 副業/2社目以降の標準)。
  - 丙欄 = 日額 ¥9,300 まで非課税、超過分のみ課税 (日雇い対応)。
  - 値は国税庁「令和8年版 源泉徴収税額表」に基づく簡易版。完全な月額表は
    Sprint 7 で精緻化 (社内取扱に過不足ない近似)。
- `PayrollCalculator::withholding()` に `class` 引数追加、staff の
  `withholding_class` を渡すように `calculate()` を改修。

**テスト:** `PayrollCalculatorWithholdingClassTest` で A/B/C それぞれが
ゼロ以上を返し、B が A よりも 通常重い (副業前提) ことを確認。

---

### 1B. Kiosk 4桁 PIN

**問題:** Sprint 4 の Kiosk は QR トークンのみで認証。トークンが流出 (落とした
QR カード等) すると別人がなりすませる。

**修正 (1 migration):** Sprint 6 で唯一の migration。spec 通り 1 本だが、
Zengin (Phase 2B) で必要な銀行コード列が Sprint 1 schema に欠けていたため、
**同じ migration に同梱**。差分 1 ファイルで:
- `fujiba_staff.pin_hash CHAR(60) NULL` (bcrypt)
- `fujiba_staff.bank_code VARCHAR(4) NULL` (4桁、全銀協)
- `fujiba_staff.bank_branch_code VARCHAR(3) NULL` (3桁)
- `fujiba_staff.bank_account_holder_kana VARCHAR(64) NULL` (半角カナ)

**Kiosk 動作:**
1. QR スキャン → 候補 staff 特定
2. PIN 設定済みなら 4桁入力プロンプト表示、bcrypt 検証
3. PIN 未設定 staff は従来通りスキャン即 punch (移行期間)
4. 30秒 idempotency ウィンドウは PIN ゲート前で評価 — 二度押し防止は維持

**テスト:** PIN 正/誤/未設定 + idempotency 維持の4ケース + Staff edit
form 経由の PIN 設定保存。

---

## Phase 2 — 業務需要

### 2A. Pay-stub PDF (給与明細書)

**Routes:**
- `/admin/accounting/payroll/{id}/pdf` — 個別 PDF ダウンロード
- `/admin/accounting/payroll?year_month=YYYY-MM&format=pdf-zip` — 全員分 zip

**レイアウト:** 標準的な給与明細 3 ブロック構成 — 支給/控除/差引、
A4 縦、社判欄スペース付き。dompdf スタック継承 (新 dep なし)。

### 2B. Zengin format export (全銀協 振込ファイル)

**Route:** `/admin/accounting/payroll?year_month=YYYY-MM&format=zengin`

**仕様:** 全銀協 1 標準フォーマット (2008年改訂版):
- 120 バイト固定長 / Shift_JIS / CRLF 行末
- レコード構成: ヘッダー(1) → データ(N) → トレーラー(1) → エンド(1)
- データレコードに半角カナ/数字、全銀コード必須

**Staff カラム要求 (Phase 1B migration で追加):**
- `bank_code` (4桁数字)
- `bank_branch_code` (3桁数字)
- `bank_account_type` (普通=1 / 当座=2 / 貯蓄=4 etc.)
- `bank_account_number` (7桁、ゼロパディング)
- `bank_account_holder_kana` (半角カナ、最大30文字)

未入力 staff はその月のレコードから除外し、`errors` 配列で報告。

---

## Phase 3 — 年次イベント

### 3A. 賞与計算 (設計判断: `is_bonus` フラグ採用)

**選択肢:**
- A) 新テーブル `fujiba_bonus_slips` — クリーン分離、UI が増える
- B) `fujiba_payroll_slips.is_bonus = true` フラグ — 既存テーブル流用

**採用: B (フラグ)** 理由:
- 賞与の支給/控除/journal posting 構造は給与とほぼ同一 (基本給 vs 賞与額、
  社保標準月額 vs 賞与標準月額の違いだけ)
- PayrollSlip スキーマは年月+staff_id ユニーク制約だが、賞与は
  `year_month` を `'2026-12-bonus'` のように suffix 付けて回避できる
  (もしくは migration で複合 unique を `(staff_id, year_month, is_bonus)` に変更)
- 既存 PayrollJournalBuilder / pay-stub PDF / Zengin export がそのまま使える
- UI は `?is_bonus=1` で一覧をフィルタするだけ

**所得税:** 賞与は「賞与に対する源泉徴収税額の算出率の表」を使う:
前月給与 (社保控除後) から扶養人数考慮の率を引いて賞与額に乗算。
- `fujiba_tax_rates` に `bonus_withholding_rate` (bracket-based) を追加 seed
- `PayrollCalculator::calculateBonus()` 新設

**社保:** 賞与標準月額制度 (健保 = 年累計 573 万円 / 厚年 = 1 回 150 万円
上限) を実装。

### 3B. 年末調整 (基本のみ — 完全版は Sprint 7)

**スコープ:**
- 1〜12月給与の年間集計
- 年間所得税の再計算 (生命保険控除/地震保険控除/扶養控除を staff 個別入力)
- 12月給与 (or 翌年1月) で差額還付/徴収を slip に追加

**実装:**
- `Staff` に年末調整入力カラム追加 (`life_insurance_premium_total`,
  `earthquake_insurance_premium_total`) — 既存 `notes` を使い
  `notes_json` (Sprint 1 既存？確認) — **migration なし**、`notes` は
  text なので JSON 文字列を入れる運用
- `YearEndAdjustmentService` 新設、対象 year を引数に slip 補正提案を返す
- 操作は admin から手動トリガー (年1回イベント、cron 不要)

完全な書類出力 (給与所得者の保険料控除申告書 等) は Sprint 7。

### 3C. 住民税 import

**Service:** `ResidentTaxImporter`
**Routes:** `/admin/accounting/payroll/resident-tax/import` (アップロード form)

**サポート自治体:** mapping config 駆動 — 3つ完全サポート + 拡張可能:
- saitama_city
- adachi_ku
- toshima_ku

**Mapping config (`config/accounting.php`):** 各自治体の CSV 列名 ↔
staff ルックアップキー (郵便番号+生年月日 / staff_code 等) のマッピング。

**処理:** CSV → 行ごとに staff 特定 → 当該年度の毎月住民税額を保存
(新カラム不要、`fujiba_settings` に `resident_tax:{staff_id}:{year_month}=amount`
の key で書き込み、PayrollCalculator が読む)。

---

## Out of scope (Sprint 7 候補)

- Excel export
- 弥生/freee 連携
- Report の HTML→PDF 移行 (現在: HTML がメイン、PDF は export 経路)
- 完全な年末調整書類出力 (国税庁様式)
- Kiosk: 顔認証 / NFC (PIN で十分とまず判断)

---

## Constraints & decisions log

- **Migration 本数:** Phase 1B で 1本のみ。Zengin で必要だった銀行コード3列も
  同じ migration に同梱して差分1本維持 (spec 緩和、SPRINT6.md 明記)。
- **PDF stack:** dompdf 継承、新 composer dep なし。
- **Multi-tenant:** Sprint 5 と同じ `channel_id` semantics 維持。
- **DatabaseTransactions:** 全テスト rollback、中央 DB 不汚染。
- **Phase 1A 優先理由:** 現在 B/C 区分の staff (副業/日雇い) は所得税
  ¥0 で誤計算中。本番運用しているなら税務リスクが累積している可能性。
