<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Fiscal year start month (1-12)
    |--------------------------------------------------------------------------
    | Japanese SMEs typically use April → March. Override per-tenant later.
    */
    'fiscal_year_start_month' => env('FUJIBA_ACCOUNTING_FY_START', 4),

    'base_currency' => env('FUJIBA_ACCOUNTING_CURRENCY', 'JPY'),
    'default_locale' => env('FUJIBA_ACCOUNTING_LOCALE', 'ja'),

    /*
    |--------------------------------------------------------------------------
    | Channel ↔ shop alias map
    |--------------------------------------------------------------------------
    | NULL channel_id = corporate-level (consolidated) entries.
    */
    'channels' => [
        1 => 'kimono',
        2 => 'mmfood',
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit log
    |--------------------------------------------------------------------------
    */
    'audit' => [
        'enabled' => true,
        // Skip writing audit rows for these attribute-only changes (noise reduction).
        'ignore_attribute_only_changes' => [
            \Fujiba\Accounting\Models\Account::class => ['sort_order'],
        ],
    ],
];
