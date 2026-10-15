<?php

use App\Models\Project;
use App\Models\User;
use App\Services\RemoteEngineService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->project = Project::factory()->create([
        'user_id' => $this->user->id,
        'sync_config' => [
            'google_ads' => [
                'enabled' => true,
                'assets' => [
                    'ad_accounts' => [
                        ['id' => 'customers/1234567890', 'enabled' => true],
                    ],
                ],
            ],
        ],
    ]);

    \Illuminate\Support\Facades\DB::table('project_user')->insert([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'asset_access_unrestricted' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

it('fetches summary data via /api/google-ads/summary', function () {
    $mockService = Mockery::mock(RemoteEngineService::class);
    $mockService->shouldReceive('aggregateChanneledPool')
        ->withArgs(function ($project, $channel, $entity, $payloads) {
            return $channel === 'google_ads' && isset($payloads['summary'], $payloads['previous']);
        })
        ->once()
        ->andReturn([
            'summary' => [
                'data' => [
                    [
                        'total_spend' => '150.50',
                        'total_clicks' => '450',
                        'total_impressions' => '9000',
                        'total_conversions' => '15',
                        'total_conversion_value' => '600.00',
                    ],
                ],
            ],
            'previous' => [
                'data' => [
                    [
                        'total_spend' => '100.00',
                        'total_clicks' => '300',
                        'total_impressions' => '6000',
                        'total_conversions' => '10',
                        'total_conversion_value' => '400.00',
                    ],
                ],
            ],
        ]);
    $this->app->instance(RemoteEngineService::class, $mockService);

    $response = $this->postJson('/api/google-ads/summary', [
        'tenant' => $this->project->id,
        'account' => ['1234567890'],
        'dateStart' => '2026-10-01',
        'dateEnd' => '2026-10-14',
    ]);

    $response->assertOk()
        ->assertJsonPath('summary.spend', '150.50')
        ->assertJsonPath('summary.clicks', '450')
        ->assertJsonPath('summary.impressions', '9000')
        ->assertJsonPath('previous.spend', '100.00');
});

it('fetches chart time-series data via /api/google-ads/chart', function () {
    $mockService = Mockery::mock(RemoteEngineService::class);
    $mockService->shouldReceive('aggregateChanneledPool')
        ->withArgs(function ($project, $channel, $entity, $payloads) {
            return $channel === 'google_ads' && isset($payloads['chart']);
        })
        ->once()
        ->andReturn([
            'chart' => [
                'data' => [
                    ['date' => '2026-10-01', 'spend' => '50.00', 'clicks' => 150],
                    ['date' => '2026-10-02', 'spend' => '60.00', 'clicks' => 180],
                ],
            ],
        ]);
    $this->app->instance(RemoteEngineService::class, $mockService);

    $response = $this->postJson('/api/google-ads/chart', [
        'tenant' => $this->project->id,
        'account' => ['1234567890'],
        'dateStart' => '2026-10-01',
        'dateEnd' => '2026-10-02',
    ]);

    $response->assertOk()
        ->assertJsonPath('chart.0.date', '2026-10-01')
        ->assertJsonPath('chart.1.spend', '60.00');
});

it('fetches hierarchical breakdown data via /api/google-ads/table', function () {
    $mockService = Mockery::mock(RemoteEngineService::class);
    $mockService->shouldReceive('aggregateChanneledPool')
        ->withArgs(function ($project, $channel, $entity, $payloads) {
            return $channel === 'google_ads' && isset($payloads['table']) && $payloads['table']['groupBy'] === ['channeledCampaign'];
        })
        ->once()
        ->andReturn([
            'table' => [
                'data' => [
                    ['id' => '1001', 'name' => 'Search Campaign Q4', 'spend' => '120.00', 'clicks' => 300],
                ],
            ],
        ]);
    $this->app->instance(RemoteEngineService::class, $mockService);

    $response = $this->postJson('/api/google-ads/table', [
        'tenant' => $this->project->id,
        'account' => ['1234567890'],
        'dateStart' => '2026-10-01',
        'dateEnd' => '2026-10-14',
        'activeTab' => 'campaigns',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.0.id', '1001')
        ->assertJsonPath('data.0.name', 'Search Campaign Q4');
});
