<?php

return [
    [
        'key'   => 'sagawa',
        'name'  => 'sagawa::app.menu.title',
        'route' => 'admin.sagawa.queue.index',
        'sort'  => 7,
        'icon'  => 'icon-shipping',
    ], [
        'key'   => 'sagawa.queue',
        'name'  => 'sagawa::app.menu.queue',
        'route' => 'admin.sagawa.queue.index',
        'sort'  => 1,
        'icon'  => '',
    ], [
        'key'   => 'sagawa.credentials',
        'name'  => 'sagawa::app.menu.credentials',
        'route' => 'admin.sagawa.credentials.index',
        'sort'  => 2,
        'icon'  => '',
    ], [
        'key'   => 'sagawa.schedule',
        'name'  => 'sagawa::app.menu.schedule',
        'route' => 'admin.sagawa.schedule.index',
        'sort'  => 3,
        'icon'  => '',
    ], [
        'key'   => 'sagawa.usage',
        'name'  => 'sagawa::app.menu.usage',
        'route' => 'admin.sagawa.usage',
        'sort'  => 4,
        'icon'  => '',
    ],
];
