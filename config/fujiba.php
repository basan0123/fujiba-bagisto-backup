<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Fee defaults for fulfillment switches
    |--------------------------------------------------------------------------
    |
    | Read by Fujiba\OrdersAdmin's fulfillment recalculation.
    | Override per environment via .env if you ever need to.
    |
    */
    'fees' => [
        /*
         * Cash on Delivery (Sagawa Payment Collection) fee, JPY.
         * Tiered by subtotal: tiers are evaluated in order; first match wins.
         * Each tier: ['max' => int|null, 'fee' => int]
         *   max = null means "no upper bound" (the catch-all).
         *
         * Sagawa schedule:
         *   subtotal ≤  9,999 → ¥330
         *   subtotal ≥ 10,000 → ¥440
         */
        'cod_tiers' => [
            ['max' => 9999, 'fee' => 330],
            ['max' => null, 'fee' => 440],
        ],

        /*
         * Legacy flat COD fee — only used if cod_tiers is empty or absent.
         * Kept for backwards compat with other code that reads this key.
         */
        'cod'  => env('FUJIBA_FEE_COD',  330),

        // Cool / freeze shipping surcharge, JPY. Applied per order when any
        // line item's product has the requires_cool_shipping attribute set.
        'cool' => env('FUJIBA_FEE_COOL', 220),
    ],

    /*
    |--------------------------------------------------------------------------
    | Chatpion deep-link base URL
    |--------------------------------------------------------------------------
    |
    | Used by the PaymentConfirmModal to build a "Chatpionで確認 →" link
    | that opens a subscriber's conversation in Chatpion. Empty by default —
    | the link stays hidden in the UI until this is set.
    |
    | Final URL format: {base_url}/subscriber/{messenger_subscriber_id}
    |
    */
    'chatpion' => [
        'base_url' => env('FUJIBA_CHATPION_URL', ''),
    ],

];
