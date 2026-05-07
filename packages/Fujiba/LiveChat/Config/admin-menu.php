<?php

return [
    [
        'key'   => 'livechat',
        'name'  => 'fujiba_livechat::app.menu.livechat',
        'route' => 'admin.fujiba.livechat.index',
        'sort'  => 4,
        'icon'  => 'icon-mail',
    ], [
        'key'   => 'livechat.conversations',
        'name'  => 'fujiba_livechat::app.menu.conversations',
        'route' => 'admin.fujiba.livechat.index',
        'sort'  => 1,
        'icon'  => '',
    ], [
        'key'   => 'livechat.settings',
        'name'  => 'fujiba_livechat::app.menu.settings',
        'route' => 'admin.fujiba.livechat.settings.index',
        'sort'  => 2,
        'icon'  => '',
    ], [
        'key'   => 'livechat.abandoned_cart',
        'name'  => 'fujiba_livechat::app.menu.abandoned_cart',
        'route' => 'admin.fujiba.livechat.settings.index',
        'sort'  => 3,
        'icon'  => '',
    ],
];
