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
    $this->user->givePermissionTo('view_settings');
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
