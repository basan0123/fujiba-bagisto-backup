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
    [
        'key'   => 'accounting.journal',
        'name'  => 'accounting::app.menu.journal',
        'route' => 'admin.accounting.journal.index',
        'sort'  => 2,
        'icon'  => '',
    ],
    [
        'key'   => 'accounting.receipts',
        'name'  => 'accounting::app.menu.receipts',
        'route' => 'admin.accounting.receipts.index',
        'sort'  => 3,
        'icon'  => '',
    ],
    [
        'key'   => 'accounting.staff',
        'name'  => 'accounting::app.menu.staff',
        'route' => 'admin.accounting.staff.index',
        'sort'  => 4,
        'icon'  => '',
    ],
    [
        'key'   => 'accounting.timecards',
        'name'  => 'accounting::app.menu.timecard',
        'route' => 'admin.accounting.timecards.index',
        'sort'  => 5,
        'icon'  => '',
    ],
    [
        'key'   => 'accounting.payroll',
        'name'  => 'accounting::app.menu.payroll',
        'route' => 'admin.accounting.payroll.index',
        'sort'  => 6,
        'icon'  => '',
    ],
];
