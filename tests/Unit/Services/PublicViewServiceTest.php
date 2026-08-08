<?php

use App\Models\Dashboard;
use App\Models\DashboardPublicView;
use App\Models\Project;
use App\Models\User;
use App\Services\PublicViewService;

beforeEach(function () {
    $this->service = app(PublicViewService::class);
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create(['user_id' => $this->user->id]);
    $this->dashboard = Dashboard::create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'name' => 'Service Dashboard',
        'is_public' => true,
        'is_default' => false,
    ]);
});

it('generateToken and verifyToken work correctly for valid tokens', function () {
    $pv = DashboardPublicView::create([
        'dashboard_id' => $this->dashboard->id,
        'name' => 'Service Test View',
    ]);

    $verified = $this->service->verifyToken($pv->token);

    expect($verified)->not->toBeNull()
        ->and($verified->id)->toBe($pv->id);
});

it('verifyToken returns null for invalid or tampered tokens', function () {
    expect($this->service->verifyToken('invalid.token.string'))->toBeNull()
        ->and($this->service->verifyToken('abc.def.ghi'))->toBeNull();
});

it('getEmbedJs returns javascript code with embed url', function () {
    $pv = DashboardPublicView::create([
        'dashboard_id' => $this->dashboard->id,
        'name' => 'JS Embed View',
    ]);

    $js = $this->service->getEmbedJs($pv);

    expect($js)->toContain('iframeSrc')
        ->and($js)->toContain($pv->token);
});
