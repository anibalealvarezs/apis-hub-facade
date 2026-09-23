<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Signals that a remote engine request failed transiently (e.g. Cloudflare interruption,
 * gateway timeout) and the same widget data request may succeed if retried shortly.
 */
class RetryableEngineException extends RuntimeException
{
    public function __construct(string $message = '')
    {
        parent::__construct($message);
    }
}