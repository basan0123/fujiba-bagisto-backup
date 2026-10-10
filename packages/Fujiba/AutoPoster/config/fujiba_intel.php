<?php

// Fujiba intelligence-layer ops config (merged by IntelOpsServiceProvider).
return [
    // Not provisioned yet. Null => insights importer reports blocked:no_token.
    'meta_insights_token' => env('FUJIBA_INTEL_META_INSIGHTS_TOKEN'),
    'graph_version'       => env('FUJIBA_INTEL_GRAPH_VERSION', 'v25.0'),
    'insights_lookback_days' => 28,

    'tenant_id'   => env('FUJIBA_INTEL_TENANT', '1bd1181f-fcd5-4238-a12d-a207d6ea4006'),
    'cache_store' => env('FUJIBA_INTEL_CACHE_STORE'), // null = default store
    'done_ttl'    => 30 * 86400,
    'log_channel' => 'stack',
    'log_dir'     => env('FUJIBA_INTEL_LOG_DIR'), // null = storage_path('logs')

    'guard' => [
        'max_load'        => 4.2,
        'slowdown_factor' => 2.0,
        'site_url'        => 'https://kimono.fujiba.co.jp/',
        'probe_ttl'       => 60,
        'release_delay'   => 300,
    ],

    'scheduler_logs' => [
        'autoposter-plan'      => 'autoposter-plan.log',
        'autoposter-dispatch'  => 'autoposter-dispatch.log',
        'autoposter-instagram' => 'autoposter-instagram.log',
    ],
];
