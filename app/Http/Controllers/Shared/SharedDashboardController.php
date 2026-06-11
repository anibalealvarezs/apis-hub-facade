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
            $widget->resolved_controls = $service->resolveControls($dashboard, $widget);
        }

        $runtimeAssets = [];
        $runtimeChannel = null;
        $dashboardControls = $dashboard->controls ?? [];
        if (!empty($dashboardControls['asset_mode']) && $dashboardControls['asset_mode'] === 'multiple' && !empty($dashboardControls['assets']) && !empty($dashboardControls['channel'])) {
            $runtimeChannel = $dashboardControls['channel'];
            $allAssets = \App\Services\Analytics\KpiFormBuilder::getAssetOptionsForChannel($runtimeChannel);
            
            $configuredAssets = $dashboardControls['assets'];
            
            // For public dashboards, we just show all configured assets since there is no user
            foreach ($configuredAssets as $assetId) {
                if (isset($allAssets[$assetId])) {
                    $runtimeAssets[$assetId] = $allAssets[$assetId];
                }
            }
        }

        return view('shared.public-dashboard', [
            'dashboard' => $dashboard,
            'project' => $project,
            'widgets' => $widgets,
            'runtimeAssets' => $runtimeAssets,
            'runtimeChannel' => $runtimeChannel,
        ]);
    }
}
