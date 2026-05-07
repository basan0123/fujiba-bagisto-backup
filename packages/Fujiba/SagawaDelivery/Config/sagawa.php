<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enable / disable the delivery slot picker on checkout
    |--------------------------------------------------------------------------
    */
    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Processing days (lead time before we can ship)
    | Applies in JST. If order placed after cutoff_hour, one extra day is added.
    |--------------------------------------------------------------------------
    */
    'processing_days' => 1,
    'cutoff_hour'     => 22,    // JST hour component
    'cutoff_minute'   => 30,    // JST minute — orders after 22:30 ship +2 days

    /*
    |--------------------------------------------------------------------------
    | Zone days — additional transit days per prefecture (Sagawa estimates)
    | Keys must match the prefecture names stored by the zipcloud auto-fill
    | (e.g. 東京都, 大阪府 …)
    |--------------------------------------------------------------------------
    */
    'zone_days' => [
        '北海道' => 3,
        '青森県' => 2, '岩手県' => 2, '宮城県' => 2, '秋田県' => 2,
        '山形県' => 2, '福島県' => 2,
        '茨城県' => 1, '栃木県' => 1, '群馬県' => 1, '埼玉県' => 1,
        '千葉県' => 1, '東京都' => 1, '神奈川県' => 1,
        '新潟県' => 2, '富山県' => 2, '石川県' => 2, '福井県' => 2,
        '山梨県' => 1, '長野県' => 1, '岐阜県' => 1, '静岡県' => 1,
        '愛知県' => 1, '三重県' => 1,
        '滋賀県' => 1, '京都府' => 1, '大阪府' => 1, '兵庫県' => 1,
        '奈良県' => 1, '和歌山県' => 1,
        '鳥取県' => 2, '島根県' => 2, '岡山県' => 2, '広島県' => 2, '山口県' => 2,
        '徳島県' => 2, '香川県' => 2, '愛媛県' => 2, '高知県' => 2,
        '福岡県' => 2, '佐賀県' => 2, '長崎県' => 2, '熊本県' => 2,
        '大分県' => 2, '宮崎県' => 2, '鹿児島県' => 3, '沖縄県' => 4,
    ],

    /*
    |--------------------------------------------------------------------------
    | Available time slots
    | value => display label (Japanese)
    |--------------------------------------------------------------------------
    */
    'time_slots' => [
        'am'    => '午前中 (9:00-12:00)',
        '1214'  => '12:00-14:00',
        '1416'  => '14:00-16:00',
        '1618'  => '16:00-18:00',
        '1820'  => '18:00-20:00',
        '1921'  => '19:00-21:00',
    ],

    /*
    |--------------------------------------------------------------------------
    | Selectable date range
    |--------------------------------------------------------------------------
    */
    'max_days_ahead' => 30,

    /*
    |--------------------------------------------------------------------------
    | Sender (origin) address — used by SagawaShipmentBuilder.
    | Per-tenant/channel overrides live in sagawa_credentials table.
    |--------------------------------------------------------------------------
    */
    'sender' => [
        'name'       => env('SAGAWA_SENDER_NAME', ''),
        'postcode'   => env('SAGAWA_SENDER_POSTCODE', ''),
        'prefecture' => env('SAGAWA_SENDER_PREFECTURE', ''),
        'city'       => env('SAGAWA_SENDER_CITY', ''),
        'address'    => env('SAGAWA_SENDER_ADDRESS', ''),
        'phone'      => env('SAGAWA_SENDER_PHONE', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default waybill options
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'okuri_code'  => 'A501',  // A5 standard waybill
        'binsyu_code' => '000',   // standard (non-cool) service
    ],

    'default_okuricode' => 'A501',

    /*
    |--------------------------------------------------------------------------
    | API endpoints and HTTP behaviour
    |--------------------------------------------------------------------------
    */
    'base_url'         => env('SAGAWA_BASE_URL', 'https://api.sgsystems.co.jp'),
    'staging_base_url' => env('SAGAWA_STAGING_URL', 'https://api-stg.sgsystems.co.jp'),
    'timeout'          => (int) env('SAGAWA_TIMEOUT', 30),

    'retry' => [
        'max_attempts' => (int) env('SAGAWA_RETRY_MAX', 3),
        'base_ms'      => (int) env('SAGAWA_RETRY_BASE_MS', 300),
        'cap_ms'       => (int) env('SAGAWA_RETRY_CAP_MS', 5000),
    ],

    /*
    |--------------------------------------------------------------------------
    | PDF poll / reissue behaviour
    |--------------------------------------------------------------------------
    */
    'pdf_poll_interval'     => (int) env('SAGAWA_PDF_POLL_INTERVAL', 30),   // seconds between polls
    'pdf_poll_max_attempts' => (int) env('SAGAWA_PDF_POLL_MAX', 20),        // ~10 min ceiling
    'reissue_window_hours'  => (int) env('SAGAWA_REISSUE_WINDOW', 72),

    /*
    |--------------------------------------------------------------------------
    | Blackout dates (national holidays etc.)
    |--------------------------------------------------------------------------
    */
    'blackout_dates' => [],
];
