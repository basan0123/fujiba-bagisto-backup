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

    /*
    |--------------------------------------------------------------------------
    | OCR (receipt extraction)
    |--------------------------------------------------------------------------
    | Drivers: 'stub' (default, no external calls) | 'azure' (Document Intelligence).
    */
    'ocr' => [
        'driver' => env('FUJIBA_OCR_DRIVER', 'stub'),
        'azure'  => [
            'endpoint' => env('FUJIBA_OCR_AZURE_ENDPOINT'),
            'key'      => env('FUJIBA_OCR_AZURE_KEY'),
            'model'    => env('FUJIBA_OCR_AZURE_MODEL', 'prebuilt-receipt'),
            'api_version' => env('FUJIBA_OCR_AZURE_API_VERSION', '2024-11-30'),
            'poll_interval_ms' => 1000,
            'poll_max_attempts' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Receipt upload
    |--------------------------------------------------------------------------
    */
    'receipts' => [
        'storage_disk'   => env('FUJIBA_RECEIPT_DISK', 'local'),
        'storage_root'   => 'accounting/receipts',
        'max_size_kb'    => 10240,                 // 10 MB
        'allowed_mimes'  => ['jpg', 'jpeg', 'png', 'pdf'],
    ],

    /*
    |--------------------------------------------------------------------------
    | 国税庁 適格請求書発行事業者 公表サイト API
    |--------------------------------------------------------------------------
    | Get a free app_id at https://www.invoice-kohyo.nta.go.jp/web-api/.
    | If unset, lookups gracefully no-op (system still captures T-numbers,
    | just doesn't validate them against the registry).
    */
    /*
    |--------------------------------------------------------------------------
    | Dashboard / report cache
    |--------------------------------------------------------------------------
    | TTL for the dashboard widget aggregates and the heavier reports
    | (trial balance, P/L, B/S). 1 hour is a good default — bookkeeping data
    | doesn't move by the second, and the cache miss cost on first load is
    | the only real penalty. Pass `?nocache=1` on any report URL to bypass.
    */
    'dashboard' => [
        'cache_ttl_seconds' => (int) env('FUJIBA_DASH_CACHE_TTL', 3600),
        'cache_prefix'      => 'fujiba:accounting:report:',
    ],

    'nta_invoice_api' => [
        'app_id'    => env('FUJIBA_NTA_INVOICE_API_ID'),
        'base_url'  => env('FUJIBA_NTA_INVOICE_API_URL', 'https://web-api.invoice-kohyo.nta.go.jp/v1/num'),
        'cache_days' => 30,
        'rate_limit_per_sec' => 10,
    ],
];
