<?php

use App\Filament\App\Pages\MailchimpDashboard;
use App\Models\Project;
use App\Models\User;
use App\Services\RemoteEngineService;
use Filament\Facades\Filament;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('populates accounts from remote channeled accounts when available', function () {
    $project = Project::factory()->create([
        'user_id' => $this->user->id,
        'sync_config' => [
            'mailchimp' => [
                'assets' => [
                    'audiences' => [
                        ['id' => 'list_123', 'name' => 'Newsletter', 'enabled' => true],
                    ],
                ],
            ],
        ],
    ]);

    Filament::setTenant($project);

    $mockService = Mockery::mock(RemoteEngineService::class);
    $mockService->shouldReceive('listChanneled')
        ->with($project, 'mailchimp', 'channeled_account', Mockery::any())
        ->once()
        ->andReturn([
            'status' => 'success',
            'data' => [
                [
                    'id' => 10,
                    'platformId' => 'list_123',
                    'name' => 'Newsletter Synced',
                ],
            ],
        ]);
    $this->app->instance(RemoteEngineService::class, $mockService);

    $page = new MailchimpDashboard();
    $page->loadAccounts();

    expect($page->accounts)->toHaveKey(10)
        ->and($page->accounts[10])->toBe('Newsletter Synced')
        ->and($page->selectedAccount)->toBe('10');
});

it('falls back to sync_config audiences when remote engine returns empty', function () {
    $project = Project::factory()->create([
        'user_id' => $this->user->id,
        'sync_config' => [
            'mailchimp' => [
                'assets' => [
                    'audiences' => [
                        ['id' => 'list_abc', 'name' => 'VIP Customers', 'enabled' => true],
                        ['id' => 'list_xyz', 'name' => 'Abandoned Carts', 'enabled' => false],
                    ],
                ],
            ],
        ],
    ]);

    Filament::setTenant($project);

    $mockService = Mockery::mock(RemoteEngineService::class);
    $mockService->shouldReceive('listChanneled')
        ->with($project, 'mailchimp', 'channeled_account', Mockery::any())
        ->once()
        ->andReturn([
            'status' => 'success',
            'data' => [],
        ]);
    $this->app->instance(RemoteEngineService::class, $mockService);

    $page = new MailchimpDashboard();
    $page->loadAccounts();

    expect($page->accounts)->toHaveKey('list_abc')
        ->and($page->accounts['list_abc'])->toBe('VIP Customers')
        ->and($page->selectedAccount)->toBe('list_abc');
});
