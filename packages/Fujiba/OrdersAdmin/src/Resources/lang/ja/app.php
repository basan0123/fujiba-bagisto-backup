<?php

return [
    'menu' => [
        'estimate' => '見積もり / 放棄カート',
    ],

    'tracking' => [
        'no-shipment' => '更新する出荷がありません。先にラベル印刷（または出荷）を行ってください。',
        'updated'     => '追跡番号を更新しました。',
    ],

    'payment-confirm' => [
        'confirmed'   => '入金を確認しました。',
        'unconfirmed' => '入金確認を取り消しました。',
    ],

    'label-printed' => [
        'flipped' => 'ラベルを印刷済みにしました。',
        'already' => 'ラベルはすでに印刷済みです。',
    ],

    'estimate' => [
        'page_title'        => '放棄カート / 見積もり利益',
        'subtitle'          => '注文未完了のカートと、確定した場合の予想利益を表示します。',
        'back_to_orders'    => '← 注文一覧',

        'filter_channel'    => 'チャネル',
        'all_stores'        => '全店舗',
        'filter_status'     => '状態',
        'status_all'        => 'すべて',
        'status_active'     => '有効カートのみ',
        'status_inactive'   => '無効/放棄済み',
        'filter_period'     => '期間 (日)',
        'period_7'          => '7日',
        'period_30'         => '30日',
        'period_90'         => '90日',
        'period_all'        => 'すべて',
        'min_items'         => '最小商品数',
        'search_customer'   => '検索 (顧客)',
        'search_placeholder' => 'メール / 名前',

        'stat_cart_count'   => '放棄カート',
        'stat_total_items'  => '商品数合計',
        'stat_subtotal_sum' => '小計合計',
        'stat_profit_sum'   => '予想利益 (集計可能)',
        'stat_carts_missing_cost' => '原価未設定カート',
        'stat_unique_missing_products' => '原価未設定の商品種',

        'col_customer'      => '顧客',
        'col_store'         => '店舗',
        'col_items'         => '商品',
        'col_subtotal'      => '小計',
        'col_shipping'      => '配送料',
        'col_cost_total'    => '原価合計',
        'col_profit'        => '予想利益',
        'col_status'        => '状態',
        'col_last_activity' => '最終活動',

        'loading'           => '読み込み中...',
        'empty_state'       => '放棄カートはありません 👍',
        'guest'             => 'ゲスト',
        'cost_missing'      => '未設定あり',
        'cannot_calc'       => '⚠ 算出不可',
        'pill_active'       => '有効',
        'pill_abandoned'    => '放棄',
        'pill_repeat_order' => '追加注文中',
        'pill_coupon'       => 'クーポン',
        'last_order'        => '前回注文',
        'updated'           => '更新',
        'created'           => '作成',
        'expand_open'       => '開く',
        'expand_close'      => '閉じる',

        'cart_details'      => 'カート明細',
        'col_item'          => '商品',
        'col_qty'           => '数量',
        'col_price'         => '売価',
        'col_cost'          => '原価',
        'col_line_subtotal' => '小計',
        'col_profit_per'    => '利益/個',
        'cost_unset'        => '未設定',
        'missing_cost_label' => '⚠ 原価未設定:',

        'count_unit'        => '件',
        'missing_cost_title_prefix' => '原価未設定:',
        'last_order_title_prefix'   => '前回注文:',
    ],
];
