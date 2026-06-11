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

class StopProjectContainersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Project $project
    ) {}

    /**
     * Execute the job.
     */
    public function handle(DeployerService $deployer): void
    {
        try {
            $result = $deployer->stopContainers($this->project);
            
            if ($result['status'] !== 'success') {
                Log::error("Failed to stop containers for suspended project {$this->project->id}: " . ($result['output'] ?? 'SSH Error'));
            } else {
                Log::info("Successfully stopped containers for suspended project {$this->project->id}");
            }
        } catch (\Exception $e) {
            Log::error("Exception stopping containers for suspended project {$this->project->id}: " . $e->getMessage());
        }
    }
}
