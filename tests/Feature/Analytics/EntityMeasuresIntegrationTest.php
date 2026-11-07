<?php

namespace Tests\Feature\Analytics;

use App\Http\Controllers\Api\DashboardWidgetDataController;
use App\Http\Controllers\Api\DerivedMetricPreviewController;
use App\Http\Controllers\Api\MailchimpController;
use App\Models\CustomKpi;
use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\DerivedMetric;
use App\Models\Project;
use App\Models\User;
use App\Services\RemoteEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class EntityMeasuresIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mailchimp_summary_computes_store_aov_and_attaches_entity_measures(): void
    {
        $project = Project::factory()->create();
        $service = Mockery::mock(RemoteEngineService::class);

        $service->shouldReceive('aggregateChanneledPool')->once()->andReturn([
            'summary' => [
                'status' => 'success',
                'data' => [
                    ['orders' => 5, 'revenue' => 250.0],
                ],
            ],
            'previous' => [
                'status' => 'success',
                'data' => [
                    ['orders' => 2, 'revenue' => 80.0],
                ],
            ],
        ]);

        $service->shouldReceive('aggregateCatalogEntities')
            ->times(3)
            ->andReturnUsing(function ($tenant, $payload) {
                return match ($payload['entity_type']) {
                    'orders' => [
                        'status' => 'success',
                        'data' => [['order_count' => 5, 'revenue' => 250.0, 'aov' => 50.0, 'total_discount' => 15.0]],
                    ],
                    'customers' => [
                        'status' => 'success',
                        'data' => [['customer_count' => 10, 'unique_customer_count' => 8, 'customer_ltv' => 31.25, 'repeat_buyer_rate' => 0.25]],
                    ],
                    'discounts' => [
                        'status' => 'success',
                        'data' => [['discount_count' => 3, 'total_usage' => 9]],
                    ],
                };
            });

        $this->app->instance(RemoteEngineService::class, $service);

        $request = Request::create('/api/mailchimp/summary', 'POST', [
            'tenant' => $project->id,
            'account' => '42',
            'activeTab' => 'stores',
            'dateStart' => '2026-11-01',
            'dateEnd' => '2026-11-05',
        ]);

        $response = (new MailchimpController())->summary($request)->getData(true);

        $this->assertEquals(50.0, $response['summary']['aov']);
        $this->assertEquals(40.0, $response['previous']['aov']);
        $this->assertEquals(5, $response['entity_measures']['orders']['order_count']);
        $this->assertEquals(50.0, $response['entity_measures']['orders']['aov']);
        $this->assertEquals(8, $response['entity_measures']['customers']['unique_customer_count']);
        $this->assertEquals(3, $response['entity_measures']['discounts']['discount_count']);
    }

    public function test_dashboard_widget_direct_entity_measure_source_tile_and_line_chart(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'subdomain' => 'em-test-' . uniqid(),
            'sync_config' => [
                'mailchimp' => [
                    'audiences' => [
                        ['id' => '42', 'enabled' => true],
                    ],
                ],
            ],
        ]);

        $dashboard = Dashboard::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'name' => 'Entity Measure Dashboard',
            'is_public' => true,
            'is_default' => false,
        ]);

        $tileWidget = DashboardWidget::create([
            'dashboard_id' => $dashboard->id,
            'name' => 'Canonical Orders AOV Tile',
            'source_type' => 'entity_measure',
            'widget_type' => 'tile',
            'source_config' => [
                'entity_type' => 'orders',
                'measures' => ['aov'],
                'deduplicate_canonical' => true,
            ],
            'grid_x' => 0,
            'grid_y' => 0,
            'grid_w' => 3,
            'grid_h' => 2,
        ]);

        $lineWidget = DashboardWidget::create([
            'dashboard_id' => $dashboard->id,
            'name' => 'Daily Orders Revenue Chart',
            'source_type' => 'entity_measure',
            'widget_type' => 'line_chart',
            'source_config' => [
                'entity_type' => 'orders',
                'measures' => ['revenue'],
                'deduplicate_canonical' => true,
            ],
            'grid_x' => 3,
            'grid_y' => 0,
            'grid_w' => 6,
            'grid_h' => 4,
        ]);

        $service = Mockery::mock(RemoteEngineService::class);
        $service->shouldReceive('listChanneled')->andReturn(['data' => []]);
        $service->shouldReceive('aggregateCatalogEntities')->andReturnUsing(function ($tenant, $payload) {
            if (($payload['time_bucket'] ?? null) === 'day') {
                return [
                    'status' => 'success',
                    'data' => [
                        ['time_bucket' => '2026-11-01', 'revenue' => 120.0],
                        ['time_bucket' => '2026-11-02', 'revenue' => 180.0],
                    ],
                    'meta' => ['currency_mixed' => false, 'currencies' => ['USD']],
                ];
            }

            return [
                'status' => 'success',
                'data' => [
                    ['aov' => 75.5],
                ],
                'meta' => ['currency_mixed' => false, 'currencies' => ['USD']],
            ];
        });

        $this->app->instance(RemoteEngineService::class, $service);

        $controller = app(DashboardWidgetDataController::class);

        $tileReq = Request::create('/api/dashboard/widget/' . $tileWidget->id . '/data', 'POST', [
            'tenant' => $project->subdomain,
            'controls' => [
                'date_start' => '2026-11-01',
                'date_end' => '2026-11-02',
            ],
        ]);
        $tileRes = $controller->show($tileReq, $tileWidget)->getData(true);

        $this->assertTrue($tileRes['success']);
        $this->assertEquals(75.5, $tileRes['data']['value']);

        $lineReq = Request::create('/api/dashboard/widget/' . $lineWidget->id . '/data', 'POST', [
            'tenant' => $project->subdomain,
            'controls' => [
                'date_start' => '2026-11-01',
                'date_end' => '2026-11-02',
                'granularity' => 'daily',
            ],
        ]);
        $lineRes = $controller->show($lineReq, $lineWidget)->getData(true);

        $this->assertTrue($lineRes['success']);
        $this->assertSame(['2026-11-01', '2026-11-02'], $lineRes['data']['labels']);
        $this->assertEquals([120.0, 180.0], $lineRes['data']['datasets'][0]['data']);
    }

    public function test_derived_metric_and_kpi_combine_entity_measure_and_standard_metric(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'subdomain' => 'em-dm-' . uniqid(),
            'sync_config' => [
                'facebook_marketing' => [
                    'ad_accounts' => [
                        ['id' => 'act_111', 'enabled' => true, 'lost_access' => false],
                    ],
                ],
            ],
        ]);

        $dm = DerivedMetric::create([
            'project_id' => $project->id,
            'name' => 'Blended ROAS (Canonical Orders Revenue / Meta Spend)',
            'format' => 'decimal',
            'output_granularity' => 'daily',
            'is_active' => true,
            'ast' => [
                'type' => 'division',
                'numerator' => 'rev',
                'denominator' => 'spend',
            ],
            'source_series' => [
                [
                    'key' => 'rev',
                    'source_type' => 'entity_measure',
                    'entity_type' => 'orders',
                    'measure' => 'revenue',
                    'deduplicate_canonical' => true,
                ],
                [
                    'key' => 'spend',
                    'source_type' => 'metric',
                    'channel' => 'facebook_marketing',
                    'metric' => 'spend',
                ],
            ],
        ]);

        $dashboard = Dashboard::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'name' => 'Blended ROAS Dashboard',
            'is_public' => true,
            'is_default' => false,
        ]);

        $dmWidget = DashboardWidget::create([
            'dashboard_id' => $dashboard->id,
            'name' => 'Blended ROAS Chart',
            'source_type' => 'derived_metric',
            'derived_metric_id' => $dm->id,
            'widget_type' => 'line_chart',
            'grid_x' => 0,
            'grid_y' => 0,
            'grid_w' => 6,
            'grid_h' => 4,
        ]);

        $kpi = CustomKpi::create([
            'project_id' => $project->id,
            'name' => 'Orders Revenue Anomaly KPI',
            'calculation_type' => 'calculate_anomaly',
            'filters' => [
                '_ui_state' => [
                    'dependent_source_type' => 'entity_measure',
                    'dependent_entity_type' => 'orders',
                    'dependent_measure' => 'revenue',
                    'granularity' => 'daily',
                ],
            ],
            'ast' => [],
            'is_active' => true,
        ]);

        $kpiWidget = DashboardWidget::create([
            'dashboard_id' => $dashboard->id,
            'name' => 'Orders Revenue Anomaly Widget',
            'source_type' => 'kpi',
            'custom_kpi_id' => $kpi->id,
            'widget_type' => 'tile',
            'grid_x' => 6,
            'grid_y' => 0,
            'grid_w' => 3,
            'grid_h' => 2,
        ]);

        $capturedComputePayloads = [];

        $service = Mockery::mock(RemoteEngineService::class);
        $service->shouldReceive('listChanneled')->andReturn(['data' => []]);
        $service->shouldReceive('aggregateChanneledPool')->andReturn([
            'chart' => [
                'status' => 'success',
                'data' => [
                    ['daily' => '2026-11-01', 'spend' => 50.0],
                    ['daily' => '2026-11-02', 'spend' => 60.0],
                ],
            ],
        ]);
        $service->shouldReceive('aggregateCatalogEntities')->andReturn([
            'status' => 'success',
            'data' => [
                ['time_bucket' => '2026-11-01', 'revenue' => 200.0],
                ['time_bucket' => '2026-11-02', 'revenue' => 300.0],
            ],
        ]);
        $service->shouldReceive('computeKpi')->andReturnUsing(function ($tenant, $payload) use (&$capturedComputePayloads) {
            $capturedComputePayloads[] = $payload;
            return [
                'success' => true,
                'data' => [
                    'dates' => ['2026-11-01', '2026-11-02'],
                    'values' => [4.0, 5.0],
                ],
            ];
        });

        $this->app->instance(RemoteEngineService::class, $service);

        $controller = app(DashboardWidgetDataController::class);

        // 1. Test Derived Metric Widget with Entity Measure + Standard Metric
        $dmReq = Request::create('/api/dashboard/widget/' . $dmWidget->id . '/data', 'POST', [
            'tenant' => $project->subdomain,
            'controls' => [
                'date_start' => '2026-11-01',
                'date_end' => '2026-11-02',
                'granularity' => 'daily',
            ],
        ]);
        $dmRes = $controller->show($dmReq, $dmWidget)->getData(true);

        $this->assertTrue($dmRes['success']);
        $this->assertEquals([4.0, 5.0], $dmRes['data']['datasets'][0]['data']);
        $this->assertEquals(['2026-11-01' => 200.0, '2026-11-02' => 300.0], $capturedComputePayloads[0]['series_data']['rev']);
        $this->assertEquals(['2026-11-01' => 50.0, '2026-11-02' => 60.0], $capturedComputePayloads[0]['series_data']['spend']);

        // 2. Test Statistical KPI with Entity Measure
        $kpiReq = Request::create('/api/dashboard/widget/' . $kpiWidget->id . '/data', 'POST', [
            'tenant' => $project->subdomain,
            'controls' => [
                'date_start' => '2026-11-01',
                'date_end' => '2026-11-02',
                'granularity' => 'daily',
            ],
        ]);
        $kpiRes = $controller->show($kpiReq, $kpiWidget)->getData(true);

        $this->assertTrue($kpiRes['success']);
        $this->assertSame('em_orders_revenue', $capturedComputePayloads[1]['ast']['metric']);
        $this->assertEquals(['2026-11-01' => 200.0, '2026-11-02' => 300.0], $capturedComputePayloads[1]['series_data']['em_orders_revenue']);

        // 3. Test DerivedMetricPreviewController with Entity Measure
        $previewReq = Request::create('/api/derived-metrics/preview', 'POST', [
            'project_id' => $project->id,
            'ast' => $dm->ast,
            'source_series' => $dm->source_series,
            'date_start' => '2026-11-01',
            'date_end' => '2026-11-02',
            'granularity' => 'daily',
        ]);
        $previewRes = (new DerivedMetricPreviewController())->preview($previewReq)->getData(true);

        $this->assertTrue($previewRes['success']);
        $this->assertEquals([4.0, 5.0], $previewRes['data']['values']);
    }
}
