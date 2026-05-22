# Accounting Package — 現状サマリー (2026-05-12)

`packages/Fujiba/Accounting` の到達点を、SPRINT2〜7 ドキュメントから読み取ってまとめた一枚。コード/DB は触っていない、文書のみの読み取り。

---

## 今あるもの (Built)

### Sprint 1 (schema + 基盤)
SPRINT1.md は無く、Sprint 2 以降の references から復元: COA (95 勘定)、税率 seed (9 行)、`fujiba_fiscal_periods`、observers ベースの append-only `fujiba_audit_log`、ダッシュボード placeholder。

### Sprint 2 (自動仕訳)
`JournalPoster` + `accounting:post-orders` / `accounting:post-refunds` cron (毎時 :00 / :15、`withoutOverlapping(10)` + `onOneServer()`)。orders/refunds を automatic に仕訳化、`fujiba_settings` でカーソル管理、`ClosedPeriodException` を soft-skip。

### Sprint 3 (領収書 OCR)
領収書アップロード UI (`fujiba_receipts`、SHA-256、`storage/app/accounting/receipts/`)、Azure Document Intelligence + stub driver、国税庁 適格請求書発行事業者公表サイト API で T-number 検証 (30日キャッシュ)、UI から `uploaded → ready_for_review → confirmed` フロー。

### Sprint 4 (給与基盤)
`fujiba_staff` + QR タブレット kiosk (`/accounting/kiosk`、30秒 idempotency)、`fujiba_timecards` (open/closed/edited/approved)、`PayrollCalculator` + `accounting:run-payroll`、PayrollJournalBuilder (6120/2210/2220/2230/2240/1130 の標準仕訳)。

### Sprint 5 (帳票)
試算表 / P/L / B/S / 仕訳帳 / 総勘定元帳 — HTML + PDF (dompdf) + CSV (UTF-8 BOM)、`ReportPeriodResolver` で year_month/fiscal_year/from-to/as_of を正規化、1時間レポートキャッシュ、ダッシュボード "当月サマリー" カード。

### Sprint 6 (本番ブロッカー解消 + 年次)
甲欄 B/C ブラケット seed 追加 + `PayrollCalculator::withholding(class)`、Kiosk 4桁 PIN (bcrypt)、Zengin format 振込ファイル export (120バイト固定長 / Shift_JIS、staff の銀行コード列追加)、Pay-stub PDF (個別 + zip)、`is_bonus` フラグで賞与計算、`YearEndAdjustmentService` (基本のみ)、`ResidentTaxImporter` (saitama_city/adachi_ku/toshima_ku の CSV mapping 駆動)。

### Sprint 7 (B/C バックフィル)
`accounting:audit-bc-payroll` (Phase A、read-only、CSV 出力) + `accounting:backfill-bc-payroll` (Phase B、`--mode=staff-deduction|company-bears` 必須、`corrected_from_slip_id` 付き correction slip + `(staff_id, year_month, is_bonus, status)` unique 拡張)。Sprint 4 リリース以降に B/C 区分で income_tax=¥0 finalize された slip を訂正する仕組み。Phase A + Phase B 両方コミット済、Phase B 実行はまだ GO 待ち想定。

---

## テストと品質 (Quality)

直近 2026-05-11 の solo 実行で **Fujiba Accounting Feature Test suite 173 件 pass / 504 assertions** (Accounting package 単体)。網羅領域は SPRINT*.md 自体に記載 — 抜粋:

