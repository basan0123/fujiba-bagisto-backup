<?php

return [
    /*
    | TikTok Shop API endpoints. JP Open Platform values per the master prompt's
    | §17.3 — confirm the exact host for the live shop before going to production.
    */
    'auth_authorize_url' => env('TIKTOK_AUTH_AUTHORIZE_URL', 'https://auth.tiktok-shops.com/oauth/authorize'),
    'auth_token_url'     => env('TIKTOK_AUTH_TOKEN_URL',     'https://auth.tiktok-shops.com/api/v2/token/get'),
    'auth_refresh_url'   => env('TIKTOK_AUTH_REFRESH_URL',   'https://auth.tiktok-shops.com/api/v2/token/refresh'),

    /*
    | App credentials registered with TikTok. Per-tenant credentials live in
    | tiktok_credentials.tiktok_app_key/_secret; these envs are the defaults
    | used when an admin clicks "Connect" before per-tenant overrides exist.
    */
    'app_key'      => env('TIKTOK_APP_KEY', ''),
    'app_secret'   => env('TIKTOK_APP_SECRET', ''),
    'redirect_uri' => env('TIKTOK_REDIRECT_URI', ''),

    /*
    | Default TikTok category ID used when a product has no explicit mapping.
    | TikTokProductService::mapCategory() throws RuntimeException if empty —
    | product sync is intentionally blocked until this is set in production.
    | See §8 of the master prompt.
    */
    'default_category_id' => env('TIKTOK_DEFAULT_CATEGORY_ID', ''),
];
