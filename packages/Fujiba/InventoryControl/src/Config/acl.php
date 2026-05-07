<?php

return [
    [
        'key'   => 'catalog.inventory_control',
        'name'  => 'inventory-control::app.acl.group',
        'route' => 'admin.inventory_control.expiry_alerts.index',
        'sort'  => 5,
    ], [
        'key'   => 'catalog.inventory_control.expiry_alerts',
        'name'  => 'inventory-control::app.acl.expiry_alerts',
        'route' => 'admin.inventory_control.expiry_alerts.index',
        'sort'  => 1,
    ], [
        'key'   => 'catalog.inventory_control.batches',
        'name'  => 'inventory-control::app.acl.batches',
        'route' => 'admin.inventory_control.batches.index',
        'sort'  => 2,
    ], [
        'key'   => 'catalog.inventory_control.batches.create',
        'name'  => 'inventory-control::app.acl.receive_stock',
        'route' => 'admin.inventory_control.batches.create',
        'sort'  => 3,
    ], [
        'key'   => 'catalog.inventory_control.stock_take',
        'name'  => 'inventory-control::app.acl.stock_take',
        'route' => 'admin.inventory_control.stock_take.index',
        'sort'  => 4,
    ], [
        'key'   => 'catalog.products.barcode_reassign',
        'name'  => 'inventory-control::app.acl.barcode_reassign',
        'route' => 'admin.barcode_assign.index',
        'sort'  => 6,
    ],
];
