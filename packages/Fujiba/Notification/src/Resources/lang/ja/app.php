<?php

return [

    'menu' => [
        'title' => '通知設定',
    ],

    'page' => [
        'title'             => '通知テンプレート設定',
        'tab_templates'     => 'テンプレート',
        'tab_admin_alerts'  => '管理者アラート',
    ],

    'events'        => 'イベント',
    'coming_soon'   => '近日',
    'last_updated'  => '最終更新',
    'new_template'  => '新規テンプレート（未保存）',

    'event' => [
        'order.placed'    => '注文確定',
        'order.invoiced'  => '請求書発行',
        'order.shipped'   => '発送',
        'order.cancelled' => 'キャンセル',
        'order.refunded'  => '返金',
    ],

    'channel' => [
        'mail'      => 'メール',
        'messenger' => 'Messenger',
        'line'      => 'LINE',
        'whatsapp'  => 'WhatsApp',
        'telegram'  => 'Telegram',
        'instagram' => 'Instagram',
        'tiktok'    => 'TikTok',
    ],

    'recipient' => [
        'label'     => '受信者:',
        'customer'  => '顧客',
        'admin'     => '管理者アラート',
    ],

    'store' => [
        'label'         => '店舗:',
        'default_all'   => 'デフォルト（全体）',
    ],

    'pill' => [
        'store'        => '店舗:',
        'recipient'    => '受信者:',
        'last_updated' => '最終更新',
        'new_template' => '新規テンプレート（未保存）',
    ],

    'is_active'      => '有効',
    'from_name'      => '送信者名',
    'from_email'     => '送信元メール',
    'subject'        => '件名',
    'body'           => '本文',
    'save'           => '保存',
    'delete'         => '削除',
    'confirm_delete' => 'このテンプレートを削除しますか？',
    'saved'          => 'テンプレートを保存しました。',
    'deleted'        => 'テンプレートを削除しました。',
    'toggled'        => 'ステータスを更新しました。',

    'buttons' => [
        'label_max'       => 'ボタン（最大3つ — Messenger 制限）',
        'btn_label'       => 'ラベル（最大20文字）',
        'btn_value_url'   => 'URL またはペイロード',
        'btn_url'         => 'URL',
        'btn_postback'    => 'Postback',
        'btn_phone'       => '電話',
        'add_button'      => '+ ボタンを追加',
        'vars_for_button' => 'ボタン URL/値で使える変数:',
        'max_3_alert'     => 'Messenger は最大3ボタンまでです。',
    ],

    'recipients' => [
        'page_title'    => '管理者アラート',
        'heading'       => '管理者アラート受信者',
        'description'   => '注文イベント発生時に管理者アラートを受信する PSID を設定します。テンプレートタブの「管理者アラート」テンプレートが送信内容を決定します。各受信者は特定の Bagisto チャンネルに紐付け（または「デフォルト（全体）」を選択して全チャンネルのアラートを受信）できます。',
        'channel'       => 'チャンネル',
        'page_id'       => 'ページID',
        'page_id_ph'    => '例: 592609133927678',
        'psid'          => 'PSID',
        'psid_ph'       => '管理者の Messenger PSID',
        'recipient_label'   => 'ラベル',
        'recipient_label_ph'=> '例: オーナー / マネージャー',
        'add'               => '+ 追加',
        'col_label'         => 'ラベル',
        'col_active'        => '有効',
        'pill_active'       => '有効',
        'pill_inactive'     => '無効',
        'remove'            => '削除',
        'confirm_remove'    => 'この受信者を削除しますか？',
        'empty'             => '管理者受信者が未登録です。上記から追加してください。',
    ],
];
