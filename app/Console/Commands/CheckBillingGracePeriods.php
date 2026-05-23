<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Project;
use App\Services\BillingLifecycleService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckBillingGracePeriods extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:check-grace-periods';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks projects that are past due and enforces downgrade/suspension after 7 days of grace period.';

    /**
     * Execute the console command.
     */
    public function handle(BillingLifecycleService $lifecycleService)
    {
        $this->info('Checking billing grace periods...');

        // Find projects that have been past due for more than 7 days
        $expiredProjects = Project::where('billing_status', 'past_due')
            ->whereNotNull('past_due_at')
            ->where('past_due_at', '<=', Carbon::now()->subDays(7))
            ->get();

        if ($expiredProjects->isEmpty()) {
            $this->info('No expired grace periods found.');
            return;
        }

        foreach ($expiredProjects as $project) {
            $owner = $project->trueOwner;
            if (!$owner) continue;

            $this->info("Enforcing downgrade for User {$owner->id} due to project {$project->id} past due.");
            
            // Downgrade to Free tier. 
            // The service will handle suspending this project if the user exceeds the 1 project limit.
            $suspendedProjects = $lifecycleService->enforceDowngradeLimits($owner, \App\Enums\UserTier::FREE);
            
            $owner->notify(new \App\Notifications\ProjectsSuspendedNotification(count($suspendedProjects)));
        }

        $this->info('Completed checking billing grace periods.');
    }
}
