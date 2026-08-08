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
        'name' => 'Robots Test Dashboard',
        'is_public' => true,
        'is_default' => false,
    ]);
});

it('public view response includes noindex meta tag and X-Robots-Tag header', function () {
    $pv = DashboardPublicView::create([
        'dashboard_id' => $this->dashboard->id,
        'name' => 'Robots PV',
    ]);

    $this->get(route('public-view.show', ['token' => $pv->token]))
        ->assertSuccessful()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});

it('robots.txt contains Disallow: /pv/', function () {
    $content = file_get_contents(public_path('robots.txt'));

    expect($content)->toContain('Disallow: /pv/');
});
