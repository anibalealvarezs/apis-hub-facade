<?php

use App\Models\AssetBillingLock;
use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create(['user_id' => $this->user->id]);
});

test('getPendingConfirmedAssetsCount returns 0 if project has never been deployed or synced', function () {
    AssetBillingLock::create([
        'user_id' => $this->user->id,
        'project_id' => $this->project->id,
        'channel' => 'facebook_marketing',
        'asset_identifier' => 'act_123',
        'status' => 'locked',
        'locked_at' => now(),
    ]);

    expect($this->project->getPendingConfirmedAssetsCount())->toBe(0);
});

test('getPendingConfirmedAssetsCount returns count of assets locked AFTER latest sync or deploy', function () {
    $past = now()->subHours(2);
    $this->project->update([
        'last_deployed_at' => $past,
    ]);

    // Pre-deployment lock (should not count)
    AssetBillingLock::create([
        'user_id' => $this->user->id,
        'project_id' => $this->project->id,
        'channel' => 'facebook_marketing',
        'asset_identifier' => 'act_old',
        'status' => 'locked',
        'locked_at' => $past->copy()->subHour(),
    ]);

    // Post-deployment lock (should count)
    AssetBillingLock::create([
        'user_id' => $this->user->id,
        'project_id' => $this->project->id,
        'channel' => 'facebook_marketing',
        'asset_identifier' => 'act_new',
        'status' => 'locked',
        'locked_at' => now(),
    ]);

    expect($this->project->getPendingConfirmedAssetsCount())->toBe(1);
});

test('triggering sync updates last_sync_started_at and clears pending assets count', function () {
    $past = now()->subHours(2);
    $this->project->update([
        'last_deployed_at' => $past,
    ]);

    AssetBillingLock::create([
        'user_id' => $this->user->id,
        'project_id' => $this->project->id,
        'channel' => 'facebook_marketing',
        'asset_identifier' => 'act_123',
        'status' => 'locked',
        'locked_at' => now()->subMinute(),
    ]);

    expect($this->project->getPendingConfirmedAssetsCount())->toBe(1);

    // Deploy update executed
    $this->project->update([
        'last_sync_started_at' => now(),
    ]);

    expect($this->project->fresh()->getPendingConfirmedAssetsCount())->toBe(0);
});
