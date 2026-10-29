<?php

namespace App\Services;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

/** Cross-process backpressure and brief permission-scoped request coalescing. */
class RemoteRequestCoordinator
{
    public function __construct(private ?Repository $cache = null, private ?array $settings = null) {}

    public function run(int $projectId, string $actor, array $request, callable $callback, int $timeout): array
    {
        $settings = $this->settings ?? config('remote_requests');
        $requestLock = $slot = null;
        $callbackFailure = null;
        try {
            $cache = $this->cache ?? Cache::store($settings['store']);
            if (!$this->cache && $cache->getStore() instanceof \Illuminate\Cache\ArrayStore && !app()->environment('testing')) {
                throw new \RuntimeException('Remote requests require a shared cache store');
            }
            $key = 'remote_requests:'.$projectId.':'.hash('sha256', $actor.':'.json_encode(self::canonicalize($request), JSON_THROW_ON_ERROR));
            $cached = $cache->get($key.':result');
            if (is_array($cached)) return $cached;
            // Every outbound HTTP call has a timeout. Leave cleanup margin so a
            // live request cannot lose its lease while the connection is active.
            $lease = max($timeout + 30, (int)$settings['lease_seconds']);
            $requestLock = $cache->lock($key.':lock', $lease);
            if (!$requestLock->get()) {
                $requestLock = null;
                $deadline = microtime(true) + max(0, (int)$settings['coalesce_wait_ms']) / 1000;
                do {
                    $cached = $cache->get($key.':result');
                    if (is_array($cached)) return $cached;
                    if (microtime(true) >= $deadline) return self::busy('duplicate_pending');
                    usleep(25000);
                } while (true);
            }
            // Another caller may have completed between the lookup and lock.
            $cached = $cache->get($key.':result');
            if (is_array($cached)) return $cached;
            $deadline = microtime(true) + max(0, (int)$settings['queue_wait_ms']) / 1000;
            do {
                for ($index = 0; $index < max(1, (int)$settings['max_concurrent']); $index++) {
                    $candidate = $cache->lock('remote_requests:'.$projectId.':slot:'.$index, $lease);
                    if ($candidate->get()) { $slot = $candidate; break; }
                }
                if ($slot) break;
                if (microtime(true) >= $deadline) return self::busy('tenant_concurrency');
                usleep(25000);
            } while (true);
            try { $result = $callback(); }
            catch (\Throwable $error) { $callbackFailure = $error; throw $error; }
            if (is_array($result) && ($result['status'] ?? null) !== 'error' && empty($result['error'])) {
                $cache->put($key.':result', $result, max(1, (int)$settings['coalesce_ttl_seconds']));
            }
            return $result;
        } catch (\Throwable $error) {
            if ($callbackFailure !== null) throw $callbackFailure;
            return ['status' => 'error', 'http_status' => 503, 'error_code' => 'request_guard_unavailable',
                'retry_after' => 2, 'retryable' => true, 'message' => 'Request capacity checks are temporarily unavailable. Please retry later.'];
        } finally {
            try { $slot?->release(); } catch (\Throwable) { /* Lease expires if the shared cache is unavailable. */ }
            try { $requestLock?->release(); } catch (\Throwable) { /* Owner-bound release; never force-delete another request's lock. */ }
        }
    }

    private static function busy(string $reason): array
    {
        return ['status' => 'error', 'http_status' => 429, 'error_code' => 'remote_capacity_exhausted',
            'reason' => $reason, 'retry_after' => 1, 'retryable' => true,
            'message' => 'This tenant is processing other requests. Please retry shortly.'];
    }

    public static function canonicalize(mixed $value): mixed
    {
        if (is_object($value)) $value = (array)$value;
        if (!is_array($value)) return $value;
        if (!array_is_list($value)) ksort($value);
        foreach ($value as &$item) $item = self::canonicalize($item);
        return $value;
    }
}
