<?php

return [
    [
        'key'   => 'inventory',
        'name'  => 'reorder::app.acl.inventory',
        'route' => 'admin.inventory.reorder.index',
        'sort'  => 4,
    ], [
        'key'   => 'inventory.reorder',
        'name'  => 'reorder::app.acl.reorder',
        'route' => 'admin.inventory.reorder.index',
        'sort'  => 1,
    ], [
        'key'   => 'inventory.reorder-history',
        'name'  => 'Reorder History',
        'route' => 'admin.inventory.reorder.history',
        'sort'  => 2,
    ],
];
