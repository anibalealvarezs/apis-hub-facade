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
        'name' => 'Cascade Dashboard',
        'is_public' => true,
        'is_default' => false,
    ]);
});

it('soft deleting a dashboard soft deletes its public views', function () {
    $pv = DashboardPublicView::create([
        'dashboard_id' => $this->dashboard->id,
        'name' => 'Cascade PV',
    ]);

    $this->dashboard->delete();

    expect(DashboardPublicView::find($pv->id))->toBeNull()
        ->and(DashboardPublicView::withTrashed()->find($pv->id))->not->toBeNull();
});

it('restoring a dashboard restores its public views', function () {
    $pv = DashboardPublicView::create([
        'dashboard_id' => $this->dashboard->id,
        'name' => 'Restore PV',
    ]);

    $this->dashboard->delete();
    $this->dashboard->restore();

    expect(DashboardPublicView::find($pv->id))->not->toBeNull();
});

it('force deleting a dashboard hard deletes its public views', function () {
    $pv = DashboardPublicView::create([
        'dashboard_id' => $this->dashboard->id,
        'name' => 'Force Delete PV',
    ]);

    $this->dashboard->forceDelete();

    expect(DashboardPublicView::withTrashed()->find($pv->id))->toBeNull();
});
