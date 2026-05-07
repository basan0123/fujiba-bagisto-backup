<?php

return [
    [
        'key'   => 'inventory',
        'name'  => 'reorder::app.menu.inventory',
        'route' => 'admin.inventory.reorder.index',
        'sort'  => 4,
        'icon'  => 'icon-product',
    ], [
        'key'   => 'inventory.reorder',
        'name'  => 'reorder::app.menu.reorder',
        'route' => 'admin.inventory.reorder.index',
        'sort'  => 1,
        'icon'  => '',
    ], [
        'key'   => 'inventory.reorder-history',
        'name'  => 'Reorder History',
        'route' => 'admin.inventory.reorder.history',
        'sort'  => 2,
        'icon'  => '',
    ],
];
