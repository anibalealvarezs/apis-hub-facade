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

        $kpiUiState = [];
        $kpi = null;
        if ($widget->source_type === 'kpi') {
            $kpi = $widget->relationLoaded('customKpi') ? $widget->customKpi : null;
            if (!$kpi && !empty($widget->custom_kpi_id)) {
                $kpi = $widget->customKpi;
            }
            if (!$kpi) {
                $kpiId = $widget->source_config['custom_kpi_id'] ?? $widget->custom_kpi_id ?? null;
                if ($kpiId) {
                    $kpi = \App\Models\CustomKpi::find($kpiId);
                }
            }
            if ($kpi) {
                $kpiUiState = $kpi->filters['_ui_state'] ?? [];
                if (empty($kpiUiState['granularity']) && !empty($kpiUiState['template_key'])) {
                    $predefined = \App\Services\Analytics\PredefinedKpiRegistry::getPredefinedKpis();
                    if (!empty($predefined[$kpiUiState['template_key']]['default_granularity'])) {
                        $kpiUiState['granularity'] = $predefined[$kpiUiState['template_key']]['default_granularity'];
                    }
                }
            }
        }

        // Inherit global defaults where explicitly requested or missing
        foreach ($inheritableKeys as $key) {
            if (!isset($resolved[$key]) || $resolved[$key] === '__inherit__' || $resolved[$key] === '' || $resolved[$key] === '__NOT_SET__') {
                // Priority 1: KPI-specific default (if applicable)
                // Note: KPI uses start_date/end_date instead of date_start/date_end
                $kpiKey = $key;
                if ($key === 'date_start') $kpiKey = 'start_date';
                if ($key === 'date_end') $kpiKey = 'end_date';

                if (isset($kpiUiState[$kpiKey]) && $kpiUiState[$kpiKey] !== '') {
                    $resolved[$key] = $kpiUiState[$kpiKey];
                    continue;
                }

                // Priority 2: Dashboard global default
                if (array_key_exists($key, $dashboardControls)) {
                    $resolved[$key] = $dashboardControls[$key];
                }
            }
        }

        // For KPI widgets, non-temporal granularities (e.g. channeledCampaign, dimensions.page, query)
        // are fixed by the KPI definition and cannot be overridden to temporal daily/weekly/etc.
        if ($widget->source_type === 'kpi' && !empty($kpiUiState['granularity'])) {
            $kpiGran = $kpiUiState['granularity'];
            $isTimely = in_array($kpiGran, ['daily', 'weekly', 'monthly', 'quarterly', 'semiannual', 'annually', 'lifetime'], true);
            if (!$isTimely || !isset($widgetControls['granularity']) || $widgetControls['granularity'] === '__inherit__' || $widgetControls['granularity'] === '' || $widgetControls['granularity'] === '__NOT_SET__') {
                $resolved['granularity'] = $kpiGran;
            }
        }

        // Inherit edge-case and max_ratio from KPI's _ui_state when applicable
        if ($widget->source_type === 'kpi' && $kpi) {
            if (!isset($resolved['edge_case_grouping']) && !empty($kpiUiState['edge_case_grouping'])) {
                $resolved['edge_case_grouping'] = $kpiUiState['edge_case_grouping'];
            }
            if (!isset($resolved['max_ratio']) && isset($kpiUiState['max_ratio'])) {
                $resolved['max_ratio'] = $kpiUiState['max_ratio'];
            }
        }

        \Illuminate\Support\Facades\Log::debug("[DM_DEBUG] resolveControls EXIT", ['widget_id' => $widget->id, 'ms' => round((microtime(true) - $t0) * 1000, 1)]);

        return $resolved;
    }

    public function getResolvedAssetList(DashboardWidget $widget, array $resolvedControls): array
    {
        if (!empty($resolvedControls['series_assets']) && is_array($resolvedControls['series_assets'])) {
            $flat = [];
            array_walk_recursive($resolvedControls['series_assets'], function ($v) use (&$flat) {
                if ($v !== null && $v !== '') {
                    $flat[] = (string) $v;
                }
            });
            $flat = array_values(array_unique($flat));
            if (!empty($flat)) {
                return $flat;
            }
        }

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
