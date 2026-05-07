<?php

/**
 * Sagawa Delivery Configuration
 * 
 * Sagawa is a Japanese delivery service with zone-based delivery times.
 * This configuration maps prefectures to delivery zones and defines
 * delivery time slots and other settings.
 * 
 * Usage: 
 *   $config = config('sagawa');
 *   $zones = $config['zones'];
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Sagawa Delivery Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for Sagawa delivery service
    */

    // Origin location (where orders ship from)
    'origin' => [
        'prefecture' => '埼玉県',
        'city' => '蕨市',
        'area' => 'Toda Area',
    ],

    // Order cut-off time (2 PM = orders after 2 PM start delivery next day)
    'cutoff_hour' => 14,

    // Skip Sundays in delivery calculation
    'skip_sundays' => true,

    // Japanese holidays (can be loaded from database as well)
    'holidays' => [
        '2024-01-01' => '元日',
        '2024-01-08' => '成人の日',
        '2024-02-11' => '建国記念の日',
        '2024-02-12' => '振替休日',
        '2024-02-23' => '天皇誕生日',
        '2024-03-20' => '春分の日',
        '2024-04-29' => '昭和の日',
        '2024-05-03' => '憲法記念日',
        '2024-05-04' => 'みどりの日',
        '2024-05-05' => 'こどもの日',
        '2024-05-06' => '振替休日',
        '2024-07-15' => '海の日',
        '2024-08-11' => '山の日',
        '2024-08-12' => '振替休日',
        '2024-09-16' => '敬老の日',
        '2024-09-22' => '秋分の日',
        '2024-09-23' => '振替休日',
        '2024-10-14' => 'スポーツの日',
        '2024-11-03' => '文化の日',
        '2024-11-04' => '振替休日',
        '2024-11-23' => '勤労感謝の日',
    ],

    /*
    |--------------------------------------------------------------------------
    | Prefecture to Zone Mapping
    |--------------------------------------------------------------------------
    |
    | Each zone maps to delivery days from origin (Toda, Saitama)
    | Zone 1: 1 day (Tokyo, Saitama, Kanagawa, Chiba)
    | Zone 2: 2 days (Kanto + some Tokai)
    | Zone 3: 2-3 days (Kansai)
    | Zone 4: 3 days (Kansai extending)
    | Zone 5-8: 3-4 days (Western Japan, Hokkaido, Okinawa)
    */
    'prefectures' => [
        // Zone 1 - 1 Day (関東)
        '東京都' => ['zone' => 1, 'days' => 1],
        '埼玉県' => ['zone' => 1, 'days' => 1],
        '神奈川県' => ['zone' => 1, 'days' => 1],
        '千葉県' => ['zone' => 1, 'days' => 1],

        // Zone 2 - 2 Days (甲信越・東北一部)
        '山梨県' => ['zone' => 2, 'days' => 2],
        '長野県' => ['zone' => 2, 'days' => 2],
        '群馬県' => ['zone' => 2, 'days' => 2],
        '栃木県' => ['zone' => 2, 'days' => 2],
        '茨城県' => ['zone' => 2, 'days' => 2],
        '静岡県' => ['zone' => 2, 'days' => 2],

        // Zone 3 - 2-3 Days (中部)
        '愛知県' => ['zone' => 3, 'days' => 3],
        '岐阜県' => ['zone' => 3, 'days' => 3],
        '三重県' => ['zone' => 3, 'days' => 3],
        '奈良県' => ['zone' => 3, 'days' => 3],

        // Zone 4 - 3 Days (近畿)
        '京都府' => ['zone' => 4, 'days' => 3],
        '大阪府' => ['zone' => 4, 'days' => 3],
        '兵庫県' => ['zone' => 4, 'days' => 3],
        '滋賀県' => ['zone' => 4, 'days' => 3],
        '和歌山県' => ['zone' => 4, 'days' => 3],

        // Zone 5 - 3-4 Days (中国)
        '岡山県' => ['zone' => 5, 'days' => 4],
        '広島県' => ['zone' => 5, 'days' => 4],
        '鳥取県' => ['zone' => 5, 'days' => 4],
        '島根県' => ['zone' => 5, 'days' => 4],
        '山口県' => ['zone' => 5, 'days' => 4],

        // Zone 6 - 3-4 Days (四国)
        '香川県' => ['zone' => 6, 'days' => 4],
        '徳島県' => ['zone' => 6, 'days' => 4],
        '愛媛県' => ['zone' => 6, 'days' => 4],
        '高知県' => ['zone' => 6, 'days' => 4],

        // Zone 7 - 3-4 Days (九州)
        '福岡県' => ['zone' => 7, 'days' => 4],
        '佐賀県' => ['zone' => 7, 'days' => 4],
        '長崎県' => ['zone' => 7, 'days' => 4],
        '熊本県' => ['zone' => 7, 'days' => 4],
        '大分県' => ['zone' => 7, 'days' => 4],
        '宮崎県' => ['zone' => 7, 'days' => 4],
        '鹿児島県' => ['zone' => 7, 'days' => 4],

        // Zone 8 - 3-4 Days (東北・北海道)
        '青森県' => ['zone' => 8, 'days' => 4],
        '岩手県' => ['zone' => 8, 'days' => 4],
        '宮城県' => ['zone' => 8, 'days' => 4],
        '秋田県' => ['zone' => 8, 'days' => 4],
        '山形県' => ['zone' => 8, 'days' => 4],
        '福島県' => ['zone' => 8, 'days' => 4],
        '新潟県' => ['zone' => 8, 'days' => 4],
        '富山県' => ['zone' => 8, 'days' => 4],
        '石川県' => ['zone' => 8, 'days' => 4],
        '福井県' => ['zone' => 8, 'days' => 4],
        '北海道' => ['zone' => 8, 'days' => 4],

        // Special Zone - Okinawa (4-5 days)
        '沖縄県' => ['zone' => 9, 'days' => 5],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sagawa Time Slots
    |--------------------------------------------------------------------------
    |
    | Available delivery time slots offered by Sagawa.
    | These are the 6 standard time windows.
    */
    'time_slots' => [
        '午前中 (9:00-13:00)' => '午前中 (9:00-13:00)',
        '12:00-14:00' => '12:00-14:00',
        '14:00-16:00' => '14:00-16:00',
        '16:00-18:00' => '16:00-18:00',
        '18:00-20:00' => '18:00-20:00',
        '19:00-21:00' => '19:00-21:00',
    ],

    /*
    |--------------------------------------------------------------------------
    | Delivery Date Display Format
    |--------------------------------------------------------------------------
    |
    | Format for displaying delivery dates in the UI
    | 'japanese' = 12月25日 (水)
    | 'iso' = 2024-12-25
    | 'full' = 2024年12月25日 (水)
    */
    'date_format' => 'japanese',

    /*
    |--------------------------------------------------------------------------
    | Selectable Dates Configuration
    |--------------------------------------------------------------------------
    |
    | Number of future dates to show for selection
    | Maximum days to look ahead for available dates
    */
    'selectable_dates' => [
        'count' => 5,
        'max_lookahead_days' => 90,
    ],

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Sagawa API credentials and endpoints (if using live API)
    */
    'api' => [
        'enabled' => false, // Set to true if using live Sagawa API
        'endpoint' => 'https://api.sagawa-exp.co.jp',
        'api_key' => env('SAGAWA_API_KEY', ''),
        'timeout' => 10,
    ],
];
