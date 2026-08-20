<?php

namespace App\Services;

use App\Models\Project;
use Anibalealvarezs\ApisHubApi\ApisHubApi;
use Illuminate\Support\Facades\Log;

class ConfigSyncService
{
    /**
     * Synchronize a specific channel configuration with the remote APIs Hub node.
     *
     * @param Project $project The target project
     * @param string $channel The channel identifier (e.g. 'google_search_console')
     * @param array $userConfig The user-provided configuration from the Facade UI
     * @return array The API response
     * @throws \Exception
     */
    public function syncChannelConfig(Project $project, string $channel, array $userConfig): array
    {
        // 1. Validar que el proyecto tiene un release y SDK configurado
        if (!$project->apisHubRelease) {
            throw new \Exception("The project '{$project->name}' does not have a pinned APIs Hub release.");
        }

        // 2. Obtener el esquema para el canal
        $schemas = $project->apisHubRelease->config_schemas ?? [];
        if (!isset($schemas[$channel]['fields'])) {
            try {
                $profile = app(\App\Domain\ChannelProfiles\ChannelProfileRegistry::class)->get($channel);
                if ($profile) {
                    $schemas[$channel] = $profile->getSchemaDefinition();
                }
            } catch (\Throwable $e) {}
        }

        if (!isset($schemas[$channel]['fields'])) {
            throw new \Exception("The channel '{$channel}' is not supported in the pinned APIs Hub release.");
        }

        $schema = $schemas[$channel]['fields'];
        
        // 3. Construir el Payload final fusionando Defaults (estáticos) y UserConfig (dinámicos)
        $payload = ['type' => $channel];
        
        foreach ($schema as $key => $definition) {
            // Si el usuario proveyó el valor, lo usamos. Si no, usamos el default del schema.
            if (array_key_exists($key, $userConfig)) {
                $payload[$key] = $userConfig[$key];
            } else {
                $payload[$key] = $definition['default'] ?? null;
            }
        }

        // 4. Instanciar el SDK
        $facadeUrl = rtrim(config('app.url'), '/');
        // Asumiendo que el SDK se comunica con el subdominio del tenant. 
        // Ejemplo: https://tenant.apis-hub.cloud
        $tenantUrl = "https://{$project->subdomain}.apis-hub.cloud"; 
        
        if (!$project->remote_admin_api_key) {
             throw new \Exception("The project does not have a remote_admin_api_key generated.");
        }

        $sdk = new ApisHubApi(
            baseUrl: $tenantUrl,
            apiKey: $project->remote_admin_api_key
        );

        // 5. Enviar el payload a APIs Hub
        Log::info("Syncing {$channel} configuration for project {$project->name} to {$tenantUrl}");
        
        try {
            $response = $sdk->updateConfig($payload);
            return $response;
        } catch (\Exception $e) {
            Log::error("Failed to sync {$channel} config for {$project->name}: " . $e->getMessage());
            throw $e;
        }
    }
}
