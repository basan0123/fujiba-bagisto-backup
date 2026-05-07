<?php

return [
    'graph_api_version' => env('FB_GRAPH_API_VERSION', 'v19.0'),
    'graph_base_url'    => env('FB_GRAPH_BASE_URL', 'https://graph.facebook.com'),

    /*
     * Items per items_batch request. Facebook's hard limit is 5000 per call
     * but practical sweet spot is 200–500 for retry-friendly chunks.
     */
    'batch_size' => (int) env('FB_CATALOG_BATCH_SIZE', 200),

    /*
     * HTTP timeouts (seconds). Graph API normally responds in under 5s but
     * batch calls with 200+ items can take 30s+.
     */
    'http_timeout' => (int) env('FB_CATALOG_HTTP_TIMEOUT', 60),
    'http_retries' => (int) env('FB_CATALOG_HTTP_RETRIES', 3),

    /*
     * Job queue. Keep on default queue unless throughput becomes a problem.
     */
    'queue' => env('FB_CATALOG_QUEUE', 'default'),
];
