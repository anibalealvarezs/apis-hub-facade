<?php

use App\Filament\App\Pages\ProjectSettings;
use App\Jobs\RestoreProjectDomainJob;
use App\Jobs\SuspendProjectDomainJob;
use App\Livewire\ArchivedProjectsTable;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->project = Project::factory()->create([
        'user_id' => $this->owner->id,
        'subdomain' => 'delete-test-project',
        'is_active' => true,
    ]);

    $this->project->users()->attach($this->owner->id);
});

it('allows the owner to soft delete the project and dispatches SuspendProjectDomainJob', function () {
    Queue::fake();
    actingAs($this->owner);

    Livewire::test(ProjectSettings::class, ['tenant' => $this->project->subdomain])
        ->callAction('delete', data: [
            'confirmation' => $this->project->name,
        ])
        ->assertHasNoActionErrors();

    // Verify DB soft deleted
    $this->project->refresh();
    expect($this->project->trashed())->toBeTrue();

    // Verify Job dispatched
    Queue::assertPushed(SuspendProjectDomainJob::class, function ($job) {
        return $job->project->id === $this->project->id;
    });
});

it('redirects away from the dashboard if the project is soft deleted', function () {
    $this->project->delete(); // Soft delete it

    actingAs($this->owner);

    // Middleware EnsureUserHasActiveProject should block access and redirect to registration or another project
    get(route('filament.app.pages.dashboard', ['tenant' => $this->project->subdomain]))
        ->assertRedirect(route('filament.app.tenant.registration'));
});

it('lists only soft deleted projects in the ArchivedProjectsTable', function () {
    // Create an active project
    $activeProject = Project::factory()->create([
        'user_id' => $this->owner->id,
        'subdomain' => 'active-project',
        'is_active' => true,
    ]);
    
    // Soft delete our original project
    $this->project->delete();

    actingAs($this->owner);

    Livewire::test(ArchivedProjectsTable::class)
        ->assertCanSeeTableRecords([$this->project])
        ->assertCanNotSeeTableRecords([$activeProject]);
});

it('restores a soft deleted project and dispatches RestoreProjectDomainJob', function () {
    Queue::fake();
    $this->project->delete();

    actingAs($this->owner);

    Livewire::test(ArchivedProjectsTable::class)
        ->callTableAction('restore', $this->project)
        ->assertHasNoTableActionErrors();

    // Verify DB restored
    $this->project->refresh();
    expect($this->project->trashed())->toBeFalse();

    // Verify Job dispatched
    Queue::assertPushed(RestoreProjectDomainJob::class, function ($job) {
        return $job->project->id === $this->project->id;
    });
});
