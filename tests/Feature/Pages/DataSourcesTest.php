<?php

use App\Filament\App\Pages\DataSources;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create(['user_id' => $this->user->id]);
    Permission::firstOrCreate(['name' => 'view_settings', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'manage_channels', 'guard_name' => 'web']);
    $this->user->givePermissionTo(['view_settings', 'manage_channels']);
    $this->actingAs($this->user);
    Filament::setTenant($this->project);
});

test('data sources page renders successfully without route errors', function () {
    Livewire::test(DataSources::class)
        ->assertSuccessful();
});

test('getAssetBillingReferenceUrl returns valid url with anchor', function () {
    $page = new DataSources();
    $url = $page->getAssetBillingReferenceUrl('Annual vs. Monthly Subscriptions');
    expect($url)->toContain('account-projects-billing/asset-billing-reference#annual-vs-monthly-subscriptions');
});

test('gsc additional search types are rendered and merged into payload with web', function () {
    $release = \App\Models\ApisHubRelease::firstOrCreate(['version_tag' => 'v1.18.3'], [
        'is_active' => true,
        'is_default' => true,
        'supported_channels' => ['google_search_console'],
    ]);
    $release->update(['supported_channels' => ['google_search_console']]);

    $channelProfile = \App\Models\ChannelProfile::create([
        'user_id' => $this->user->id,
        'provider' => 'google',
        'provider_account_id' => 'google_acc_123',
        'name' => 'Test Google Profile',
        'access_token' => 'dummy_token',
        'authorized_channels' => ['google_search_console'],
    ]);

    $this->project->update([
        'apis_hub_release_id' => $release->id,
        'google_profile_id' => $channelProfile->id,
    ]);
    $this->project->refresh();
    Filament::setTenant($this->project);

    Livewire::withQueryParams(['activeChannel' => 'google_search_console'])
        ->test(DataSources::class)
        ->set('activeChannel', 'google_search_console')
        ->assertSeeHtml('gsc-search-types-warning')
        ->assertSee(__('Additional Search Types'));

    $service = app(\App\Services\ConfigPayloadService::class);
    
    // Default when no additional types selected: only web
    $payloadData = $service->buildPayload($this->project, $release, 'google_search_console', [
        'assets' => ['sites' => []],
        'additional_search_types' => [],
    ]);
    expect($payloadData['payload']['search_types'])->toBe(['web']);

    // When additional types selected: web is prepended and preserved
    $payloadDataWithTypes = $service->buildPayload($this->project, $release, 'google_search_console', [
        'assets' => ['sites' => []],
        'additional_search_types' => ['image', 'discover'],
    ]);
    expect($payloadDataWithTypes['payload']['search_types'])->toBe(['web', 'image', 'discover']);
});

test('project getGscConfiguredSearchTypes returns configured types or defaults to web', function () {
    expect($this->project->getGscConfiguredSearchTypes())->toBe(['web']);

    $this->project->update([
        'sync_config' => [
            'google_search_console' => [
                'search_types' => ['web', 'image', 'video'],
            ],
        ],
    ]);
    expect($this->project->getGscConfiguredSearchTypes())->toBe(['web', 'image', 'video']);

    $options = \App\Services\Analytics\GscAnalyticsCatalog::searchTypes($this->project->getGscConfiguredSearchTypes());
    expect(array_keys($options))->toBe(['web', 'image', 'video']);
});

test('adding a new gsc search type triggers nuclear resync without wipe', function () {
    $remoteMock = Mockery::mock(\App\Services\RemoteEngineService::class);
    $remoteMock->shouldReceive('updateCredentials')->andReturn(['status' => 'success', 'config' => []]);
    // Expect triggerHistoricalResync with wipeMetrics = false (nuclear resync WITHOUT wipe)
    $remoteMock->shouldReceive('triggerHistoricalResync')
        ->once()
        ->with(Mockery::on(fn ($p) => $p->id === $this->project->id), ['google_search_console'], false)
        ->andReturn(['status' => 'success']);

    app()->instance(\App\Services\RemoteEngineService::class, $remoteMock);

    $this->project->update([
        'last_deployed_at' => now(),
        'sync_config' => [
            'google_search_console' => [
                'enabled' => true,
                'search_types' => ['web'],
                'sites' => [],
            ],
        ],
    ]);
    Filament::setTenant($this->project);

    Livewire::withQueryParams(['activeChannel' => 'google_search_console'])
        ->test(DataSources::class)
        ->set('activeChannel', 'google_search_console')
        ->set('data.google_search_console.additional_search_types', ['image'])
        ->call('save')
        ->assertNotified(__('Search Types Updated'));
});
