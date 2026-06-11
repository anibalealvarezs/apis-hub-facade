<?php

use App\Console\Commands\CleanupDeletedProjects;
use App\Jobs\DestroyProjectInfrastructureJob;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->owner = User::factory()->create();
});

it('dispatches destruction job and force deletes projects older than 30 days', function () {
    Queue::fake();

    $project = Project::factory()->create([
        'user_id' => $this->owner->id,
        'subdomain' => 'old-deleted-project',
        'is_active' => false,
    ]);
    
    // Soft delete it and artificially change the deleted_at date
    $project->delete();
    $project->deleted_at = now()->subDays(31);
    $project->save();

    // Run the command
    $this->artisan('projects:cleanup-deleted')
         ->expectsOutput('Starting cleanup of old deleted projects...')
         ->expectsOutput("Scheduling infrastructure destruction for project ID: {$project->id} ({$project->name})")
         ->expectsOutput('Cleanup complete.')
         ->assertExitCode(0);

    // Verify Job dispatched
    Queue::assertPushed(DestroyProjectInfrastructureJob::class, function ($job) use ($project) {
        return $job->project->id === $project->id;
    });

    // Verify it is hard deleted from DB
    $this->assertDatabaseMissing('projects', [
        'id' => $project->id,
    ]);
});

it('ignores soft deleted projects that are less than 30 days old', function () {
    Queue::fake();

    $project = Project::factory()->create([
        'user_id' => $this->owner->id,
        'subdomain' => 'recent-deleted-project',
        'is_active' => false,
    ]);
    
    // Soft delete it 10 days ago
    $project->delete();
    $project->deleted_at = now()->subDays(10);
    $project->save();

    // Run the command
    $this->artisan('projects:cleanup-deleted')
         ->expectsOutput('Starting cleanup of old deleted projects...')
         ->expectsOutput('No projects found for hard deletion.')
         ->assertExitCode(0);

    // Verify Job NOT dispatched
    Queue::assertNotPushed(DestroyProjectInfrastructureJob::class);

    // Verify it is STILL in the DB (soft deleted)
    $project->refresh();
    expect($project->trashed())->toBeTrue();
});

it('ignores active projects entirely', function () {
    Queue::fake();

    $project = Project::factory()->create([
        'user_id' => $this->owner->id,
        'subdomain' => 'active-project-never-delete',
        'is_active' => true,
    ]);

    // Run the command
    $this->artisan('projects:cleanup-deleted')
         ->expectsOutput('Starting cleanup of old deleted projects...')
         ->expectsOutput('No projects found for hard deletion.')
         ->assertExitCode(0);

    // Verify Job NOT dispatched
    Queue::assertNotPushed(DestroyProjectInfrastructureJob::class);

    // Verify it is STILL in the DB and active
    $project->refresh();
    expect($project->trashed())->toBeFalse();
});
