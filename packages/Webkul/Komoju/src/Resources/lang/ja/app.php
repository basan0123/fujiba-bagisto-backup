<?php

return [
    'title'                 => 'Komoju',
    'description'           => 'クレジットカード・コンビニ・銀行振込・PayPay・LINE Payでお支払い（Komoju経由）',
    'redirect'              => 'Komojuに移動してお支払いを完了してください。',
    'merchant-id'           => 'マーチャントID',
    'merchant-id-info'      => 'Komojuのマーチャント ID（参考用）。',
    'secret-key'            => 'シークレットキー',
    'secret-key-info'       => 'Komoju APIシークレットキー（sk_live_xxx または sk_test_xxx）。',
    'publishable-key'       => '公開キー',
    'publishable-key-info'  => 'Komoju公開キー（pk_xxx）。',
    'webhook-secret'        => 'Webhookシークレット',
    'webhook-secret-info'   => 'Komojuダッシュボードで発行されたWebhook署名シークレット。',
    'payment-types'         => '有効な決済方法',
    'payment-types-info'    => 'カンマ区切り: credit_card,konbini,bank_transfer,paypay,linepay',
    'payment-failed'        => 'Komojuでの決済に失敗しました。もう一度お試しください。',
    'payment-canceled'      => 'Komojuでの決済がキャンセルされました。',
];
