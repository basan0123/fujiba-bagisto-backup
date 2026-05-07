<?php

return [
    'title'              => '廃棄管理',
    'add'                => '廃棄を追加',
    'add-record'         => '廃棄レコードを追加',
    'edit-record'        => '廃棄レコードを編集',
    'filter'             => '絞り込み',
    'reset'              => 'リセット',
    'records'            => '件',
    'filtered-total'     => '絞り込み合計金額',
    'total-qty'          => '合計数量',

    'columns' => [
        'date'    => '日付',
        'product' => '商品',
        'sku'     => 'SKU',
        'channel' => 'チャネル',
        'qty'     => '数量',
        'amount'  => '金額',
        'reason'  => '理由',
        'notes'   => 'メモ',
        'actions' => '操作',
    ],

    'empty'              => '廃棄レコードはありません。',

    'actions' => [
        'edit'    => '編集',
        'delete'  => '削除',
        'save'    => '保存',
        'cancel'  => 'キャンセル',
        'confirm' => '確認',
        'close'   => '閉じる',
    ],

    'form' => [
        'product'         => '商品',
        'search-product'  => '商品名・SKU・バーコードで検索',
        'qty'             => '数量',
        'amount'          => '金額（¥）',
        'amount-hint'     => '仕入価格 × 数量から自動計算',
        'buy-price'       => '仕入価格（¥）',
        'buy-price-hint'  => '商品の原価から自動入力されます。手動で上書きできます',
        'reason'          => '理由',
        'notes'           => 'メモ',
        'date'            => '廃棄日',
        'channel'         => 'チャネル',
        'save-btn'        => '廃棄を保存',
        'update-btn'      => '廃棄を更新',
    ],

    'reasons' => [
        'expired' => '期限切れ',
        'damaged' => '破損',
        'theft'   => '盗難',
        'other'   => 'その他',
    ],

    'confirm' => [
        'delete-title' => 'この廃棄レコードを削除しますか？',
        'delete-text'  => 'この操作は取り消せません。',
    ],

    'messages' => [
        'saved'   => '廃棄レコードを保存しました。',
        'updated' => '廃棄レコードを更新しました。',
        'deleted' => '廃棄レコードを削除しました。',
        'error'   => '操作に失敗しました。',
    ],

    'menu' => [
        'catalog' => 'カタログ',
        'waste'   => '廃棄',
    ],
];
