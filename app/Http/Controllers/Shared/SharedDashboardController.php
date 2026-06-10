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

        return view('shared.public-dashboard', [
            'dashboard' => $dashboard,
            'project' => $project,
            'widgets' => $widgets,
        ]);
    }
}
