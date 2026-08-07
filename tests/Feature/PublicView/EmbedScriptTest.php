<?php

use App\Models\Dashboard;
use App\Models\DashboardPublicView;
use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    config(['app.public_view_skip_ua_check' => true]);
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create(['user_id' => $this->user->id]);
    $this->dashboard = Dashboard::create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'name' => 'Embed Dashboard',
        'is_public' => true,
        'is_default' => false,
    ]);
});

it('GET /pv/{token}/embed.js returns 200 with javascript content-type', function () {
    $pv = DashboardPublicView::create([
        'dashboard_id' => $this->dashboard->id,
        'name' => 'Embed PV',
    ]);

    $this->get(route('public-view.embed', ['token' => $pv->token]))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/javascript; charset=utf-8')
        ->assertSee('iframe.src =')
        ->assertSee('apis-hub-popout')
        ->assertSee('apis-hub-measure');
});

it('GET /pv/{token}/embed.js returns 404 for unknown token', function () {
    $this->get(route('public-view.embed', ['token' => 'invalid-token']))
        ->assertNotFound();
});

it('public view with ?embedded=1 renders without header and notifies resize', function () {
    $pv = DashboardPublicView::create([
        'dashboard_id' => $this->dashboard->id,
        'name' => 'Embedded Mode PV',
    ]);

    $this->get(route('public-view.show', ['token' => $pv->token, 'embedded' => 1]))
        ->assertSuccessful()
        ->assertSee('data-embedded="1"', false);
});
