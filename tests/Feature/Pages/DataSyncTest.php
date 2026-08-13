<?php

use App\Filament\App\Pages\DataSync;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->user = User::factory()->create();
    Permission::firstOrCreate(['name' => 'view_data', 'guard_name' => 'web']);
    $this->user->givePermissionTo('view_data');
    $this->actingAs($this->user);
});

test('telemetry page renders undeployed card state when project is not deployed', function () {
    $project = Project::factory()->create([
        'user_id' => $this->user->id,
        'last_deployed_at' => null,
    ]);
    Filament::setTenant($project);

    Livewire::test(DataSync::class)
        ->assertSuccessful()
        ->assertSee('Project Not Deployed Yet')
        ->assertSee('Configure & Deploy Project');
});
