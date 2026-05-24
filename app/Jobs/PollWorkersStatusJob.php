<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\User;
use App\Services\DeployerService;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PollWorkersStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Project $project;
    public string $provider;
    public int $tries = 120; // Allow 120 tries (e.g. 2 hours if delayed 1 min each)
    public int $maxExceptions = 3;

    public function __construct(Project $project, string $provider)
    {
        $this->project = $project;
        $this->provider = $provider;
    }

    public function handle(DeployerService $deployerService)
    {
        Log::info("Polling worker status for project {$this->project->id}...");

        $cmd = "php bin/console dbal:run-sql 'SELECT count(id) FROM jobs WHERE status = 2'";

        try {
            $response = $deployerService->executeCommand($this->project, clone $this->project->server, $cmd);
            
            // Extract the count from the output.
            $outputString = $response['output'] ?? '';
            preg_match('/\b\d+\b/', $outputString, $matches);
            $activeJobs = isset($matches[0]) ? (int)$matches[0] : 0;

            if ($activeJobs === 0) {
                Log::info("Workers for project {$this->project->id} have stopped gracefully.");
                $this->project->update(['health_status' => 'ready_for_auth']);

                // Notify all project users
                $users = $this->project->users()->get()->push($this->project->trueOwner);
                $providerName = ucfirst($this->provider);

                foreach ($users as $user) {
                    Notification::make()
                        ->title('Safe to Update Credentials')
                        ->body("All active sync jobs have been paused. You can now safely update your {$providerName} credentials.")
                        ->success()
                        ->actions([
                            Action::make('authorize')
                                ->label('Update Now')
                                ->url(route('oauth.redirect', ['provider' => $this->provider]))
                                ->button()
                        ])
                        ->sendToDatabase($user);
                }
            } else {
                Log::info("Project {$this->project->id} still has {$activeJobs} active jobs. Polling again in 1 minute.");
                // Re-dispatch with 1 min delay
                self::dispatch($this->project, $this->provider)->delay(now()->addMinute());
            }

        } catch (\Exception $e) {
            Log::error("Error polling worker status for project {$this->project->id}: " . $e->getMessage());
            // Retry on transient SSH error, throw to decrement $tries
            throw $e;
        }
    }
}
