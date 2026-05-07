<?php

return [
    [
        'key'   => 'shipping_rules',
        'name'  => 'shipping::app.menu.shipping_rules',
        'route' => 'admin.shipping.cool-fees.index',
        'sort'  => 6,
        'icon'  => 'icon-sales',
    ],
    [
        'key'   => 'shipping_rules.cool_fee',
        'name'  => 'shipping::app.menu.cool_fee',
        'route' => 'admin.shipping.cool-fees.index',
        'sort'  => 1,
        'icon'  => '',
    ],
    [
        'key'   => 'shipping_rules.thresholds',
        'name'  => 'shipping::app.menu.thresholds',
        'route' => 'admin.shipping.thresholds.index',
        'sort'  => 2,
        'icon'  => '',
    ],
    [
        'key'   => 'shipping_rules.prefecture',
        'name'  => 'shipping::app.menu.prefecture',
        'route' => 'admin.shipping.prefecture-rates.index',
        'sort'  => 3,
        'icon'  => '',
    ],
    // bulk_bonus menu hidden 2026-05-01: cart_rules in /admin/marketing/promotions/cart-rules
    // is the canonical source. Route + controller + handler still exist; flip back on by
    // restoring this entry. Reason: parallel logic confused operators; cart_rules covers
    // qty thresholds and is admin-managed via the standard Bagisto promotions UI.
    [
        'key'   => 'shipping_rules.zones',
        'name'  => 'shipping::app.menu.zones',
        'route' => 'admin.shipping.zones.index',
        'sort'  => 5,
        'icon'  => '',
    ],
];
