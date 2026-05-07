<?php

return [
    [
        'key'   => 'accounting',
        'name'  => 'accounting::app.menu.accounting',
        'route' => 'admin.accounting.dashboard.index',
        // Bagisto's "Reports" lives at sort 7-8; place ours after it.
        'sort'  => 9,
        'icon'  => 'icon-dollar',
    ],
    [
        'key'   => 'accounting.dashboard',
        'name'  => 'accounting::app.menu.dashboard',
        'route' => 'admin.accounting.dashboard.index',
        'sort'  => 1,
        'icon'  => '',
    ],
];
