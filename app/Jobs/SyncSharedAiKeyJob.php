<?php

namespace App\Jobs;

use App\Models\Project;
use App\Settings\AiSettings;
use Anibalealvarezs\ApisHubApi\ApisHubApi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncSharedAiKeyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function handle(): void
    {
        $settings = app(AiSettings::class);

        if (!$settings->typesafe_admin_share_enabled || empty($settings->typesafe_admin_api_key)) {
            Log::info('[SyncSharedAiKeyJob] Sharing is disabled or admin key is empty. Skipping push.');
            return;
        }

        $sharedKey = $settings->typesafe_admin_api_key;
        Log::info('[SyncSharedAiKeyJob] Starting propagation of shared TypeSafe AI key to borrowing tenants.');

        // Find all active projects supporting AI (v1.16.0+) that do NOT have their own dedicated key
        $borrowingProjects = Project::where('is_active', true)
            ->whereNull('typesafe_api_key')
            ->whereNotNull('remote_admin_api_key')
            ->get()
            ->filter(fn (Project $p) => $p->supportsAiClassification());

        $syncedCount = 0;
        foreach ($borrowingProjects as $project) {
            try {
                $domain = config('app.network_domain') ?: 'apis-hub.cloud';
                $scheme = config('app.env') === 'local' ? 'http' : 'https';
                $hubUrl = "{$scheme}://{$project->subdomain}.{$domain}";

                $client = new ApisHubApi(
                    baseUrl: $hubUrl,
                    apiKey: $project->remote_admin_api_key
                );

                // Push shared key to tenant node hot-reload endpoint
                $client->updateCredentials([
                    'TYPESAFE_API_KEY' => $sharedKey,
                    'TYPESAFE_BASE_URL' => 'https://api.typesafe.ai/v1/',
                ]);

                $syncedCount++;
            } catch (\Throwable $e) {
                Log::warning("[SyncSharedAiKeyJob] Failed to push shared AI key to tenant #{$project->id} ({$project->name}): " . $e->getMessage());
            }
        }

        Log::info("[SyncSharedAiKeyJob] Completed synchronization. Pushed shared key to {$syncedCount} borrowing tenants.");
    }
}
