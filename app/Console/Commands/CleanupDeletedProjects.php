<?php

namespace App\Console\Commands;

use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupDeletedProjects extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'projects:cleanup-deleted';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permanently delete projects that have been soft-deleted for more than 30 days and dismantle their infrastructure.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting cleanup of old deleted projects...');

        // Find projects deleted more than 30 days ago
        $projects = Project::onlyTrashed()
            ->where('deleted_at', '<=', now()->subDays(30))
            ->get();

        if ($projects->isEmpty()) {
            $this->info('No projects found for hard deletion.');
            return;
        }

        foreach ($projects as $project) {
            $this->info("Scheduling infrastructure destruction for project ID: {$project->id} ({$project->name})");
            Log::info("Hard deleting project ID: {$project->id} ({$project->name})");

            // Dispatch job to completely remove container, vhost, etc.
            \App\Jobs\DestroyProjectInfrastructureJob::dispatch($project);

            // Force delete the database record
            $project->forceDelete();
        }

        $this->info('Cleanup complete.');
    }
}
