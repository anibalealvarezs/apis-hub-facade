<?php

namespace Tests\Unit\Services;

use Anibalealvarezs\ApisHubApi\ApisHubApi;
use App\Models\Project;
use App\Services\RemoteEngineService;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class EntityAttributeCompatibilityTest extends TestCase
{
    public function testOlderTenantGetsAnActionableMessageWithoutTheRawRequestUrl(): void
    {
        $client = $this->createMock(ApisHubApi::class);
        $client->method('getEntityAttributes')->willThrowException(new ClientException(
            'Raw upstream exception', new Request('POST', 'https://tenant.example/google_search_console/metric/attributes'), new Response(405)
        ));
        $service = new class($client) extends RemoteEngineService {
            public function __construct(private ApisHubApi $client) {}
            public function execute(Project $project, callable $callback, int $timeout = 15)
            {
                return $callback($this->client);
            }
        };
        $result = $service->getEntityAttributes(new Project(), 'google_search_console');
        self::assertSame('error', $result['status']);
        self::assertSame('entity_attributes_unavailable', $result['error_code']);
        self::assertStringContainsString('Update the tenant', $result['message']);
        self::assertStringNotContainsString('https://', $result['message']);
    }
}
