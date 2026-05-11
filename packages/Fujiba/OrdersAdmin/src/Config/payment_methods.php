<?php

/**
 * Fujiba-only payment methods that extend Webkul's defaults.
 *
 * `cash` — admin-only walk-in shop method. Staff records cash received when
 * handing the order over to the customer at the store (typically a pickup
 * order, but not restricted). No COD surcharge applies — that fee belongs
 * only to Sagawa's 代金引換.
 *
 * `admin_only: true` is a marker for a future storefront filter. Webkul
 * core ignores unknown keys, so it's safe to add.
 *
 * Merged into config('payment_methods') from OrdersAdminServiceProvider.
 */
return [
    'cash' => [
        'code'        => 'cash',
        'title'       => '現金 (Cash)',
        'description' => 'Cash payment recorded by staff at handover. Admin-only.',
        'class'       => \Webkul\Payment\Payment\MoneyTransfer::class,
        'active'      => true,
        'sort'        => 3,
        'admin_only'  => true,
    ],
];
