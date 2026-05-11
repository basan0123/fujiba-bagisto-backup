# UI Polish Sprint — Phase A Audit

Read-only audit. No code changed. Captured at HEAD `5a93a9c`
(`feat(pos): customerCreditAdjust mutation + role gate (Ask #2)`).

Scope: storefront (Blade shop) + management screens. PWA SPA noted where
relevant since the two storefronts share the URL space.

Legend for "現状" column:
- **DONE** — implemented, working as intended
- **PARTIAL** — implemented but has a defect or incomplete behaviour
- **NONE** — not implemented at all
- **AMBIG** — implementation exists but the user's question is ambiguous; pin scope before deciding

Legend for "core?" column:
- **N** — only Fujiba/* or `resources/themes/*` files needed
- **Y** — change requires editing `packages/Webkul/*` (upgrade-path risk)

---

## 8 listed items

| # | 項目 | 現状 | 証拠 | サイズ | core? | 依存 |
|---|---|---|---|---|---|---|
| 1 | スティッキーカテゴリー行 | **PARTIAL** | `resources/themes/mmfood-default/views/components/mmfood/sticky-categories.blade.php:4` `class="sticky top-0 z-40"`. 親ヘッダーは `packages/Webkul/Shop/src/Resources/views/components/layouts/index.blade.php:203` `.lc-scroll-header { position:fixed; top:0; z-index:50 }` → カテゴリー行が **z-40 < z-50 でヘッダーの下に隠れる**。Mobile home (`body.lc-home-page`, `<1024px`) ではヘッダーが `position:static` になるのでカテゴリーがちゃんと貼り付く。Desktop と非ホームでは見た目壊れている。コンポーネントは home page の `CATEGORY_CAROUSEL` カスタマイズスロットでしか呼ばれていない (`resources/themes/mmfood-default/views/home/index.blade.php:30-34`)。 | small | N | header CSS と協調必要 (item 4 / 8 と同じファイル群) |
| 2 | あいまい検索 (fuzzy search) | **PARTIAL — 二重実装** | PWA SPA: `packages/Fujiba/PwaFrontend/src/Http/Controllers/Api/SearchController.php:170-220` で Levenshtein ベース、句読点剥がし、SKU マッチ、距離許容度=クエリ長依存。**しかし全角/半角・カナ/ひらがな正規化なし** (`mb_convert_kana` 未使用)。Blade 蜂店フロント: `packages/Webkul/Product/src/Repositories/ProductRepository.php:329-336` で plain `LIKE %query%` + 手動 synonym 表のみ。fuzzy なし。PWA 側 `/api/pwa-frontend/search`、Blade 側 `/search` で**完全に別経路**。 | medium (PWA) / large (Blade に持ち込む場合) | Blade に持ち込むと **Y** (ProductRepository::searchFromDatabase 改造) | item 7 と関連 (kana 入力ゆらぎ対応) |
| 3 | お知らせバー | **NONE (storefront)** / **DONE (admin)** | 管理画面: `app/Http/Middleware/PlatformAnnouncementBanner.php` で `/admin/*` `/superadmin/*` のみに `<div id="platform-announcement-stack">` を `</body>` 前に注入。**ストアフロントには出ない** — middleware の `$isAdmin` ガード行 32-36 で除外。Storefront 用の announcement bar コンポーネント・ミドルウェアは見つからず。 | medium | N (新規 Fujiba\Notification か新パッケージで足せる) | channel-scope (テナントごと on/off?) を要相談 |
| 4 | カートボタンレイアウト | **PARTIAL** | Desktop: `packages/Webkul/Shop/src/Resources/views/components/layouts/header/desktop/bottom.blade.php:188-195` のミニカート (`sales.checkout.shopping_cart.cart_page` 設定でゲート)。Mobile: ヘッダーには cart アイコンなし、`packages/Webkul/Shop/src/Resources/views/components/layouts/index.blade.php:621` の **bottom nav の `data-nav="cart"` のみ**。バッジは `lc-cart-badge` (line 185-201)、qty>0 で `lc-badge-show` 切り替え。**コード上は揃っているが「mobile/desktop で位置・サイズ」の不満は実機で何が崩れているか確認しないと特定できず**。 | unknown until reported | N (theme override で解決可) | item 8 (cart-page で nav 隠す) と同じ DOM |
| 5 | ゲストのログイン誘導 | **PARTIAL** | Cart-add: ゲスト許容、何も誘導なし。Checkout: `packages/Webkul/Shop/src/Http/Controllers/OnepageController.php:25-34` で `allow_guest_checkout=false` のとき **無言で `shop.customer.session.index` へリダイレクト**。フラッシュメッセージなし、ログイン後にカートへ戻る redirect intent なし。「ログインすると配送先保存・ポイント貯まる等」の説明 UX なし。 | medium | N (controller は core だがビュー側で intent + フラッシュ追加可、コントローラ自体は触らずに済む) | カート → ログイン → カート復帰の session intent を追加するなら **Y** |
| 6 | 管理者設定のソート順 | **AMBIG** | コア config: `packages/Webkul/Admin/src/Config/system.php` で各 `sort` キーで決定論的に並ぶ (静的、UI なし)。Drag reorder は `packages/Fujiba/Reorder/src/Resources/views/admin/index.blade.php` (Reorder パッケージ独自) や POS Custom Layout (project memory より「shipped」) に存在。質問が「どのカ画面のソート順か」が私には分からない。**人間に確認したい**:<br>(a) Bagisto 管理メニューの並び順 → 既に sort 数値で稼働、UI は無いが希望ある? <br>(b) Fujiba/Notification の通知テンプレート並び順? <br>(c) Fujiba 独自の何か新画面? | depends | depends | — |
| 7 | 多言語・多通貨スイッチャー | **DONE — 機能はあるが配置未確認** | Desktop: `packages/Webkul/Shop/src/Resources/views/components/layouts/header/desktop/top.blade.php:86` `<v-currency-switcher>` と locale switcher (line 関連)。Mobile: ドロワー内 `header/mobile/index.blade.php:332,382`。選択保持は core の cookie/session 経由。Project memory: `Fujiba locale codes (canonical = my)` で `my_MM` 排除済 — 既知のテナントで動く。「表示位置」の不満は実機で何が見えづらいかによる。 | small (CSS) | depends (theme override で済めば N) | item 1 (sticky bar) のヘッダー高調整と CSS 競合あり得る |
| 8 | カートページでのナビゲーション隠し | **NONE** | `packages/Webkul/Shop/src/Resources/views/checkout/cart/*.blade.php` 群に `lc-mobile-nav` を隠す JS/CSS なし。`lc-mobile-nav` は全ページで scroll-down でしか hide しない (`layouts/index.blade.php:784`)。カートページで「会計に進む」ボタンと bottom nav の cart アイコンが競合。隢路防止のため cart 詳細ページでは `lc-mobile-nav { display:none }` 推奨。 | small | N (theme/layout の body class 追加 1 行 + CSS 1 ルール) | item 4 と同じファイル群 |

---

## 監査中に見つけた関連の不具合 (修正していない、参考)

| 種別 | 場所 | 内容 |
|---|---|---|
| dead code? | `packages/Webkul/Shop/src/Resources/views/components/layouts/index.blade.php:233` | `.lc-header-spacer { display:none }` というルールがあるが、対応する DOM ノードは line 736-749 のコメントで明示的に削除済。CSS だけ残骸。掃除候補。 |
| brittle | sticky-categories.blade.php:55-65 | `mmfoodStickyCategories(src)` は Alpine だが `@pushOnce('scripts')` で push されているため、複数 CATEGORY_CAROUSEL があっても OK な反面、エラー時は `console.error` のみで UI に何も出ない (skeleton ローディングが永久に出っぱなし)。 |
| accessibility | sticky-categories.blade.php:14 | `role="tablist"` を付けているが子要素は `<a>` で `role="tab"` がない。SR ユーザーへ機能が伝わらない。 |
| z-index 衝突 | layouts/index.blade.php:206 (`lc-scroll-header z-50`) vs. PlatformAnnouncementBanner の `z-index:99998`. | Admin announcement banner はストア側に出ないので実害なし、ただし将来ストア側にも同じ仕組み載せる場合は両方 fixed/top:0 で重なる。item 3 設計時に意識。 |
| i18n | 各テナントの drawer locale switcher | テナントごとに locale 一覧 (en/ja/my) が同じ筈だが、kimono と mmfood で `core_config_data` の locale 設定が違う場合、片方で my が出ない可能性。サンプル投入で確認推奨。実コードに bug なし。 |
| guest UX | `OnepageController:33` の redirect 後、戻り先 URL が session に保存されない | ログイン後 cart へ戻らず一律 my-account へ。item 5 の本質。 |

---

## 推奨スプリント分割

3 案 (こちらの推奨は **B**)。

### A) 一本でやる (1 sprint, large)

8 項目 + 関連 bug 全部。利点: アトミック、ヘッダー CSS の競合 (item 1/4/7/8) を一度に整理できる。難点: 単一 PR が肥大化、回帰テスト範囲が広い。

### B) **推奨 — 2 phase 分割**

**Phase B-1: Header/Nav まわり (small-medium)** — item 1, 4, 7, 8, 関連 bug の z-index/dead-code 整理。同じファイル群 (`layouts/index.blade.php`, `header/*`, `sticky-categories`) を一度に編集することで競合を1回で解決。core を **触らない**前提で theme override + view_render_event 注入 + Fujiba/PwaFrontend のリスナー追加で済む。

**Phase B-2: Discovery & Onboarding (medium-large)** — item 2 (fuzzy search を Blade 側にも持ち込む or PWA で kana 正規化追加), 3 (storefront お知らせバー新規実装), 5 (guest login nudge UX 改善 + intent 復帰), 6 (要件定義 → 該当画面確定後に実装)。

理由: B-1 は CSS 中心で互いに干渉、まとめて触ったほうが効率的。B-2 は機能追加で互いに独立、優先順位を税理士判断と同じくらい重く付けるべき (例えば「お知らせバー無いと年末セールが告知できない」だと優先度高、「kana 検索」は段階導入可能)。

### C) アイテム単位 (8 sprints, micro)

毎項目を個別 PR に。利点: ロールバック容易・変更が局所的。難点: header CSS の競合を毎回再考する手間、PR レビューコスト×8。Item 6 の AMBIG が解消されない限り渋滞するリスク。

---

## Phase B 進む前に確認したいこと

1. **Item 6 の対象画面**: (a)/(b)/(c) のどれか、または別物か。AMBIG 解消後に着手判定。
2. **Item 4 の具体不満**: 実機 (mobile + desktop) で「位置・サイズ」のどこが崩れているか — スクリーン or 「desktop でカートアイコンが小さすぎる」のような言語化。コードを見る限り core 既定通り動いている。
3. **Item 3 の表示権限**: お知らせバーは admin/superadmin が編集する想定? channel ごとに on/off できる? expire 日付管理ある? 設計フェーズで先に決めたい。
4. **Item 5 の方針**: 「ログインに誘導はするがゲストチェックアウトも残す」(穏健) か、「ゲストチェックアウト禁止する」(strict) か。穏健だと nudge は「ログインすると便利」程度、strict だと現在の無言 redirect を「ログインしてください」モーダルに置換。
5. **Bagisto core (Webkul/) 触る判断**: 上記表で core=Y は item 2 (Blade fuzzy 持ち込み時のみ), item 5 (intent 復帰を controller でやる場合のみ)。**両方とも回避策あり** (theme override + middleware) なので、core を絶対触らないなら回避策に倒す。

---

## やっていないこと (制約遵守)

- accounting (`packages/Fujiba/Accounting/*`) は一切触らず・読まずスキップ
- core (`packages/Webkul/*`) はファイル参照のみ、編集なし
- 47件の dirty working tree files は触らず (ユーザー他作業)
- Phase B コード変更ゼロ、commit ゼロ、migration ゼロ、テスト追加ゼロ
- 実機ブラウザ確認はしていない (Playwright/手動 browser を起動できる環境ない、コードと CSS 数値からの推定のみ)

Phase B の優先順位指示待ち。
