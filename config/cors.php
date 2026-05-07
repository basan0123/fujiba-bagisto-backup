<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    // Added graphql* and pos* to the allowed paths
    'paths' => ['api/*', 'graphql*', 'sanctum/csrf-cookie', 'pos/*'],

    'allowed_methods' => ['*'],

    // In production, you can change '*' to ['*.162.43.75.135.nip.io'] 
    // for better security later.
    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // IMPORTANT: Set to true to allow session/cookie sharing across tenants
    'supports_credentials' => true,

];