<?php

namespace Tests\Unit\Services;

use App\Services\RemoteRequestCoordinator;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use PHPUnit\Framework\TestCase;

class RemoteRequestCoordinatorTest extends TestCase
{
    private function coordinator(): RemoteRequestCoordinator
    {
        return new RemoteRequestCoordinator(new Repository(new ArrayStore()), [
            'max_concurrent' => 1, 'lease_seconds' => 30, 'queue_wait_ms' => 0,
            'coalesce_wait_ms' => 0, 'coalesce_ttl_seconds' => 2,
        ]);
    }

    public function testIdenticalQueriesShareResultsOnlyWithinTheirPermissionScope(): void
    {
        $coordinator = $this->coordinator();
        $calls = 0;
        $fetch = static function () use (&$calls) { $calls++; return ['status' => 'success', 'data' => ['value' => 12]]; };
        $first = $coordinator->run(1, 'actor-one', ['b' => 2, 'a' => 1], $fetch, 10);
        self::assertSame($first, $coordinator->run(1, 'actor-one', ['a' => 1, 'b' => 2], $fetch, 10));
        self::assertSame(1, $calls);
        $coordinator->run(1, 'actor-two', ['a' => 1, 'b' => 2], $fetch, 10);
        self::assertSame(2, $calls);
    }

    public function testConcurrentQueriesAreBoundedPerTenantAndLocksAreReleased(): void
    {
        $coordinator = $this->coordinator();
        $blocked = $otherTenant = $duplicate = null;
        $coordinator->run(1, 'actor', ['id' => 1], function () use ($coordinator, &$blocked, &$otherTenant, &$duplicate) {
            $blocked = $coordinator->run(1, 'other-actor', ['id' => 2], static fn () => ['unexpected' => true], 10);
            $duplicate = $coordinator->run(1, 'actor', ['id' => 1], static fn () => ['unexpected' => true], 10);
            $otherTenant = $coordinator->run(2, 'actor', ['id' => 1], static fn () => ['status' => 'success'], 10);
            return ['status' => 'success'];
        }, 10);
        self::assertSame(429, $blocked['http_status']);
        self::assertSame('duplicate_pending', $duplicate['reason']);
        self::assertSame('success', $otherTenant['status']);
        self::assertSame('success', $coordinator->run(1, 'actor', ['id' => 3], static fn () => ['status' => 'success'], 10)['status']);
    }

    public function testErrorsAreNotCachedAndCallbackFailuresReleaseTheirSlots(): void
    {
        $coordinator = $this->coordinator();
        $coordinator->run(1, 'actor', [], static fn () => ['status' => 'error'], 10);
        try { $coordinator->run(1, 'actor', [], static function () { throw new \RuntimeException('failed'); }, 10); }
        catch (\RuntimeException $error) { self::assertSame('failed', $error->getMessage()); }
        self::assertSame('success', $coordinator->run(1, 'actor', [], static fn () => ['status' => 'success'], 10)['status']);
    }
}
