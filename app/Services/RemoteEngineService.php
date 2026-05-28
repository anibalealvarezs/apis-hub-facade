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

        // 🛠️ Local/Demo Environment Hack: Connect directly to the master node container or port
        $protocol = 'https';
        if ($project->subdomain === 'alpha') {
            $domain = 'localhost:10000';
            $protocol = 'http';
        }

        $apiKey = $project->remote_admin_api_key;
        if (!$apiKey) {
            throw new Exception("Remote Admin API Key not configured for project: {$project->name}");
        }

        // Initialize SDK with base protocol and API Key
        return new ApisHubApi(
            baseUrl: "{$protocol}://{$domain}",
            apiKey: (string) $apiKey,
            debugMode: false // NEVER couple this to config('app.debug'), otherwise the SDK intercepts and mocks all requests
        );
    }


    /**
     * Execute a task via the SDK with centralized error handling.
     */
    public function execute(Project $project, callable $callback)
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
     * Trigger a lightweight synchronization start without full downtime.
     */
    public function startSync(Project $project)
    {
        return $this->execute($project, fn(ApisHubApi $client) => $client->startSync());
    }

    /**
     * Update secure environment credentials on the remote node.
     */
    public function updateCredentials(Project $project, array $credentials)
    {
        return $this->execute($project, fn(ApisHubApi $client) => $client->updateConfig($credentials));
    }

    /**
     * Perform an action on a specific container (e.g. "start", "stop").
     */
    public function containerAction(Project $project, string $name, string $action)
    {
        return $this->execute($project, fn(ApisHubApi $client) => $client->containerAction($name, $action));
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

    /**
     * Fetch sync telemetry payload.
     */
    public function getSyncTelemetry(Project $project)
    {
        return $this->execute($project, fn(ApisHubApi $client) => $client->getSyncTelemetry());
    }

    /**
     * Validate and fetch platform tokens from the remote node.
     */
    public function validateTokens(Project $project, string $type = 'all')
    {
        return $this->execute($project, fn(ApisHubApi $client) => $client->validateTokens(['type' => $type]));
    }

    /**
     * Fetch live assets from the remote node.
     */
    public function fetchAssets(Project $project, string $channel, bool $refresh = false)
    {
        return $this->execute($project, fn(ApisHubApi $client) => $client->fetchAssets([
            'type' => $channel,
            'refresh' => $refresh ? 1 : 0
        ]));
    }

    /**
     * Perform an aggregation query on channeled entities via the remote node.
     */
    public function aggregateChanneled(Project $project, string $channel, string $entity, array $payload)
    {
        return $this->execute($project, fn(ApisHubApi $client) => $client->aggregateChanneled($channel, $entity, $payload));
    }

    /**
     * Perform concurrent aggregation queries via the remote node.
     */
    public function aggregateChanneledPool(Project $project, string $channel, string $entity, array $payloads)
    {
        $baseDomain = config('app.network_domain');
        $domain = "{$project->subdomain}.{$baseDomain}";

        $protocol = 'https';
        if ($project->subdomain === 'alpha') {
            $domain = 'localhost:10000';
            $protocol = 'http';
        }

        $apiKey = $project->remote_admin_api_key;
        if (!$apiKey) {
            throw new Exception("Remote Admin API Key not configured for project: {$project->name}");
        }

        $url = "{$protocol}://{$domain}/{$channel}/{$entity}/aggregate";
        
        $results = [];
        $startTime = microtime(true);
        \Illuminate\Support\Facades\Log::error("Starting aggregateChanneledPool with ".count($payloads)." payloads");
        
        foreach ($payloads as $key => $payload) {
            try {
                \Illuminate\Support\Facades\Log::error("Sending payload for '{$key}'...");
                $startReq = microtime(true);
                
                $response = \Illuminate\Support\Facades\Http::timeout(45)
                    ->withHeaders([
                        'X-Admin-API-Key' => $apiKey,
                        'Accept' => 'application/json',
                        'Connection' => 'close',
                    ])
                    ->post($url, $payload);
                    
                $reqDuration = round(microtime(true) - $startReq, 3);
                \Illuminate\Support\Facades\Log::error("Payload '{$key}' finished in {$reqDuration}s. Status: " . $response->status());
                    
                if ($response->ok()) {
                    $results[$key] = $response->json();
                } else {
                    $results[$key] = ['status' => 'error', 'message' => "Request failed with status {$response->status()}"];
                }
            } catch (\Exception $e) {
                $reqDuration = round(microtime(true) - $startReq, 3);
                \Illuminate\Support\Facades\Log::error("Payload '{$key}' threw Exception after {$reqDuration}s: " . $e->getMessage());
                $results[$key] = ['status' => 'error', 'message' => $e->getMessage()];
            }
        }

        $totalDuration = round(microtime(true) - $startTime, 3);
        \Illuminate\Support\Facades\Log::error("Finished aggregateChanneledPool in {$totalDuration}s");

        return $results;
    }

    /**
     * List channeled entities via the remote node.
     */
    public function listChanneled(Project $project, string $channel, string $entity, array $params = [])
    {
        return $this->execute($project, fn(ApisHubApi $client) => $client->listChanneled($channel, $entity, $params));
    }
}
