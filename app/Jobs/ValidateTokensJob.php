<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\User;
use App\Services\RemoteEngineService;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ValidateTokensJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes

    public function __construct(
        public Project $project,
        public User $user
    ) {}

    public function handle(RemoteEngineService $service): void
    {
        try {
            $validation = $service->validateTokens($this->project, 'all');

            if (($validation['status'] ?? '') === 'error') {
                Notification::make()
                    ->danger()
                    ->title(__('Validation Failed for ' . $this->project->name))
                    ->body($validation['message'] ?? 'Unknown error connecting to remote engine.')
                    ->persistent()
                    ->sendToDatabase($this->user);
                return;
            }

            $results = $validation['results'] ?? [];
            $validCount = 0;
            $invalidCount = 0;
            $details = [];

            foreach ($results as $channel => $data) {
                $name = ucfirst($channel);
                if (($data['status'] ?? '') === 'valid') {
                    $validCount++;
                    $details[] = "✅ <strong>{$name}</strong>";
                } else {
                    $invalidCount++;
                    $details[] = "❌ <strong>{$name}</strong>";
                }
            }

            if (empty($details)) {
                Notification::make()
                    ->info()
                    ->title(__('No channels to validate for ' . $this->project->name))
                    ->sendToDatabase($this->user);
                return;
            }

            $notification = Notification::make()
                ->title(__("Validation Complete ({$this->project->name}): $validCount valid, $invalidCount invalid"))
                ->body(implode('<br>', $details));

            if ($invalidCount > 0) {
                $notification->warning()->persistent();
            } else {
                $notification->success();
            }

            $notification->sendToDatabase($this->user);
        } catch (\Exception $e) {
            Log::error("ValidateTokensJob failed for project {$this->project->id}: " . $e->getMessage());
            Notification::make()
                ->danger()
                ->title(__('Validation Error for ' . $this->project->name))
                ->body('An error occurred during validation: ' . $e->getMessage())
                ->persistent()
                ->sendToDatabase($this->user);
        }
    }
}
