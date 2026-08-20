<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

\Illuminate\Support\Facades\Schedule::command('projects:cleanup-deleted')->daily();
\Illuminate\Support\Facades\Schedule::command('billing:process-grace-periods')->everyFiveMinutes();
\Illuminate\Support\Facades\Schedule::command('billing:expire-pending-assignments')->daily();
\Illuminate\Support\Facades\Schedule::command('bcv:fetch')->everyFifteenMinutes();
\Illuminate\Support\Facades\Schedule::command('model:prune', ['--model' => [\App\Models\AlertLog::class]])->daily();

Artisan::command('alerts:sync {project_id?}', function ($projectId = null) {
    $deployer = app(\App\Services\DeployerService::class);
    $query = \App\Models\Project::query();
    if ($projectId) {
        $query->where('id', $projectId);
    }
    $projects = $query->get();
    foreach ($projects as $project) {
        $this->info("Syncing alerts for project {$project->name} (ID: {$project->id})...");
        $res = $deployer->syncAlertConfig($project);
        if ($res) {
            $this->info("  -> Success!");
        } else {
            $this->error("  -> Failed or skipped.");
        }
    }
})->purpose('Synchronize alerts.json configuration to remote apis-hub tenant nodes.');

