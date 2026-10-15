<?php

use App\Filament\App\Pages\GoogleAdsDashboard;
use App\Models\Project;
use App\Models\User;
use App\Services\RemoteEngineService;
use Filament\Facades\Filament;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('populates google ads accounts from remote channeled accounts', function () {
    $project = Project::factory()->create([
        'user_id' => $this->user->id,
        'sync_config' => [
            'google_ads' => [
                'enabled' => true,
                'assets' => [
                    'ad_accounts' => [
                        ['id' => 'customers/1234567890', 'name' => 'Main Ads Account', 'enabled' => true],
                        ['id' => 'customers/9876543210', 'name' => 'Disabled Ads Account', 'enabled' => false],
                    ],
                ],
            ],
        ],
    ]);

    Filament::setTenant($project);

    $mockService = Mockery::mock(RemoteEngineService::class);
    $mockService->shouldReceive('listChanneled')
        ->with($project, 'google_ads', 'channeled_account', Mockery::any())
        ->once()
        ->andReturn([
            'status' => 'success',
            'data' => [
                [
                    'id' => 101,
                    'platformId' => '1234567890',
                    'name' => 'Main Ads Account Synced',
                ],
            ],
        ]);
    $this->app->instance(RemoteEngineService::class, $mockService);

    $page = new GoogleAdsDashboard();
    $page->loadAccounts();

    expect($page->accounts)->toHaveKey('101')
        ->and($page->accounts['101'])->toBe('Main Ads Account Synced')
        ->and($page->selectedAccounts)->toBe(['101']);
});
