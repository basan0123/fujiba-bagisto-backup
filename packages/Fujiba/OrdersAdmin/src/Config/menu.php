<?php

/**
 * Adds the abandoned-cart estimate page under Sales.
 *
 * Bagisto's nav is keyed by dotted path (parent.child); 'sales' is already
 * registered by Webkul/Admin, so we only emit the leaf.
 */
return [
    [
        'key'   => 'sales.estimate',
        'name'  => 'orders-admin::app.menu.estimate',
        'route' => 'admin.sales.estimate',
        'sort'  => 6,
        'icon'  => '',
    ],
];
