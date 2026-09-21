<?php

namespace App\Jobs;

use App\Models\Project;
use Anibalealvarezs\ApisHubApi\ApisHubApi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RevokeSharedAiKeyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Log::info('[RevokeSharedAiKeyJob] Starting revocation of shared AI credentials across borrowing tenants.');

        // Find all active projects that do NOT have their own dedicated key and support AI (v1.16.0+)
        $borrowingProjects = Project::where('is_active', true)
            ->whereNull('typesafe_api_key')
            ->get()
            ->filter(fn (Project $p) => $p->supportsAiClassification());

        $revokedCount = 0;
        foreach ($borrowingProjects as $project) {
            try {
                if (empty($project->remote_admin_api_key)) {
                    continue;
                }

                $domain = config('app.network_domain') ?: 'apis-hub.cloud';
                $scheme = config('app.env') === 'local' ? 'http' : 'https';
                $hubUrl = "{$scheme}://{$project->subdomain}.{$domain}";

                $client = new ApisHubApi(
                    baseUrl: $hubUrl,
                    apiKey: $project->remote_admin_api_key
                );

                // Scrub the TYPESAFE_API_KEY from the remote tenant .env
                $client->updateCredentials([
                    'TYPESAFE_API_KEY' => '',
                ]);

                $revokedCount++;
            } catch (\Throwable $e) {
                Log::warning("[RevokeSharedAiKeyJob] Failed to scrub AI key on project #{$project->id} ({$project->name}): " . $e->getMessage());
            }
        }

        Log::info("[RevokeSharedAiKeyJob] Completed revocation. Scrubbed key from {$revokedCount} tenants.");
    }
}