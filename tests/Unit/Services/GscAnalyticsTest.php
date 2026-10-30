<?php

namespace Tests\Unit\Services;

use App\Http\Controllers\Api\GoogleSearchConsoleController;
use App\Services\Analytics\GscAnalyticsCatalog;
use App\Services\Analytics\KpiPayloadBuilder;
use App\Services\RemoteEngineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GscAnalyticsTest extends TestCase
{
    private string $connection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = DB::getDefaultConnection();
        config(['database.connections.gsc_surface_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::setDefaultConnection('gsc_surface_test');
        DB::statement('CREATE TABLE projects (id INTEGER PRIMARY KEY, apis_hub_release_id INTEGER, deleted_at TEXT)');
        DB::statement('CREATE TABLE apis_hub_releases (id INTEGER PRIMARY KEY, version_tag TEXT)');
        DB::table('apis_hub_releases')->insert(['id' => 1, 'version_tag' => 'v1.18.3']);
        DB::table('projects')->insert(['id' => 1, 'apis_hub_release_id' => 1]);
    }

    protected function tearDown(): void
    {
        DB::purge('gsc_surface_test');
        DB::setDefaultConnection($this->connection);
        parent::tearDown();
    }

    public function testCatalogDistinguishesSourceCapabilitiesFromCachedCapabilities(): void
    {
        $tree = GscAnalyticsCatalog::tree();
        $this->assertCount(6, $tree['search_types']);
        $this->assertContains('devices', $tree['search_types']['googleNews']['source_tabs']);
        $this->assertNotContains('devices', $tree['search_types']['googleNews']['tabs']);
        $this->assertNotContains('position', $tree['search_types']['discover']['metrics']);
        $this->assertContains('position', $tree['search_types']['news']['metrics']);
        $this->assertArrayHasKey('daily', $tree['granularities']);
        $this->assertArrayNotHasKey('query', $tree['granularities']);
    }

    public function testRegistryIndexAndScopeSelectorsUseTheCanonicalCatalog(): void
    {
        $this->assertSame(GscAnalyticsCatalog::tree(), \App\Services\Analytics\ChannelCapabilityRegistry::getAnalyticsTree('google_search_console'));
        $definitions = \App\Services\Analytics\ChannelBreakdownRegistry::getBreakdownsForChannel('google_search_console', 'searchAppearance');
        $this->assertSame(GscAnalyticsCatalog::searchTypes(), $definitions['dimensions.search_type']['options']);
        $this->assertArrayHasKey('dimensions.searchAppearance', $definitions);
        $this->assertArrayNotHasKey('query', $definitions);
        $this->assertArrayHasKey('dimensions.search_type', \App\Services\Analytics\ChannelGranularityRegistry::getGranularitiesForChannel('google_search_console'));
    }

    public function testEveryExplorerEndpointForwardsSameSelectedTypeAndPreservesAccountScope(): void
    {
        foreach (['summary', 'chart', 'table'] as $action) {
            $service = $this->createMock(RemoteEngineService::class);
            $service->expects($this->once())->method('aggregateChanneledPool')->willReturnCallback(function ($project, $channel, $entity, $payloads) {
                foreach ($payloads as $payload) {
                    $this->assertSame('image', $payload['filters']['dimensions.search_type']);
                    $this->assertSame(['operator' => 'in', 'value' => ['11', '12']], $payload['filters']['channeledAccount']);
                    $this->assertSame('standard', $payload['filters']['dimensions.searchAppearance']);
                }
                return [];
            });
            $this->app->instance(RemoteEngineService::class, $service);
            $response = (new GoogleSearchConsoleController())->$action($this->request(['searchType' => 'image', 'filters' => ['channeledAccount' => '999']]));
            $this->assertSame(200, $response->getStatusCode());
        }
    }

    public function testFeedReportsOmitPositionAndRejectIncompatibleBreakdownsBeforeTenantCall(): void
    {
        $service = $this->createMock(RemoteEngineService::class);
        $service->expects($this->once())->method('aggregateChanneledPool')->willReturnCallback(function ($p, $c, $e, $payloads) {
            foreach ($payloads as $payload) $this->assertArrayNotHasKey('position', $payload['aggregations']);
            return [];
        });
        $this->app->instance(RemoteEngineService::class, $service);
        $controller = new GoogleSearchConsoleController();
        $this->assertSame(200, $controller->summary($this->request(['searchType' => 'googleNews']))->getStatusCode());
        $this->assertSame(422, $controller->table($this->request(['searchType' => 'googleNews', 'activeTab' => 'devices']))->getStatusCode());
        $this->assertSame(422, $controller->chart($this->request(['searchType' => 'discover', 'metrics' => ['position']]))->getStatusCode());
        $this->assertSame(422, $controller->summary($this->request(['searchType' => 'discover', 'activeFilters' => ['intents' => ['commercial']]]))->getStatusCode());
    }

    public function testWidgetMultiselectFilterReachesChartAndDerivedKpiNodes(): void
    {
        $filter = ['operator' => 'in', 'value' => ['image', 'video']];
        $service = $this->createMock(RemoteEngineService::class);
        $service->expects($this->once())->method('aggregateChanneledPool')->willReturnCallback(function ($p, $c, $e, $payloads) use ($filter) {
            $this->assertSame($filter, $payloads['chart']['filters']['dimensions.search_type']);
            return [];
        });
        $this->app->instance(RemoteEngineService::class, $service);
        $response = (new GoogleSearchConsoleController())->chart($this->request(['filters' => ['dimensions.search_type' => $filter], 'metrics' => ['clicks']]));
        $this->assertSame(200, $response->getStatusCode());
        $ast = KpiPayloadBuilder::buildAstFromState('calculate_regression', [
            'dependent_channel' => 'google_search_console', 'dependent_metric' => 'clicks',
            'filters' => ['dimensions.search_type' => $filter],
            'independent_variables' => [['independent_channel' => 'google_search_console', 'independent_metric' => 'impressions', 'filters' => ['dimensions.search_type' => 'news']]],
        ]);
        $this->assertSame($filter, $ast['left']['filters']['dimensions.search_type']);
        $this->assertSame('news', $ast['right']['filters']['dimensions.search_type']);
    }

    public function testKpiCannotInventPositionsForFeedTraffic(): void
    {
        $this->expectException(ValidationException::class);
        KpiPayloadBuilder::buildAstFromState('calculate_autocorrelation', ['dependent_channel' => 'google_search_console', 'dependent_metric' => 'position', 'filters' => ['dimensions.search_type' => 'discover']]);
    }

    public function testAppearanceScopeStaysSeparateWhenChangingSearchType(): void
    {
        $service = $this->createMock(RemoteEngineService::class);
        $service->expects($this->exactly(2))->method('aggregateChanneledPool')->willReturnCallback(function ($p, $c, $e, $payloads) {
            foreach ($payloads as $payload) {
                $this->assertSame('video', $payload['filters']['dimensions.search_type']);
                $this->assertContains($payload['filters']['dimensions.searchAppearance']['operator'], ['neq', 'not_equal']);
                $this->assertSame('standard', $payload['filters']['dimensions.searchAppearance']['value']);
            }
            return [];
        });
        $this->app->instance(RemoteEngineService::class, $service);
        $controller = new GoogleSearchConsoleController();
        $this->assertSame(200, $controller->chart($this->request(['searchType' => 'video', 'breakdown' => 'dimensions.searchAppearance']))->getStatusCode());
        $this->assertSame(200, $controller->table($this->request(['searchType' => 'video', 'activeTab' => 'appearances']))->getStatusCode());
    }

    public function testOldTenantRejectsNewFiltersButKeepsExistingWebRequestsWorking(): void
    {
        DB::table('apis_hub_releases')->update(['version_tag' => 'v1.18.2']);
        $service = $this->createMock(RemoteEngineService::class);
        $service->expects($this->once())->method('aggregateChanneledPool')->willReturn([]);
        $this->app->instance(RemoteEngineService::class, $service);
        $controller = new GoogleSearchConsoleController();
        $this->assertSame(422, $controller->summary($this->request(['searchType' => 'image']))->getStatusCode());
        $this->assertSame(200, $controller->summary($this->request([]))->getStatusCode());
    }

    public function testMixedStandardAndAppearanceScopesAreRejected(): void
    {
        $this->expectException(ValidationException::class);
        GscAnalyticsCatalog::validateQuery(['web'], ['clicks'], [], ['dimensions.searchAppearance' => ['operator' => 'in', 'value' => ['standard', 'AMP']]]);
    }

    public function testRuntimeKpiAppearanceScopeDoesNotInheritStandardTotals(): void
    {
        $payload = KpiPayloadBuilder::build('calculate_autocorrelation', ['dependent_channel' => 'google_search_console', 'dependent_metric' => 'clicks', 'granularity' => 'daily'], ['granularity' => 'dimensions.searchAppearance']);
        $this->assertSame(['operator' => 'neq', 'value' => 'standard'], $payload['ast']['filters']['dimensions.searchAppearance']);
    }

    private function request(array $extra): Request
    {
        return Request::create('/api/gsc/test', 'POST', array_replace(['tenant' => 1, 'account' => ['11', '12'], 'dateStart' => '2026-10-01', 'dateEnd' => '2026-10-28', 'activeTab' => 'pages'], $extra));
    }
}
