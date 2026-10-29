<?php

namespace App\Services;

use GuzzleHttp\Exception\RequestException;

final class RemoteCapacityRetry
{
    public static function retryAfterMilliseconds(string $header, int $fallback = 1000): int
    {
        if ($header === '') return $fallback;
        if (ctype_digit($header)) return max(0, (int)$header * 1000);
        $date = strtotime($header);
        return $date === false ? $fallback : max(0, ($date - time()) * 1000);
    }

    public function run(callable $callback, int $retries = 1, int $maxDelayMs = 1500, ?callable $sleep = null): mixed
    {
        $sleep ??= static fn (int $ms) => usleep($ms * 1000);
        for ($attempt = 0; ; $attempt++) {
            try { return $callback(); }
            catch (RequestException $error) {
                $response = $error->getResponse();
                if (!$response || !in_array($response->getStatusCode(), [429, 503], true) || $attempt >= min(2, max(0, $retries))) throw $error;
                $delay = self::retryAfterMilliseconds($response->getHeaderLine('Retry-After'), 250 * (2 ** $attempt));
                // Long Retry-After values are surfaced to the caller, not slept
                // through while tying up a facade HTTP worker.
                if ($delay > $maxDelayMs) throw $error;
                $sleep(min($maxDelayMs, $delay + random_int(0, 150)));
            }
        }
    }
}
