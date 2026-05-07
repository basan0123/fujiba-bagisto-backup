<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Special Shipping Zones
    |--------------------------------------------------------------------------
    |
    | Prefecture codes that have different shipping thresholds/surcharges.
    | Key = JIS prefecture code (01 = Hokkaido, 47 = Okinawa).
    |
    | Rules (per zone):
    |   free_threshold  — subtotal >= this → free shipping
    |   surcharge       — subtotal <  free_threshold → flat surcharge
    |   label           — breakdown label when surcharge applies
    |   free_label      — breakdown label when free shipping applies
    */

    'special' => [
        // 北海道・沖縄県 now use admin shipping zones (shipping_zones table)
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Flat Rate (normal zones, fallback)
    |--------------------------------------------------------------------------
    */
    'default_flat_rate' => (float) env('SHIPPING_DEFAULT_FLAT', 650),
];
