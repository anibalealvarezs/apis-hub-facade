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
// Reconcile running operations even when a worker died or a monitor message was lost.
Artisan::command('upgrades:reconcile', function () {
    \App\Models\ProjectDeploymentLog::where('status', 'running')
        ->whereNotNull('upgrade_operation_id')->whereNotNull('target_release_id')
        ->eachById(function ($log) {
            $project = $log->project;
            $release = \App\Models\ApisHubRelease::find($log->target_release_id);
            if ($project && $release) \App\Jobs\UpgradeProjectReleaseJob::dispatch($project, $release, $log->upgrade_operation_id);
        });
})->purpose('Reconnect to durable tenant upgrades and verify their final state.');
\Illuminate\Support\Facades\Schedule::command('upgrades:reconcile')->everyMinute()->withoutOverlapping();
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

