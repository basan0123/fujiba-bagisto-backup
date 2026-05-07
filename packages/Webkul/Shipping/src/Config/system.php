<?php

return [
    [
        'key'  => 'sales.carriers.rulebased',
        'name' => 'Rule Based Shipping (Prefecture + Cool Fee)',
        'info' => 'Auto-calculates shipping by prefecture, quantity, cool/frozen fee, and free shipping thresholds.',
        'sort' => 3,
        'fields' => [
            [
                'name'          => 'title',
                'title'         => 'Title',
                'type'          => 'text',
                'value'         => 'Rule Based Shipping',
                'channel_based' => true,
                'locale_based'  => true,
            ],
            [
                'name'          => 'description',
                'title'         => 'Description',
                'type'          => 'textarea',
                'value'         => 'Shipping calculated by prefecture and quantity.',
                'channel_based' => true,
                'locale_based'  => true,
            ],
            [
                'name'          => 'active',
                'title'         => 'Status',
                'type'          => 'boolean',
                'value'         => 1,
                'channel_based' => false,
                'locale_based'  => false,
            ],
        ],
    ],
];
