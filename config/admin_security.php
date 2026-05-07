<?php

return [
    'observation_mode' => true,

    'emergency_path' => env('ADMIN_SECURITY_EMERGENCY_PATH', 'sa-emergency-c4f1a8b2'),

    'always_whitelist' => [
        'admin/tiktok/callback',
        'admin/tiktok/callback/*',
        'admin/security/settings',
        'admin/security/settings/*',
    ],

    'log_channel' => 'admin_security',

    'tenant_lookup_cache_seconds' => 30,

    'admin_path_prefix' => 'admin',
];
