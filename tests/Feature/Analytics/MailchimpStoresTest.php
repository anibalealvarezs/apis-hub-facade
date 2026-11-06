<?php

namespace Tests\Feature\Analytics;

use App\Http\Controllers\Api\MailchimpController;
use App\Models\Project;
use App\Services\Analytics\ChannelAnalyticsCatalog;
use App\Services\Analytics\ChannelGranularityRegistry;
use App\Services\RemoteEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class MailchimpStoresTest extends TestCase
{
    use RefreshDatabase;

    public function testStoreTablePreservesAudienceCurrencyAndDistinctRows(): void
    {
        $project = Project::factory()->create();
        $service = Mockery::mock(RemoteEngineService::class);
        $service->shouldReceive('aggregateChanneledPool')->once()->withArgs(
            function ($tenant, $channel, $entity, $payloads) use ($project): bool {
                $this->assertSame($project->id, $tenant->id);
                $this->assertSame(['orders' => 'orders', 'revenue' => 'revenue'], $payloads['table']['aggregations']);
                $this->assertSame(['dimensions.store_id', 'dimensions.currency'], $payloads['table']['groupBy']);
                $this->assertSame('42', $payloads['table']['filters']['channeledAccount']);
                $this->assertArrayNotHasKey('campaignType', $payloads['table']['filters']);
                return $channel === 'mailchimp' && $entity === 'metric';
            }
        )->andReturn(['table' => ['data' => [
            ['dimensions.store_id' => 'a', 'dimensions.currency' => 'USD', 'orders' => 2, 'revenue' => 12.5],
            ['dimensions.store_id' => 'a', 'dimensions.currency' => 'EUR', 'orders' => 1, 'revenue' => 0],
        ]]]);
        $service->shouldReceive('listCatalogEntities')->once()->withArgs(function ($tenant, $entity, $payload): bool {
            $this->assertSame('stores', $entity);
            $this->assertSame([42], $payload['authorized_scope']['channels']['mailchimp']['account_ids']);
            $this->assertSame(['a'], $payload['filters']['platform_ids']);
            return true;
        })->andReturn(['status' => 'success', 'data' => [[
            'reference' => 'channeled:mailchimp:stores:8', 'platform_id' => 'a', 'name' => 'Example store',
            'attributes' => ['domain' => 'store.example.test', 'source_store_id' => 'source-a'],
        ]]]);
        $this->app->instance(RemoteEngineService::class, $service);
        $request = Request::create('/', 'POST', [
            'tenant' => $project->id, 'account' => '42', 'dateStart' => '2026-11-01', 'dateEnd' => '2026-11-02',
            'dependency' => 'store_conversions', 'filters' => ['channeledAccount' => 'unrequested'],
        ]);

        $data = (new MailchimpController())->table($request)->getData(true);
        $this->assertSame('a:USD', $data['table'][0]['id']);
        $this->assertSame('a:EUR', $data['table'][1]['id']);
        $this->assertEquals(0, $data['table'][1]['revenue']);
        $this->assertSame('Example store', $data['table'][0]['name']);
        $this->assertSame('store.example.test', $data['table'][0]['domain']);
        $this->assertStringContainsString('/catalog/stores/', $data['table'][0]['catalog_url']);
    }

    public function testMixedCurrencyWindowSuppressesRevenueAcrossTheWholeChart(): void
    {
        $project = Project::factory()->create();
        $service = Mockery::mock(RemoteEngineService::class);
        $service->shouldReceive('aggregateChanneledPool')->once()->andReturn([
            'chart' => ['data' => [
                ['daily' => '2026-11-01', 'orders' => 1, 'revenue' => 10],
                ['daily' => '2026-11-02', 'orders' => 1, 'revenue' => 20],
            ]],
            'currency_guard' => ['data' => [['revenue' => null]]],
        ]);
        $this->app->instance(RemoteEngineService::class, $service);
        $request = Request::create('/', 'POST', [
            'tenant' => $project->id, 'account' => '42', 'activeTab' => 'stores',
            'dateStart' => '2026-11-01', 'dateEnd' => '2026-11-02',
        ]);
        $data = (new MailchimpController())->chart($request)->getData(true);
        $this->assertCount(2, $data['chart']);
        $this->assertNull($data['chart'][0]['revenue']);
        $this->assertNull($data['chart'][1]['revenue']);
        $this->assertEquals(1, $data['chart'][0]['orders']);
    }

    public function testWidgetStoreScopeOffersOnlyStoreMetricsAndCompatibleBreakdowns(): void
    {
        $this->assertSame(['orders', 'revenue'], array_keys(ChannelAnalyticsCatalog::mailchimpMetricOptions('daily', 'store_conversions')));
        $this->assertSame([], ChannelAnalyticsCatalog::mailchimpMetricOptions('channeledCampaign', 'store_conversions'));
        $dimensions = ChannelGranularityRegistry::getGranularitiesForChannel('mailchimp', 'store_conversions');
        $this->assertArrayHasKey('dimensions.store_id', $dimensions);
        $this->assertArrayHasKey('dimensions.currency', $dimensions);
        $this->assertArrayNotHasKey('channeledCampaign', $dimensions);
    }
}
