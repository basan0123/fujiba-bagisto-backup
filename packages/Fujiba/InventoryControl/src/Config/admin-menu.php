<?php

return [
    [
        'key'   => 'catalog.stock_take',
        'name'  => 'inventory-control::app.menu.stock_take',
        'route' => 'admin.inventory_control.stock_take.index',
        'sort'  => 6,
        'icon'  => '',
    ], [
        'key'   => 'catalog.batches',
        'name'  => 'inventory-control::app.menu.batches',
        'route' => 'admin.inventory_control.batches.index',
        'sort'  => 7,
        'icon'  => '',
    ], [
        'key'   => 'catalog.receive_stock',
        'name'  => 'inventory-control::app.menu.receive_stock',
        'route' => 'admin.inventory_control.batches.create',
        'sort'  => 8,
        'icon'  => '',
    ], [
        'key'   => 'catalog.expiry_alerts',
        'name'  => 'inventory-control::app.menu.expiry_alerts',
        'route' => 'admin.inventory_control.expiry_alerts.index',
        'sort'  => 9,
        'icon'  => '',
    ],
];
