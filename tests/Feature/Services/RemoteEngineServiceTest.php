<?php

use Anibalealvarezs\ApisHubApi\ApisHubApi;
use App\Models\Project;
use App\Models\User;
use App\Services\RemoteEngineService;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

beforeEach(function () {
    /** @var Tests\TestCase $this */
    $user = User::factory()->create();
    $this->project = Project::factory()->create([
        'user_id' => $user->id,
        'subdomain' => 'test-sub',
        'remote_admin_api_key' => 'test-key',
    ]);
});

/**
 * Helper to create a partially mocked service that returns a mocked ApisHubApi client.
 */
function getMockedService(MockHandler $mockHandler): RemoteEngineService|\Mockery\MockInterface
{
    $handlerStack = HandlerStack::create($mockHandler);
    $guzzle = new GuzzleClient(['handler' => $handlerStack]);

    // Create a real SDK client but with a mocked Guzzle handler
    $mockClient = new ApisHubApi(
        baseUrl: 'https://test-sub.anibalalvarez.com',
        apiKey: 'test-key',
        guzzleClient: $guzzle
    );

    // Partially mock the service to override getClient()
    $service = Mockery::mock(RemoteEngineService::class)->makePartial();
    $service->shouldAllowMockingProtectedMethods();
    $service->shouldReceive('getClient')
        ->with(Mockery::type(Project::class))
        ->andReturn($mockClient);

    return $service;
}

test('get status returns success', function () {
    /** @var Tests\TestCase $this */
    $mockHandler = new MockHandler([
        new Response(200, [], json_encode(['success' => true, 'status' => 'success'])),
    ]);

    $mockedService = getMockedService($mockHandler);
    $response = $mockedService->getStatus($this->project);

    expect($response['success'])->toBeTrue();
    expect($response['status'])->toBe('success');
});

test('trigger sync returns success', function () {
    /** @var Tests\TestCase $this */
    $mockHandler = new MockHandler([
        new Response(200, [], json_encode(['success' => true, 'job_id' => '123'])),
    ]);

    $mockedService = getMockedService($mockHandler);
    $response = $mockedService->triggerSync($this->project, 'facebook');

    expect($response['success'])->toBeTrue();
    expect($response['job_id'])->toBe('123');
});

test('stop jobs returns success', function () {
    /** @var Tests\TestCase $this */
    $mockHandler = new MockHandler([
        new Response(200, [], json_encode(['success' => true, 'message' => 'Jobs interrupted'])),
    ]);

    $mockedService = getMockedService($mockHandler);
    $response = $mockedService->stopJobs($this->project);

    expect($response['success'])->toBeTrue();
    expect($response['message'])->toBe('Jobs interrupted');
});

test('update credentials returns success', function () {
    /** @var Tests\TestCase $this */
    $mockHandler = new MockHandler([
        new Response(200, [], json_encode(['success' => true])),
    ]);

    $mockedService = getMockedService($mockHandler);
    $response = $mockedService->updateCredentials($this->project, ['FOO' => 'BAR']);

    expect($response['success'])->toBeTrue();
});

test('service handles exception and logs', function () {
    /** @var Tests\TestCase $this */
    $mockHandler = new MockHandler([
        new Response(500, [], 'Internal Server Error'),
    ]);

    $mockedService = getMockedService($mockHandler);
    $response = $mockedService->getStatus($this->project);

    expect($response['status'])->toBe('error');
    expect($response['message'])->toContain('500');
});

test('get heartbeat returns success', function () {
    /** @var Tests\TestCase $this */
    $mockHandler = new MockHandler([
        new Response(200, [], json_encode(['success' => true, 'health' => 'ok'])),
    ]);

    $mockedService = getMockedService($mockHandler);
    $response = $mockedService->getHeartbeat($this->project);

    expect($response['success'])->toBeTrue();
    expect($response['health'])->toBe('ok');
});
