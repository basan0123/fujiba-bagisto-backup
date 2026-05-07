<?php

return [
    'pos_cash' => [
        'code'        => 'pos_cash',
        'title'       => 'Pos Cash Payment',
        'description' => 'Pos Cash Payment',
        'class'       => 'Webkul\Pos\Payment\PosCash',
        'active'      => false,
        'sort'        => 4,
    ],

    'pos_card' => [
        'code'        => 'pos_card',
        'title'       => 'Pos Card Payment',
        'description' => 'Pos Card Payment',
        'class'       => 'Webkul\Pos\Payment\PosCard',
        'active'      => false,
        'sort'        => 5,
    ],

    'pos_split' => [
        'code'        => 'pos_split',
        'title'       => 'Pos Split Payment',
        'description' => 'Pos Split Payment',
        'class'       => 'Webkul\Pos\Payment\PosSplit',
        'active'      => false,
        'sort'        => 6,
    ],
];
