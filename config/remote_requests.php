<?php

return [
    'store' => env('REMOTE_REQUEST_CACHE_STORE', 'redis'),
    'max_concurrent' => (int) env('REMOTE_MAX_CONCURRENT_REQUESTS', 3),
    'lease_seconds' => (int) env('REMOTE_REQUEST_LEASE_SECONDS', 120),
    'queue_wait_ms' => (int) env('REMOTE_REQUEST_QUEUE_WAIT_MS', 750),
    'coalesce_wait_ms' => (int) env('REMOTE_REQUEST_COALESCE_WAIT_MS', 1500),
    'coalesce_ttl_seconds' => (int) env('REMOTE_REQUEST_COALESCE_TTL_SECONDS', 2),
    'max_retries' => (int) env('REMOTE_REQUEST_MAX_RETRIES', 1),
    'max_retry_delay_ms' => (int) env('REMOTE_REQUEST_MAX_RETRY_DELAY_MS', 1500),
];