- **テスト済 (主要):** JournalPoster、PayrollCalculator (withholding A/B/C、社保、賞与、年末調整)、ZenginExport (Shift_JIS、4-record-per-recipient、bank code 必須検証)、ReceiptUploadValidation、PayslipPdf、KioskPin (PIN 正/誤/未設定/idempotency)、ConsumptionTaxCalculator、JournalController、StaffController、AuditBcPayrollCommand (8件)、BackfillBcPayroll (12件、carry-forward uplift / dry-run / idempotency / 退職者モード切替)、TimecardKiosk (出勤/退勤/double-scan/inactive/unknown token)、MigrationTest (テーブル存在 + tenancy 登録 + hasTable ガード)。
- **未テスト / 弱い領域:**
  - 完全な月額源泉徴収税額表 (Sprint 6 は簡易版近似、Sprint 7 で精緻化予定だがまだ)
  - Multi-tenant scope — テストは `config('database.default')` 前提、tenant DB ごとの動作は未検証
  - `DashboardTest` は `No admin user seeded` で skipped 状態
  - Bagisto product_inventories ↔ 期首/期末棚卸 (`5210/5220`) の同期パス
  - 電子帳簿保存法 タイムスタンプ認証 (TSA) — SHA-256 までで好意的コンプライアンス

---

## 未完成のもの (Known gaps)

SPRINT*.md 末尾の "Sprint X+ candidates"、"Out of scope"、"documented limitations" から拾った。

### 給与計算
- **乙欄 B / 丙欄 C の簡易税率** — Sprint 6 は国税庁「令和8年版 源泉徴収税額表」の簡易版。完全な月額表は Sprint 7 後段 (未着手) で精緻化予定。
- **賞与社保上限** — 健保年累計 573 万円 / 厚年 1 回 150 万円上限を実装と書かれているが、現実の境界事例 (年中昇給で上限到達) のテストは限定的。
- **月給 → 時給換算** — `monthly_salary / 160` で固定。Sprint 5 メモで `notional_hourly_rate` 列が将来案として残置 (未実装)。
- **顔認証 / NFC kiosk** — PIN で十分の判断、保留。

### Staff データ
- **`staff.postal_code` / 生年月日** — 住民税 import の lookup key としては 郵便番号+生年月日 を想定しているが、Staff スキーマ上にこれらの列が無い (Sprint 6 では `notes` JSON 詰めの妥協)。
- **PIN mandatory mode** — 現状 PIN 未設定 staff は従来通り QR のみで punch (移行期間)。全員必須化のフラグ無し。

### 連携・外部出力
- **弥生 / freee 連携** — Sprint 7 Out of scope に明記、未実装。
- **Excel export** — 同上、未実装。
- **国税庁様式** (年末調整 完全書類: 給与所得者の保険料控除申告書 等) — Sprint 7 へ deferred、未実装。
- **証明書付き TSA タイムスタンプ** (電子帳簿保存法) — SHA-256 までで好意的コンプライアンス、JIPDEC-licensed TSA (SECOM / AmanoTime) との連携未実装。
- **Bulk zip / CSV 領収書 importer** — Sprint 3 で deferred。
- **Email-to-receipt ingestion** — 未実装。
- **モバイル撮影アプリ** — 未実装。
- **領収書の自動勘定推定 ML** — 未実装、operator が手動選択。

### 帳票
- **B/S equity 完全版** — 包括利益 / 少数株主持分なし (SME 中小企業 books としては十分の判断)。
- **P/L 特別損益細分化** — 営業外を recurring / 特別で分けるロジック限定。
- **棚卸期首/期末同期** — Bagisto `product_inventories` を勘定 5210/5220 に push する job 未実装。
- **レポートキャッシュ自動 invalidation** — 仕訳 post 時に proactive clear せず、1時間 TTL 任せ。`?nocache=1` で迂回。

### 訂正・運用
- **Sprint 7 Phase B の実本番投入** — コードは存在、`--mode` 必須・dry-run デフォルト・idempotent だが、本番 run は未実施 (audit 結果待ち + 税理士確認待ちの想定)。

### Multi-tenant
- **Tenant スコープ運用** — SPRINT7.md 明記の通り、Accounting は元来 central DB 動作前提で書かれていた。`tenants:run accounting:audit-bc-payroll` 等のテナント横断は Sprint 8 課題として deferred (※ ただし下記「本番現状」も参照、現状は逆に tenant 側に置かれて central は空)。

---

## 本番現状 (Production reality)

