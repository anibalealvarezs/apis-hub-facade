<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\DeployerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RestoreProjectDomainJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120; // 2 minutes

    public function __construct(
        public Project $project
    ) {}

    public function handle(DeployerService $deployer): void
    {
        try {
            $result = $deployer->restoreDomain($this->project);
            
            if ($result['status'] !== 'success') {
                Log::error("Failed to restore domain for project {$this->project->id}: " . $result['output']);
            } else {
                Log::info("Successfully restored domain for project {$this->project->id}");
            }
        } catch (\Exception $e) {
            Log::error("Exception restoring domain for project {$this->project->id}: " . $e->getMessage());
        }
    }
}
