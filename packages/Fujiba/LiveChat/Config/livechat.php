<?php

return [
    'default_poll_interval' => env('LIVECHAT_POLL_INTERVAL', 5),
    'health_check_interval' => env('LIVECHAT_HEALTH_CHECK_INTERVAL', 300),
    'http_timeout'          => env('LIVECHAT_HTTP_TIMEOUT', 8),

    /*
    |--------------------------------------------------------------------------
    | Auto-flow expected channels (config-drift guard)
    |--------------------------------------------------------------------------
    | Channel codes that operators expect to have the new-customer auto-flow
    | ENABLED with a non-empty postback_id. The dispatcher
    | (fujiba:dispatch-auto-flow) checks this list every run and Log::warning()s
    | if any listed channel is missing, disabled, or has no postback configured.
    |
    | This exists because a stale pre-feature DB dump restored over the
    | 2026-05 cutover silently reset livechat_settings.auto_flow_enabled to 0,
    | and the dispatcher just logged "No matching channels found" and exited
    | SUCCESS — no signal that a live feature had gone dark. See
    | feedback_livechat_settings_reset_cutover_restore. Comma-separated env
    | override, e.g. LIVECHAT_AUTOFLOW_EXPECTED=kimono,myanmar-food.
    |
    | Leave empty ([]) to disable the guard.
    */
    'auto_flow_expected_channels' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('LIVECHAT_AUTOFLOW_EXPECTED', 'kimono'))
    ))),
];
