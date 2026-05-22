<?php

use App\Filament\App\Pages\ProjectSettings;
use App\Filament\App\Pages\RegisterProject;
use App\Jobs\DeployProjectJob;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    
    // Create a server to satisfy the register logic
    $this->server = Server::factory()->create([
        'is_ready' => true,
    ]);
});

it('does not queue DeployProjectJob if deploy_immediately is false during registration', function () {
    Queue::fake();
    actingAs($this->user);

    Livewire::test(RegisterProject::class)
        ->fillForm([
            'name' => 'Test Project',
            'subdomain' => 'test-deploy-false',
            'deploy_immediately' => false,
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    // Verify Job was NOT dispatched
    Queue::assertNotPushed(DeployProjectJob::class);

    // Verify Project was created logically
    $project = Project::where('subdomain', 'test-deploy-false')->first();
    expect($project)->not->toBeNull();
    expect($project->last_deployed_at)->toBeNull();
});

it('queues DeployProjectJob if deploy_immediately is true during registration', function () {
    Queue::fake();
    actingAs($this->user);

    Livewire::test(RegisterProject::class)
        ->fillForm([
            'name' => 'Test Project True',
            'subdomain' => 'test-deploy-true',
            'deploy_immediately' => true,
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    // Verify Job WAS dispatched
    Queue::assertPushed(DeployProjectJob::class);
});

it('shows the manual deploy action if the project has never been deployed', function () {
    $project = Project::factory()->create([
        'user_id' => $this->user->id,
        'subdomain' => 'undeployed-project',
        'is_active' => true,
        'last_deployed_at' => null, // Never deployed
    ]);
    $project->users()->attach($this->user->id);

    actingAs($this->user);

    Livewire::test(ProjectSettings::class, ['tenant' => $project->subdomain])
        ->assertActionVisible('deploy_initial');
});

it('queues DeployProjectJob when the manual deploy action is used', function () {
    Queue::fake();
    
    $project = Project::factory()->create([
        'user_id' => $this->user->id,
        'subdomain' => 'manual-deploy-project',
        'is_active' => true,
        'last_deployed_at' => null,
    ]);
    $project->users()->attach($this->user->id);

    actingAs($this->user);

    Livewire::test(ProjectSettings::class, ['tenant' => $project->subdomain])
        ->callAction('deploy_initial')
        ->assertHasNoActionErrors();

    Queue::assertPushed(DeployProjectJob::class, function ($job) use ($project) {
        return $job->project->id === $project->id;
    });
});

it('hides the manual deploy action if the project was already deployed', function () {
    $project = Project::factory()->create([
        'user_id' => $this->user->id,
        'subdomain' => 'deployed-project',
        'is_active' => true,
        'last_deployed_at' => now(), // Deployed
    ]);
    $project->users()->attach($this->user->id);

    actingAs($this->user);

    Livewire::test(ProjectSettings::class, ['tenant' => $project->subdomain])
        ->assertActionHidden('deploy_initial');
});
