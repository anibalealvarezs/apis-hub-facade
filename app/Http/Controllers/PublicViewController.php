<?php

namespace App\Http\Controllers;

use App\Services\PublicViewService;
use App\Services\WidgetDataService;
use Illuminate\Http\Request;

class PublicViewController extends Controller
{
    public function __construct(protected PublicViewService $pvService)
    {
    }

    public function show(Request $request, string $token)
    {
        $pv = $this->pvService->verifyToken($token);
        if (!$pv || !$pv->is_active || $pv->trashed()) {
            abort(404);
        }

        $dashboard = $pv->dashboard;
        if (!$dashboard || !$dashboard->is_public || $dashboard->trashed()) {
            abort(404);
        }

        $project = $dashboard->project;
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

        $isEmbedded = $request->boolean('embedded');

        return view('public-view.show', [
            'pv' => $pv,
            'dashboard' => $dashboard,
            'project' => $project,
            'widgets' => $widgets,
            'isEmbedded' => $isEmbedded,
        ]);
    }
}
