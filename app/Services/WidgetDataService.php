<?php

namespace App\Services;

use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\Project;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;

class WidgetDataService
{
    public function resolveControls(Dashboard $dashboard, DashboardWidget $widget): array
    {
        $t0 = microtime(true);
        \Illuminate\Support\Facades\Log::debug("[DM_DEBUG] resolveControls ENTER", ['widget_id' => $widget->id]);
        $dashboardControls = $dashboard->controls ?? [];
        if (empty($dashboardControls['date_end'])) {
            $dashboardControls['date_end'] = \Carbon\Carbon::now()->subDays(1)->format('Y-m-d');
        }
        if (empty($dashboardControls['zero_handling'])) {
            $dashboardControls['zero_handling'] = 'remove';
        }
        if (empty($dashboardControls['granularity'])) {
            $dashboardControls['granularity'] = 'daily';
        }
        if (!isset($dashboardControls['edge_case_weighted'])) {
            $dashboardControls['edge_case_weighted'] = true;
        }
        if (empty($dashboardControls['edge_case_grouping'])) {
            $dashboardControls['edge_case_grouping'] = 'none';
        }

        $widgetControls = $widget->controls ?? [];

        \Illuminate\Support\Facades\Log::debug('[WidgetDataService.resolveControls] Widget ID: ' . $widget->id . ' | Stored controls metrics: ' . json_encode($widgetControls['metrics'] ?? '__NOT_SET__') . ' | Widget type: ' . $widget->widget_type . ' | Source type: ' . $widget->source_type);

        $resolved = [];

        $inheritableKeys = ['date_start', 'date_end', 'zero_handling', 'granularity', 'edge_case_weighted', 'edge_case_grouping'];

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

        // Inherit edge-case and max_ratio from KPI's _ui_state when applicable
        if ($widget->source_type === 'kpi' && $widget->customKpi) {
            $kpiUiState = $widget->customKpi->filters['_ui_state'] ?? [];
            if (!empty($kpiUiState['edge_case_grouping'])) {
                $resolved['edge_case_grouping'] = $kpiUiState['edge_case_grouping'];
            }
            if (isset($kpiUiState['max_ratio'])) {
                $resolved['max_ratio'] = $kpiUiState['max_ratio'];
            }
        }

        \Illuminate\Support\Facades\Log::debug("[DM_DEBUG] resolveControls EXIT", ['widget_id' => $widget->id, 'ms' => round((microtime(true) - $t0) * 1000, 1)]);

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
        return app(CollaboratorAssetAccessService::class)
            ->filterAllowedAssets($project, $userId, $channel, $assetIds);
    }

    public function computeControlsHash(array $controls): string
    {
        ksort($controls);
        return hash('sha256', json_encode($controls, JSON_UNESCAPED_UNICODE));
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
