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
        'name' => 'Feature Access Dashboard',
        'is_public' => true,
        'is_default' => false,
    ]);
});

it('returns 200 for active public view with valid token', function () {
    $pv = DashboardPublicView::create([
        'dashboard_id' => $this->dashboard->id,
        'name' => 'Feature View',
    ]);

    $this->get(route('public-view.show', ['token' => $pv->token]))
        ->assertSuccessful()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee('Feature Access Dashboard');
});

it('returns 404 for unknown token', function () {
    $this->get(route('public-view.show', ['token' => 'invalid-token-value']))
        ->assertNotFound();
});

it('returns 404 for inactive public view', function () {
    $pv = DashboardPublicView::create([
        'dashboard_id' => $this->dashboard->id,
        'name' => 'Inactive View',
        'is_active' => false,
    ]);

    $this->get(route('public-view.show', ['token' => $pv->token]))
        ->assertNotFound();
});

it('returns 404 when parent dashboard is soft-deleted', function () {
    $pv = DashboardPublicView::create([
        'dashboard_id' => $this->dashboard->id,
        'name' => 'Soft Deleted Parent View',
    ]);

    $this->dashboard->delete();

    $this->get(route('public-view.show', ['token' => $pv->token]))
        ->assertNotFound();
});

it('returns 404 when dashboard is_public is false', function () {
    $pv = DashboardPublicView::create([
        'dashboard_id' => $this->dashboard->id,
        'name' => 'Private Parent View',
    ]);

    $this->dashboard->update(['is_public' => false]);

    $this->get(route('public-view.show', ['token' => $pv->token]))
        ->assertNotFound();
});

it('returns 403 for denied bot user-agent when UA check is enabled', function () {
    config(['app.public_view_skip_ua_check' => false]);

    $pv = DashboardPublicView::create([
        'dashboard_id' => $this->dashboard->id,
        'name' => 'Bot Test View',
    ]);

    $this->withHeaders(['User-Agent' => 'Googlebot/2.1 (+http://www.google.com/bot.html)'])
        ->get(route('public-view.show', ['token' => $pv->token]))
        ->assertStatus(403);
});
