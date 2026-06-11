<?php

namespace App\Services;

use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\Project;
use App\Models\ProjectUserAllowedAsset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;

class WidgetDataService
{
    public function resolveControls(Dashboard $dashboard, DashboardWidget $widget): array
    {
        $dashboardControls = $dashboard->controls ?? [];
        $widgetControls = $widget->controls ?? [];

        $resolved = [];

        $inheritableKeys = ['date_start', 'date_end', 'zero_handling'];

        // Start with widget controls as the base
        $resolved = $widgetControls;

        // Inherit global defaults where explicitly requested or missing
        foreach ($inheritableKeys as $key) {
            if (!isset($resolved[$key]) || $resolved[$key] === '__inherit__' || $resolved[$key] === '') {
                if (array_key_exists($key, $dashboardControls)) {
                    $resolved[$key] = $dashboardControls[$key];
                }
            }
        }

        return $resolved;
    }

    public function getResolvedAssetList(DashboardWidget $widget, array $resolvedControls): array
    {
        if (!empty($resolvedControls['assets']) && is_array($resolvedControls['assets'])) {
            return $resolvedControls['assets'];
        }

        return [];
    }

    public function filterAllowedAssets(Project $project, int $userId, string $channel, array $assetIds): array
    {
        $allowed = ProjectUserAllowedAsset::where('project_id', $project->id)
            ->where('user_id', $userId)
            ->where('channel', $channel)
            ->first();

        if (!$allowed || $allowed->allowed_assets === null) {
            return $assetIds;
        }

        $allowedAssetIds = $allowed->allowed_assets;

        return array_intersect($assetIds, $allowedAssetIds);
    }

    public function computeControlsHash(array $controls): string
    {
        ksort($controls);
        return hash('sha256', json_encode($controls));
    }

    public function getCachedResult(int $customKpiId, string $controlsHash)
    {
        return \App\Models\KpiResult::where('custom_kpi_id', $customKpiId)
            ->where('controls_hash', $controlsHash)
            ->where('expires_at', '>', now())
            ->first();
    }

    public function cacheResult(int $customKpiId, int $projectId, string $controlsHash, array $result, int $ttlMinutes = 60): void
    {
        \App\Models\KpiResult::create([
            'custom_kpi_id' => $customKpiId,
            'project_id' => $projectId,
            'controls_hash' => $controlsHash,
            'result' => $result,
            'cached_at' => now(),
            'expires_at' => now()->addMinutes($ttlMinutes),
        ]);
    }

    public function invalidateCache(int $customKpiId): void
    {
        \App\Models\KpiResult::where('custom_kpi_id', $customKpiId)->delete();
    }
}
