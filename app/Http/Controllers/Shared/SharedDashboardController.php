<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Dashboard;
use App\Models\Project;
use App\Services\WidgetDataService;
use Illuminate\Http\Request;

class SharedDashboardController extends Controller
{
    public function show(Request $request, $subdomain, Dashboard $dashboard)
    {
        $project = Project::where('subdomain', $subdomain)->firstOrFail();

        if ($dashboard->project_id !== $project->id) {
            abort(404);
        }

        if (!$dashboard->is_public) {
            abort(404);
        }

        $widgets = $dashboard->widgets()
            ->orderBy('grid_y')
            ->orderBy('grid_x')
            ->get();

        $service = app(WidgetDataService::class);
        foreach ($widgets as $widget) {
            $resolved = $service->resolveControls($dashboard, $widget);
            $widget->resolved_controls = $resolved;
            $widget->series_assets_options = [];

            $uiState = [];
            if ($widget->source_type === 'kpi' && !empty($widget->source_config['custom_kpi_id'])) {
                $kpi = \App\Models\CustomKpi::find($widget->source_config['custom_kpi_id']);
                if ($kpi) {
                    $uiState = $kpi->filters['_ui_state'] ?? [];
                }
            }

            if (!empty($uiState['dependent_channel']) && empty($uiState['dependent_asset_filter'])) {
                $widget->series_assets_options['dependent'] = [
                    'label' => 'Dep (' . \Illuminate\Support\Str::headline($uiState['dependent_channel']) . ')',
                    'options' => \App\Services\Analytics\KpiFormBuilder::getAssetOptionsForChannel($uiState['dependent_channel'])
                ];
            }

            if (isset($uiState['independent_variables']) && is_array($uiState['independent_variables'])) {
                foreach ($uiState['independent_variables'] as $key => $var) {
                    if (!empty($var['independent_channel']) && empty($var['independent_asset_filter'])) {
                        $widget->series_assets_options['independent_' . $key] = [
                            'label' => 'Ind ' . $key . ' (' . \Illuminate\Support\Str::headline($var['independent_channel']) . ')',
                            'options' => \App\Services\Analytics\KpiFormBuilder::getAssetOptionsForChannel($var['independent_channel'])
                        ];
                    }
                }
            }

            if (empty($widget->series_assets_options) && !empty($resolved['channel']) && empty($resolved['assets'])) {
                $widget->series_assets_options['dependent'] = [
                    'label' => \Illuminate\Support\Str::headline($resolved['channel']),
                    'options' => \App\Services\Analytics\KpiFormBuilder::getAssetOptionsForChannel($resolved['channel'])
                ];
            }
        }

        return view('shared.public-dashboard', [
            'dashboard' => $dashboard,
            'project' => $project,
            'widgets' => $widgets,
        ]);
    }
}
