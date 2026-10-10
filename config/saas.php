<?php

/*
|--------------------------------------------------------------------------
| snssale.com SaaS — plan catalog & entitlements
|--------------------------------------------------------------------------
|
| Single source of truth for what each subscription plan allows. Prices stay
| null (page shows "Contact us") until the owner confirms them — nothing bills
| from this file yet. Limits use null for "unlimited". Read via App\Saas\Entitlements.
|
*/

return [

    'currency' => 'JPY',

    // true once the landing page is live at "/": drops robots noindex for
    // reviewed locales (unreviewed_locales stay noindex).
    'public' => true,

    // Locales the marketing site renders. Add 'ne' only once fully translated.
    'locales' => ['ja', 'en', 'my'],

    // Locales still awaiting native review: rendered with robots noindex.
    'unreviewed_locales' => ['my'],
    'default_locale' => 'ja',

    'trial_days' => 14,
    'grace_days' => 7, // access kept after a failed renewal before suspension

    'plans' => [
        'starter' => [
            'monthly_price' => null, // set JPY when owner confirms; null shows "Contact us"
            'limits' => [
                'products'        => 100,
                'staff'           => 2,
                'orders_per_month'=> 300,
                'pos_devices'     => 1,
                'social_accounts' => 1,
                'ai_credits'      => 200,
                'storage_mb'      => 1024,
            ],
            'features' => ['storefront', 'pos', 'komoju_checkout'],
        ],
        'business' => [
            'monthly_price' => null, // set JPY when owner confirms; null shows "Contact us"
            'featured' => true,
            'limits' => [
                'products'        => 2000,
                'staff'           => 10,
                'orders_per_month'=> 3000,
                'pos_devices'     => 5,
                'social_accounts' => 5,
                'ai_credits'      => 2000,
                'storage_mb'      => 10240,
            ],
            'features' => ['storefront', 'pos', 'komoju_checkout', 'sagawa', 'social', 'ai_assistant', 'custom_domain'],
        ],
        'pro' => [
            'monthly_price' => null, // set JPY when owner confirms; null shows "Contact us"
            'limits' => [
                'products'        => null,
                'staff'           => 50,
                'orders_per_month'=> null,
                'pos_devices'     => 20,
                'social_accounts' => 20,
                'ai_credits'      => 10000,
                'storage_mb'      => 51200,
            ],
            'features' => ['storefront', 'pos', 'komoju_checkout', 'sagawa', 'social', 'ai_assistant', 'custom_domain', 'autoposter', 'priority_support'],
        ],
    ],
];