- **2026-05-11 の事故とその修正後の状態:** 全 7 テナントで accounting テーブル群 (13 create + 5 alter = 18 migrations) が `tenants:migrate` 済み。`/admin/accounting` の 500 は解消、302 (login redirect) を返す。
- **データ投入状況:** どのテナントでも `fujiba_staff` / `fujiba_payroll_slips` / `fujiba_journal_entries` 全て **0 行**。
- **Chart of accounts (95 行) seed 未投入** — テナント側へ流していない。Sprint 1 で central に投入されていた seed は事故時に消失、復旧は別タスク。
- **税率 seed (9 行) 未投入** — 同上。
- **`fujiba_fiscal_periods` 2 行 (令和7年度・令和8年度)** — 同様にテナント側へ未投入、要 seeding。
- **`fujiba_settings` の cron カーソル** (`last_order_post_run_at`, `last_refund_post_run_at`) — 失われた状態、scheduler が次回実行時に作り直す想定。
- **実用状況:** 未使用。Excel で給与計算を継続中。Accounting パッケージは "engineering complete, business-data-empty" の段階。
- **Sprint 7 Phase A audit:** 走らせていない (走らせても staff/slip が 0 なので Pending スリップ 0 件出力になる)。

---

## Go-live に足りていないもの (Not-engineering gaps)

エンジニアリングは概ね揃っている。実運用に移すために**人間側で**消化が必要なもの:

1. **Staff データ入力** — `/admin/accounting/staff` から実在の staff を投入 (氏名・雇用区分・時給/月給・社保加入フラグ・銀行口座・郵便番号・甲乙丙区分)。Excel に既存のデータがある前提なので、形式合わせて移行。
2. **甲欄 / 乙欄 / 丙欄区分の判定** — 各 staff が「主たる給与の支払者」なのか「副業」なのか「日雇い」なのか。源泉徴収簿か扶養控除申告書の有無で確定。**税理士に確認を取って staff ごと記録**しないと income_tax が誤計算。
3. **税理士レビュー** — chart of accounts (95 勘定) が会社の実態と合っているか、税率 seed (9 行) が最新の国税庁版と一致しているか、賞与計算の社保上限ロジックを承認、Sprint 7 backfill モード選定 (staff-deduction か company-bears か)。
4. **銀行口座情報収集** — Zengin export を使うなら、staff 全員の銀行コード (4桁) / 支店コード (3桁) / 口座種別 / 口座番号 / 半角カナ名義 を入手。未入力 staff は export から除外され `errors` に上がる。
5. **PIN 配布 + 物理 QR カード作成** — `/admin/accounting/staff` で QR 印刷 → ラミネート → 配布。PIN を全員に決めてもらう (4桁、bcrypt)。
6. **トレーニング** — 店長 + 経理担当に、Kiosk 操作、timecard 承認、月次給与確定、領収書 OCR、月末 trial balance チェックを教える。
7. **Excel との並走期間** — 1〜3か月 Excel と Accounting パッケージで二重記帳して数値が一致することを確認してから Excel を退役。
8. **電子帳簿保存法 対応の合意** — SHA-256 までで止めるか、JIPDEC-licensed TSA を契約するか、税務署に届出している保存方式と整合させる。
9. **OCR 経費判断** — Azure Document Intelligence (¥1.5/枚) を使うか、stub driver で operator 手入力に倒すか。月 500 枚なら ¥750/月、判断は経営側。
10. **住民税 通知 CSV の所轄自治体確認** — 現状サポートは saitama_city / adachi_ku / toshima_ku の 3つのみ。実 staff の自治体がこれ以外なら、`config/accounting.php` の mapping を追加する作業 (エンジニアリング小、自治体情報入手は人間)。

---

## 推奨 (Recommendation)

Accounting パッケージは「ソフトウェアとしては概ね完成、業務データが空のまま」という段階。投資すべき次のステップは Phase B-1 の UI polish よりも **税理士レビュー + 実 staff データ投入 + 1〜3か月 Excel 並走** に思えます。chart-of-accounts seed と税率 seed をテナント側へ流す小タスクは別 sprint で安全に処理可能。事故再発防止として「本番ホストで `php artisan test` 禁止」を memory feedback に固定するのが先。
