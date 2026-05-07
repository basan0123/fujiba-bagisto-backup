<?php

return [
    'default_poll_interval' => env('LIVECHAT_POLL_INTERVAL', 5),
    'health_check_interval' => env('LIVECHAT_HEALTH_CHECK_INTERVAL', 300),
    'http_timeout'          => env('LIVECHAT_HTTP_TIMEOUT', 8),
];
