<?php

namespace Tests\Unit\Services;

use App\Http\Middleware\RemoteRequestBackpressure;
use Illuminate\Http\Request;
use Tests\TestCase;

class RemoteRequestBackpressureTest extends TestCase
{
    public function testCapacityFailureCannotBeReturnedAsSuccessfulEmptyMetrics(): void
    {
        $request = Request::create('/api/fbm/summary', 'POST');
        $request->attributes->set('remote_request_backpressure', ['http_status' => 429, 'retry_after' => 2,
            'status' => 'error', 'message' => 'Tenant busy']);
        $response = (new RemoteRequestBackpressure())->handle($request, static fn () => response()->json(['summary' => []]));
        self::assertSame(429, $response->getStatusCode());
        self::assertSame('2', $response->headers->get('Retry-After'));
        self::assertSame('Tenant busy', json_decode($response->getContent(), true)['error']);
    }
}
