<?php

return [
    'dashboard' => [

        // ───────────── range_presets (quick-range bar above filters) ─────────────
        'range_presets' => [
            'today'                => '今日',
            'yesterday'            => '昨日',
            'day_before_yesterday' => '一昨日',
            'this_month'           => '今月',
            'last_month'           => '先月',
            'last_3m'              => '直近3ヶ月',
            'this_fiscal_year'     => '今期',
            'last_fiscal_year'     => '前期',
            'this_year'            => '今年',
            'all'                  => '全期間',
            'all_stores'           => '全店',
        ],

        // ───────────── fiscal_year (会計年度 system config) ─────────────
        'fiscal_year' => [
            'section_title'    => '会計年度',
            'section_info'     => '自社の決算期に合わせて会計年度の開始月を設定します。ダッシュボードの「今期」「前期」の集計期間に反映されます。',
            'settings_title'   => '会計年度の設定',
            'start_month'      => '会計年度の開始月',
            'start_month_info' => '日本企業の多くは4月（4月〜翌3月）です。会社ごとに変更できます。',
            'months' => [
                '1'  => '1月',
                '2'  => '2月',
                '3'  => '3月',
                '4'  => '4月',
                '5'  => '5月',
                '6'  => '6月',
                '7'  => '7月',
                '8'  => '8月',
                '9'  => '9月',
                '10' => '10月',
                '11' => '11月',
                '12' => '12月',
            ],
        ],

        // ───────────── common ─────────────
        'common' => [
            'loading'         => '読み込み中...',
            'error_load'      => 'データを取得できませんでした',
            'retry'           => '再試行',
            'refresh'         => '更新',
            'view_all'        => 'すべて表示',
            'show_more'       => 'もっと見る',
            'hide'            => '閉じる',
            'last_updated'    => '最終更新 :time',
            'no_data'         => 'データがありません',
            'today'           => '本日',
            'this_month'      => '今月',
            'last_month'      => '先月',
            'last_7_days'     => '直近7日間',
            'last_30_days'    => '直近30日間',
            'wrong_channel'   => '着物チャネル限定です。チャネルを切り替えてください。',
        ],

        // ───────────── W1: hero ─────────────
        'hero' => [
            'title'           => '本日の概要',
            'orders_today'    => '本日の注文数',
            'revenue_today'   => '本日の売上',
            'aov_today'       => '平均注文金額',
            'no_data'         => '本日の注文はまだありません',
        ],

        // ───────────── W1: business_overview ─────────────
        'business_overview' => [
            'title'              => '経営サマリー',
            'subtitle'           => '指定期間の収支フロー',
            'card_revenue'                  => '売上',
            'card_profit'                   => '利益',
            'card_margin'                   => '利益率',
            'card_inventory_cost'           => '現在庫×仕入',
            'card_inventory_cost_subtitle'  => '現在の在庫評価額',
            'card_fixed_expenses'           => '固定費 合計',
            'card_fixed_expenses_subtitle'  => '期間合計（手動入力）',
            'card_delivery'                 => '送料 合計',
            'card_delivery_subtitle'        => '期間合計',
            'card_open_orders'              => '未処理注文',
            'card_open_orders_subtitle'     => '未払い＋処理中',
            'card_open_orders_tooltip'      => '請求書発行または出荷待ちの注文です。未払い＝注文済み・請求書未発行。処理中＝請求書発行済み・未出荷／未完了。',
            'card_profit_caveat'            => '利益には配送業者への支払送料が含まれていません（まだ未入力）。配送業者コストが入力されると、利益はその分減少します。',
            'section_financial'             => '業績',
            'section_operational'           => '業務',
            'section_inventory_cost'        => '在庫＋コスト',
            'card_aov'                      => '平均注文額',
            'card_aov_subtitle'             => '注文あたり',
            'card_total_orders'             => '総注文数',
            'card_total_customers'          => '総顧客数',
            'card_unpaid_invoices'          => '未払い請求書',
            'card_unpaid_invoices_subtitle' => '全社・全期間',
            'card_total_stock'              => '総在庫数',
            'card_total_stock_subtitle'     => '全SKU合計',
            'profit_label'       => '利益',
            'profit_margin'      => '利益率 :percent%',
            'expenses_hidden'    => 'POS取引がないため経費は非表示',
            'cogs_caveat'        => '売上原価は現在の仕入価格を使用しています。仕入価格が未設定の商品は0として計算されるため、販売された全商品が反映されているわけではありません。',
            'period_today'       => '本日',
            'period_7d'          => '直近7日間',
            'period_30d'         => '直近30日間',
            'period_mtd'         => '当月累計',
            'empty_state'        => 'この期間内の注文はありません',
        ],

        // ───────────── W11: total_profit (business_overviewから参照) ─────────────
        'total_profit' => [
            'title'              => '総利益',
            'subtitle'           => '売上から全コスト控除後',
            'breakdown_revenue'  => '売上',
            'breakdown_cogs'     => '売上原価',
            'breakdown_delivery' => '配送料',
            'breakdown_waste'    => '廃棄損',
            'breakdown_expenses' => '営業経費',
            'breakdown_fees'     => '決済手数料',
            'empty_state'        => 'この期間内に完了した注文はありません。',
        ],

        // ───────────── W12: delivery_fees ─────────────
        'delivery_fees' => [
            'title'        => '配送料収入',
            'subtitle'     => '顧客から徴収した配送料',
            'empty_state'  => 'この期間内の配送はありません。',
        ],

        // ───────────── 配送パフォーマンス（マージン + 送料無料の補填） ─────────────
        'shipping_performance' => [
            'title'                => '配送パフォーマンス',
            'loading'              => '読み込み中…',
            'collected'            => 'お客様からの送料収入',
            'carrier_cost'         => '実際の配送業者コスト',
            'margin'               => '差額',
            'margin_hint'          => 'プラス＝送料収入が業者請求を上回る',
            'over_n_orders'        => '対象注文 :count 件',
            'fully_reconciled'     => '全注文が照合済み',
            'pending_n'            => '請求書未登録 :count 件',
            'free_shipping_block'  => '送料無料の適用状況',
            'free_orders'          => '送料無料の注文数',
            'subsidy'              => '送料無料による負担額',
            'avg_subsidy'          => '注文あたり平均',
            'by_rule'              => '無料ルール別',
            'rule_other'           => 'その他 / 未マッチ',
            'no_free_orders'       => 'この期間に送料無料の注文はありません。',
            'manage_costs'         => '配送業者コストを管理',
            'import'               => [
                'page_title'        => '配送業者コスト',
                'back_to_dashboard' => 'ダッシュボードへ戻る',
                'upload_section'    => '配送業者請求 CSV のインポート',
                'csv_file'          => 'CSV ファイル',
                'carrier_code'      => '配送業者（既定）',
                'billing_month'     => '請求月（既定）',
                'submit'            => 'インポート',
                'format_hint'       => 'CSV ヘッダ: tracking_number, carrier_cost [, billing_month, carrier_code, notes]。または tracking_number の代わりに order_id を使用可能。',
                'recent_section'    => '最近の登録',
                'empty_state'       => '登録された業者コストはまだありません。',
                'done'              => ':count 行をインポートしました。',
                'saved'             => '保存しました。',
                'read_failed'       => 'CSV ファイルを読み込めませんでした。',
                'empty'             => 'CSV ファイルが空です。',
                'bad_header'        => 'CSV には tracking_number または order_id と、carrier_cost が必要です。',
                'unknown_order'     => '注文が見つかりません。',
                'unmatched_header'  => ':count 行を注文と照合できませんでした',
                'last_month_label'  => '最終月 :m',
            ],
        ],

        // ───────────── W2/W3: inventory_value ─────────────
        'inventory_value' => [
            'title'                 => '在庫価値',
            'stock_value'           => '在庫評価額',
            'priced_sku_count'      => '価格設定済みSKU数',
            'missing_cost'          => '原価未設定',
            'missing_cost_badge'    => '原価未設定（:count件）',
            'set_cost_link'         => '原価を設定する',
            'as_of'                 => ':time 時点',
            'empty_state'           => '在庫データがありません',
            'all_company_label'     => '全社',
            'all_company_subtitle'  => '全チャネル合計',
            'all_channels_label'    => '全チャネル — 全社合計',
        ],

        // ───────────── W4: subscribers ─────────────
        'subscribers' => [
            'title'           => '購読者',
            'total'           => '購読者総数',
            'subscriber_gain' => '購読者増加数',
            'delta_up'        => '+:count',
            'delta_down'      => ':count',
            'delta_flat'      => '増減なし',
            'empty_state'     => '購読者がまだいません',
            'empty_hint'      => '購読者数が5名以上になると、増加チャートが表示されます。',
        ],

        // ───────────── W5: payment_methods ─────────────
        'payment_methods' => [
            'title'              => '支払方法別売上',
            'subtitle'           => '支払方法ごとの注文数比率',
            'col_method'         => '支払方法',
            'col_orders'         => '注文数',
            'col_gross'          => '売上合計',
            'col_fees'           => '決済手数料(推定)',
            'col_net'            => '純売上',
            'gateway_fees'       => '決済手数料',
            'fees_estimate_note' => '手数料は設定済みのレートに基づく推定値です',
            'empty_state'        => '指定期間内の決済はありません',
        ],

        // ───────────── Orders by Region (日本地図ヒートマップ) ─────────────
        'orders_by_region' => [
            'title'           => '地域別注文数',
            'subtitle'        => '都道府県別の注文分布',
            'col_orders'      => '注文数',
            'col_revenue'     => '売上',
            'top_prefectures' => '上位都道府県',
            'empty_state'     => '指定期間内に配送先を持つ注文はありません',
        ],

        // ───────────── 顧客住所(都道府県)別売上 (横棒グラフ) ─────────────
        'sales_by_state' => [
            'title'         => '顧客住所(都道府県)別売上',
            'subtitle'      => '配送先都道府県別の売上ランキング',
            'caption'       => '顧客を配送先住所の都道府県でグルーピングしています。',
            'total_revenue' => '売上合計',
            'total_orders'  => '注文合計',
            'states_count'  => '都道府県数',
            'orders_short'  => '件',
            'unmapped'      => '未マッピング(都道府県不明)',
            'empty_state'   => '指定期間内に配送先を持つ売上はありません',
        ],

        // ───────────── Cart Activity (ライブカート活動、ダッシュボード上段) ─────────────
        'cart_activity' => [
            'title'           => 'カート活動',
            'action_converted' => '購入完了',
            'action_abandoned' => 'カートから削除',
            'action_active'   => 'カートに追加',
            'state_all'       => 'すべて',
            'state_active'    => '進行中',
            'state_abandoned' => '放棄',
            'state_converted' => '購入完了',
            'guest'           => 'ゲスト',
            'empty_state'     => 'カート活動はまだありません',
            'cart_id'         => 'カート',
            'col_item'        => '商品',
            'col_qty'         => '数量',
            'col_total'       => '小計',
            'sub_total'       => '小計',
            'discount'        => '割引',
            'tax'             => '税',
            'shipping'        => '送料',
            'grand_total'     => '合計',
            'cost'             => '原価',
            'estimated_profit' => '推定利益',
            'cost_missing'     => '商品に原価が未設定のため、利益が計算できません',
            'detail_failed'   => 'カート詳細の取得に失敗しました',
            'not_found'       => 'カートが見つかりません',
        ],

        // ───────────── W6: cart_funnel ─────────────
        'cart_funnel' => [
            'title'           => 'カート遷移',
            'subtitle'        => '訪問から購入までの遷移率',
            'stage_visits'    => '訪問',
            'stage_cart_add'  => 'カート投入',
            'stage_checkout'  => 'チェックアウト到達',
            'stage_paid'      => '支払い完了',
            'stage_completed' => '注文完了',
            'drop_off'        => '-:percent% 離脱',
            'conversion_rate' => '全体: :percent%',
            'empty_state'     => 'ファネルを描画するデータが不足しています',
        ],

        // ───────────── W7: cart_status ─────────────
        'cart_status' => [
            'title'             => 'カート状況',
            'abandoned_cart'    => '放棄されたカート',
            'recovered_cart'    => '回収されたカート',
            'abandonment_rate'  => 'カート放棄率',
            'recovery_rate'     => 'カート回収率',
            'value_at_risk'     => '機会損失額',
            'recovery_disabled' => '回収追跡は有効になっていません',
            'empty_state'       => '指定期間内に放棄されたカートはありません',
        ],

        // ───────────── W8: pos_vs_online ─────────────
        'pos_vs_online' => [
            'title'          => '店舗 vs オンライン',
            'subtitle'       => '指定期間のチャネル別売上構成',
            'pos_label'      => '店舗（POS）',
            'online_label'   => 'オンライン',
            'pos_revenue'    => '店舗売上',
            'online_revenue' => 'オンライン売上',
            'pos_orders'     => '店舗注文数',
            'online_orders'  => 'オンライン注文数',
            'mix_percent'    => ':percent% :channel',
            'empty_state'    => 'POSデータがまだありません',
        ],

        // ───────────── W9: total_waste ─────────────
        'total_waste' => [
            'title'             => '廃棄合計',
            'subtitle'          => '不良在庫の損失額',
            'total_amount'      => '損失額合計',
            'total_qty'         => '廃棄数量合計',
            'total_stock_items' => '総在庫アイテム数',
            'top_reason'        => '主な理由',
            'empty_state'       => '廃棄記録はありません',
        ],

        // ───────────── W10: total_expense ─────────────
        'total_expense' => [
            'title'         => '経費合計',
            'subtitle'      => '固定費 + 現金支出',
            'total_amount'  => '経費合計',
            'fixed_portion' => '固定費',
            'cash_portion'  => '現金支出',
            'empty_state'   => '経費記録はありません',
        ],

    ],
];
