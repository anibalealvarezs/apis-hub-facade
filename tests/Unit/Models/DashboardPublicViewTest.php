<?php

use App\Models\Dashboard;
use App\Models\DashboardPublicView;
use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create(['user_id' => $this->user->id]);
    $this->dashboard = Dashboard::create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'name' => 'Unit Dashboard',
        'is_public' => true,
        'is_default' => false,
    ]);
});

it('auto-generates token_secret and token on creating', function () {
    $pv = DashboardPublicView::create([
        'dashboard_id' => $this->dashboard->id,
        'name' => 'Test View',
    ]);

    expect($pv->token_secret)->not->toBeEmpty()
        ->and($pv->token)->not->toBeEmpty()
        ->and($pv->token)->not->toStartWith('pending_');
});

it('scopeActive returns only active non-trashed PVs', function () {
    $active = DashboardPublicView::create([
        'dashboard_id' => $this->dashboard->id,
        'name' => 'Active View',
        'is_active' => true,
    ]);

    $inactive = DashboardPublicView::create([
        'dashboard_id' => $this->dashboard->id,
        'name' => 'Inactive View',
        'is_active' => false,
    ]);

    $activePvs = DashboardPublicView::active()->get();

    expect($activePvs->pluck('id'))->toContain($active->id)
        ->and($activePvs->pluck('id'))->not->toContain($inactive->id);
});

it('getPublicUrl and getEmbedUrl return valid route strings containing token', function () {
    $pv = DashboardPublicView::create([
        'dashboard_id' => $this->dashboard->id,
        'name' => 'URL View',
    ]);

    expect($pv->getPublicUrl())->toContain('/pv/' . $pv->token)
        ->and($pv->getEmbedUrl())->toContain('/pv/' . $pv->token . '/embed.js');
});

it('regenerateToken produces a new token', function () {
    $pv = DashboardPublicView::create([
        'dashboard_id' => $this->dashboard->id,
        'name' => 'Regen View',
    ]);

    $oldToken = $pv->token;
    $pv->regenerateToken();

    expect($pv->token)->not->toBe($oldToken);
});
