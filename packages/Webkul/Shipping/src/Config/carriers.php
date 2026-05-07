<?php

return [
    'flatrate' => [
        'code'         => 'flatrate',
        'title'        => 'Flat Rate',
        'description'  => 'Flat Rate Shipping',
        'active'       => false,
        'default_rate' => '10',
        'type'         => 'per_unit',
        'class'        => 'Webkul\Shipping\Carriers\FlatRate',
    ],

    'free' => [
        'code'         => 'free',
        'title'        => 'Free Shipping',
        'description'  => 'Free Shipping',
        'active'       => false,
        'default_rate' => '0',
        'class'        => 'Webkul\Shipping\Carriers\Free',
    ],

    'rulebased' => [
        'code'        => 'rulebased',
        'title'       => 'Rule Based Shipping',
        'description' => 'Auto-calculates by prefecture, quantity, cool/frozen fee.',
        'active'      => true,
        'class'       => 'Webkul\Shipping\Carriers\RuleBasedShipping',
    ],
];
