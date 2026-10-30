<?php

namespace Tests\Unit\Services;

use App\Services\RemoteCapacityRetry;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class RemoteCapacityRetryTest extends TestCase
{
    public function testShortRetryAfterIsRespectedAndRetryCountIsBounded(): void
    {
        $calls = 0;
        $delays = [];
        $error = new ClientException('capacity', new Request('POST', 'https://tenant.example/aggregate'), new Response(429, ['Retry-After' => '1']));
        $result = (new RemoteCapacityRetry())->run(function () use (&$calls, $error) {
            if (++$calls === 1) throw $error;
            return ['status' => 'success'];
        }, 1, 1500, static function ($delay) use (&$delays) { $delays[] = $delay; });
        self::assertSame('success', $result['status']);
        self::assertSame(2, $calls);
        self::assertGreaterThanOrEqual(1000, $delays[0]);
        self::assertLessThanOrEqual(1500, $delays[0]);
    }

    public function testLongRetryAfterIsReturnedWithoutBlockingAWorker(): void
    {
        $error = new ClientException('capacity', new Request('POST', 'https://tenant.example/aggregate'), new Response(429, ['Retry-After' => '60']));
        $this->expectExceptionObject($error);
        (new RemoteCapacityRetry())->run(static function () use ($error) { throw $error; }, 1, 1500,
            static function () { self::fail('Long Retry-After must not sleep'); });
    }
}
