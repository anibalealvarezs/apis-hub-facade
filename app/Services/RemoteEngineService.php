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
    protected function getClient(Project $project, int $timeout = 120): ApisHubApi
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
        if (! $apiKey) {
            throw new Exception("Remote Admin API Key not configured for project: {$project->name}");
        }

        $handlerStack = \GuzzleHttp\HandlerStack::create();
        $handlerStack->push(\GuzzleHttp\Middleware::mapRequest(function (\Psr\Http\Message\RequestInterface $request) {
            $body = (string) $request->getBody();
            $truncated = mb_strlen($body) > 5000 ? mb_substr($body, 0, 5000) . '...[truncated]' : $body;
            \Illuminate\Support\Facades\Log::info('[STEP Guzzle] Outgoing request', [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'headers' => $request->getHeaders(),
                'body' => $truncated,
            ]);
            if ($request->getBody()->isSeekable()) {
                $request->getBody()->rewind();
            }
            return $request;
        }));
        $handlerStack->push(\GuzzleHttp\Middleware::mapResponse(function (\Psr\Http\Message\ResponseInterface $response) {
            $body = (string) $response->getBody();
            $truncated = mb_strlen($body) > 5000 ? mb_substr($body, 0, 5000) . '...[truncated]' : $body;
            \Illuminate\Support\Facades\Log::info('[STEP Guzzle] Incoming response', [
                'status' => $response->getStatusCode(),
                'body' => $truncated,
            ]);
            if ($response->getBody()->isSeekable()) {
                $response->getBody()->rewind();
            }
            return $response;
        }));
        $guzzleClient = new \GuzzleHttp\Client([
            'handler' => $handlerStack,
            'timeout' => $timeout,
            'connect_timeout' => $timeout,
        ]);

        // Initialize SDK with base protocol and API Key
        return new ApisHubApi(
            baseUrl: "{$protocol}://{$domain}",
            apiKey: (string) $apiKey,
            guzzleClient: $guzzleClient,
            debugMode: false // NEVER couple this to config('app.debug'), otherwise the SDK intercepts and mocks all requests
        );
    }

    /**
     * Execute a task via the SDK with centralized error handling.
     */
    public function execute(Project $project, callable $callback, int $timeout = 120)
    {
        try {
            $client = $this->getClient($project, $timeout);

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
        return $this->execute($project, fn (ApisHubApi $client) => $client->triggerSync($channel));
    }

    /**
     * Trigger a historical synchronization resync via background job.
     */
    public function triggerHistoricalResync(Project $project, array $channels)
    {
        try {
            \App\Jobs\NuclearResyncProjectJob::dispatch($project, $channels);

            return ['status' => 'success', 'message' => 'Nuclear resync initiated via background job.'];

        } catch (\Throwable $e) {
            Log::error("Nuclear Resync Job dispatch failed for {$project->name}: " . $e->getMessage());

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Trigger a single-asset historical synchronization resync via background job.
     */
    public function triggerAssetHistoricalResync(Project $project, string $channel, string $assetId)
    {
        try {
            \App\Jobs\NuclearResyncProjectJob::dispatch($project, [$channel], $assetId);

            return ['status' => 'success', 'message' => 'Single asset nuclear resync initiated via background job.'];

        } catch (\Throwable $e) {
            Log::error("Asset Nuclear Resync Job dispatch failed for {$project->name} ({$channel}/{$assetId}): " . $e->getMessage());

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Stop all running sync jobs on the remote node.
     */
    public function stopJobs(Project $project)
    {
        return $this->execute($project, fn (ApisHubApi $client) => $client->stopJobs());
    }

    /**
     * Trigger a background redeployment via a tracked job.
     */
    public function triggerRedeploy(Project $project)
    {
        try {
            \App\Jobs\DeployProjectJob::dispatch($project);

            return ['status' => 'success', 'message' => 'Redeploy initiated via background job.'];

        } catch (\Throwable $e) {
            Log::error("Redeploy Job dispatch failed for {$project->name}: " . $e->getMessage());

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Trigger a lightweight synchronization start via a tracked job.
     */
    public function startSync(Project $project)
    {
        try {
            \App\Jobs\SyncProjectJob::dispatch($project);

            return ['status' => 'success', 'message' => 'Synchronization initiated via background job.'];

        } catch (\Throwable $e) {
            Log::error("Start Sync Job dispatch failed for {$project->name}: " . $e->getMessage());

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Update secure environment credentials on the remote node.
     */
    public function updateCredentials(Project $project, array $credentials)
    {
        return $this->execute($project, fn (ApisHubApi $client) => $client->updateConfig($credentials));
    }

    /**
     * Perform an action on a specific container (e.g. "start", "stop").
     */
    public function containerAction(Project $project, string $name, string $action)
    {
        return $this->execute($project, fn (ApisHubApi $client) => $client->containerAction($name, $action));
    }

    /**
     * Fetch the most recent heartbeat diagnostics from the node.

     */
    public function getHeartbeat(Project $project)
    {
        return $this->execute($project, fn (ApisHubApi $client) => $client->getHeartbeat());
    }

    /**
     * Fetch live infrastructure status from the node's management API.
     */
    public function getStatus(Project $project)
    {
        return $this->execute($project, fn (ApisHubApi $client) => $client->getStatus());
    }

    /**
     * Fetch real-time monitoring of active job logs/status.
     */
    public function getMonitoringData(Project $project)
    {
        return $this->execute($project, fn (ApisHubApi $client) => $client->getMonitoringData());
    }

    /**
     * Fetch sync telemetry payload.
     */
    public function getSyncTelemetry(Project $project)
    {
        return $this->execute($project, fn (ApisHubApi $client) => $client->getSyncTelemetry());
    }

    /**
     * Validate and fetch platform tokens from the remote node.
     */
    public function validateTokens(Project $project, string $type = 'all')
    {
        return $this->execute($project, fn (ApisHubApi $client) => $client->validateTokens(['type' => $type]), 15);
    }

    /**
     * Fetch live assets from the remote node.
     */
    public function fetchAssets(Project $project, string $channel, bool $refresh = false)
    {
        return $this->execute($project, fn (ApisHubApi $client) => $client->fetchAssets([
            'type' => $channel,
            'refresh' => $refresh ? 1 : 0,
        ]));
    }

    /**
     * Perform an aggregation query on channeled entities via the remote node.
     */
    public function aggregateChanneled(Project $project, string $channel, string $entity, array $payload)
    {
        return $this->execute($project, fn (ApisHubApi $client) => $client->aggregateChanneled($channel, $entity, $payload));
    }

    /**
     * Compute a Custom KPI using an AST via the remote node.
     */
    public function computeKpi(Project $project, array $payload)
    {
        $payload['admin_api_key'] = env('ANALYTICS_API_KEY', 'dev_secret_key');
        $payload['analytics_engine_host'] = env('ANALYTICS_ENGINE_HOST', 'https://analytics.apis-hub.cloud/');

        $logPayload = $payload;
        unset($logPayload['admin_api_key']);
        \Illuminate\Support\Facades\Log::info('RemoteEngine KPI payload', $logPayload);

        $result = $this->execute($project, fn (ApisHubApi $client) => $client->computeKpi($payload));

        \Illuminate\Support\Facades\Log::info('RemoteEngine KPI raw result', is_array($result) ? ['has_result' => true, 'keys' => array_keys($result), 'data_keys' => isset($result['data']) ? array_keys($result['data']) : null] : ['has_result' => false, 'raw' => substr(json_encode($result), 0, 500)]);

        // Log full response data structure — check for intermediate/raw fields
        if (is_array($result) && isset($result['data'])) {
            $allDataKeys = array_keys($result['data']);
            $extraKeys = array_diff($allDataKeys, ['baseline_intercept','coefficients','r_squared','data_points','scatter_data']);
            if (!empty($extraKeys)) {
                \Illuminate\Support\Facades\Log::info('RemoteEngine KPI extra data keys', ['keys' => array_values($extraKeys)]);
            }
            // Log what data_points contains (it might hold raw keyword data)
            if (isset($result['data']['data_points'])) {
                $dp = $result['data']['data_points'];
                \Illuminate\Support\Facades\Log::info('RemoteEngine KPI data_points detail', [
                    'type' => gettype($dp),
                    'value' => is_scalar($dp) ? $dp : (is_array($dp) ? 'array(len=' . count($dp) . ')' : 'object'),
                ]);
            }
            // Log the FULL scatter_data structure for completeness
            if (isset($result['data']['scatter_data'])) {
                $sd = $result['data']['scatter_data'];
                \Illuminate\Support\Facades\Log::info('RemoteEngine KPI scatter_data structure', [
                    'keys' => array_keys($sd),
                    'x_type' => gettype($sd['x'] ?? null),
                    'x_len' => is_array($sd['x'] ?? null) ? count($sd['x']) : 0,
                    'y_type' => gettype($sd['y'] ?? null),
                    'y_len' => is_array($sd['y'] ?? null) ? count($sd['y']) : 0,
                    'labels_type' => gettype($sd['labels'] ?? null),
                    'labels_len' => is_array($sd['labels'] ?? null) ? count($sd['labels']) : 0,
                    'x_label' => $sd['x_label'] ?? null,
                    'has_x_label_array' => isset($sd['x_label']) && is_array($sd['x_label']),
                ]);
            }
        }

        return $result;
    }

    /**
     * Perform concurrent aggregation queries via the remote node.
     */
    public function aggregateChanneledPool(Project $project, string $channel, string $entity, array $payloads)
    {
        $results = [];
        $startTime = microtime(true);

        foreach ($payloads as $key => $payload) {
            $startReq = microtime(true);
            $response = $this->aggregateChanneled($project, $channel, $entity, $payload);

            // SDK returns the array directly, but handles errors by returning ['status' => 'error']
            if (isset($response['status']) && $response['status'] === 'error') {
                $results[$key] = $response;
            } else {
                $results[$key] = $response;
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
        return $this->execute($project, fn (ApisHubApi $client) => $client->listChanneled($channel, $entity, $params));
    }

    /**
     * Get trend calculation from Analytics Engine directly.
     */
    public function getTrend(string $type, array $payload)
    {
        $host = env('ANALYTICS_ENGINE_HOST', 'http://analytics-engine:8050/');
        $apiKey = env('ANALYTICS_API_KEY', 'dev_secret_key');
        
        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'X-Admin-API-Key' => $apiKey,
                'Content-Type' => 'application/json'
            ])->post(rtrim($host, '/') . "/api/v1/stats/trend/{$type}", $payload);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            \Illuminate\Support\Facades\Log::error("Analytics Engine Trend Failed", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to calculate trend.'
            ];
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error("Analytics Engine Connection Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Analytics Engine unreachable.'
            ];
        }
    }

    /**
     * Get cross-metric correlation from Analytics Engine directly.
     */
    public function getCorrelation(array $payload)
    {
        $host = env('ANALYTICS_ENGINE_HOST', 'http://analytics-engine:8050/');
        $apiKey = env('ANALYTICS_API_KEY', 'dev_secret_key');
        
        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'X-Admin-API-Key' => $apiKey,
                'Content-Type' => 'application/json'
            ])->post(rtrim($host, '/') . "/api/v1/stats/correlation", $payload);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            \Illuminate\Support\Facades\Log::error("Analytics Engine Correlation Failed", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to calculate correlation.'
            ];
            
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error("Analytics Engine Connection Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Analytics Engine unreachable.'
            ];
        }
    }
}
