<?php

return [
    'title'                 => 'Komoju',
    'description'           => 'Credit card, konbini, bank transfer, PayPay, LINE Pay via Komoju',
    'redirect'              => 'You will be redirected to Komoju to complete payment.',
    'merchant-id'           => 'Merchant ID',
    'merchant-id-info'      => 'Your Komoju Merchant ID (for reference only).',
    'secret-key'            => 'Secret Key',
    'secret-key-info'       => 'Your Komoju API secret key (sk_live_xxx or sk_test_xxx).',
    'publishable-key'       => 'Publishable Key',
    'publishable-key-info'  => 'Your Komoju publishable key (pk_xxx).',
    'webhook-secret'        => 'Webhook Secret',
    'webhook-secret-info'   => 'Webhook signing secret from Komoju dashboard.',
    'payment-types'         => 'Enabled Payment Types',
    'payment-types-info'    => 'Comma-separated: credit_card,konbini,bank_transfer,paypay,linepay',
    'payment-failed'        => 'Komoju payment failed. Please try again.',
    'payment-canceled'      => 'Komoju payment was canceled.',
];
