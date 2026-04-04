<?php

namespace App\Services;

use Anibalealvarezs\ApisHubApi\ApisHubApi;
use App\Models\Project;
use Exception;
use Illuminate\Support\Facades\Log;

class RemoteEngineService
{
    /**
     * Get an instance of the ApisHubApi client for a project.
     */
    protected function getClient(Project $project): ApisHubApi
    {
        $baseDomain = config('app.network_domain');
        $domain = "{$project->subdomain}.{$baseDomain}";

        $apiKey = $project->remote_admin_api_key;
        if (!$apiKey) {
            throw new Exception("Remote Admin API Key not configured for project: {$project->name}");
        }

        // Initialize SDK with base protocol and API Key
        // SDK handles /api prefix internally for management routes
        return new ApisHubApi(
            baseUrl: "https://{$domain}",
            apiKey: (string) $apiKey,
            debugMode: config('app.debug', false)
        );
    }

    /**
     * Execute a task via the SDK with centralized error handling.
     */
    protected function execute(Project $project, callable $callback)
    {
        try {
            $client = $this->getClient($project);
            return $callback($client);
        } catch (Exception $e) {
            Log::error("Remote Engine Action Failed: {$project->name}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send a custom command to the remote node (legacy support).
     */
    public function sendCommand(Project $project, string $endpoint, array $payload = [], string $method = 'POST')
    {
        return $this->execute($project, function (ApisHubApi $client) use ($endpoint, $payload, $method) {
            // Note: SDK's performRequest is protected, but we use public methods for specific tasks.
            // If we need a raw custom endpoint, we would ideally add it to the SDK.
            // For now, assume most common ones are covered by SDK methods.
            if ($endpoint === 'management/update-credentials') {
                return $client->updateConfig($payload);
            }
            if ($endpoint === 'sync/run') {
                return $client->triggerSync($payload['channel'] ?? 'all');
            }
            if ($endpoint === 'sync/stop') {
                return $client->stopJobs();
            }
            
            // Fallback for others if needed, using a generic method if available
            // but for now, let's stick to the typed methods below.
            return ['status' => 'error', 'message' => "Endpoint '{$endpoint}' should be called via its SDK method."];
        });
    }

    /**
     * Trigger a manual sync on the remote node.
     */
    public function triggerSync(Project $project, string $channel = 'all')
    {
        return $this->execute($project, fn(ApisHubApi $client) => $client->triggerSync($channel));
    }

    /**
     * Stop all running sync jobs on the remote node.
     */
    public function stopJobs(Project $project)
    {
        return $this->execute($project, fn(ApisHubApi $client) => $client->stopJobs());
    }

    /**
     * Trigger a background redeployment via the Node's management API.
     */
    public function triggerRedeploy(Project $project)
    {
        return $this->execute($project, fn(ApisHubApi $client) => $client->triggerRedeploy());
    }

    /**
     * Update secure environment credentials on the remote node.
     */
    public function updateCredentials(Project $project, array $credentials)
    {
        return $this->execute($project, fn(ApisHubApi $client) => $client->updateConfig($credentials));
    }

    /**
     * Fetch the most recent heartbeat diagnostics from the node.
     */
    public function getHeartbeat(Project $project)
    {
        return $this->execute($project, fn(ApisHubApi $client) => $client->getHeartbeat());
    }

    /**
     * Fetch live infrastructure status from the node's management API.
     */
    public function getStatus(Project $project)
    {
        return $this->execute($project, fn(ApisHubApi $client) => $client->getStatus());
    }

    /**
     * Fetch real-time monitoring of active job logs/status.
     */
    public function getMonitoringData(Project $project)
    {
        return $this->execute($project, fn(ApisHubApi $client) => $client->getMonitoringData());
    }
}
