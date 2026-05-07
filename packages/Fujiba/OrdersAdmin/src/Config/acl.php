<?php

/*
 * The `sales.orders.edit` ACL key used by this package is registered
 * directly in packages/Webkul/Admin/src/Config/acl.php.
 *
 * `sales.estimate` is registered here so the abandoned-cart page is
 * grantable in admin role permissions and the sidebar menu entry shows
 * up for any admin granted that key.
 */

return [
    [
        'key'   => 'sales.estimate',
        'name'  => '見積もり / 放棄カート',
        'route' => 'admin.sales.estimate',
        'sort'  => 6,
    ],
];
