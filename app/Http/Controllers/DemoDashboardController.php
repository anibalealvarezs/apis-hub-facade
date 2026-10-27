<?php

namespace App\Http\Controllers;

use App\Services\DemoDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DemoDashboardController extends Controller
{
    public function __construct(
        protected DemoDashboardService $demoService
    ) {}

    /**
     * Display the public interactive demo dashboard.
     */
    public function show(Request $request, ?string $locale = null)
    {
        $lang = $locale ?? $request->query('lang') ?? $request->cookie('pv_lang') ?? 'es';
        if (!in_array($lang, ['en', 'es'])) {
            $lang = 'es';
        }
        app()->setLocale($lang);

        $assetGroups = $this->demoService->getAssetGroups($lang);
        $channelAssetGroupMap = $this->demoService->getChannelAssetGroupMap();
        $dashboardDef = $this->demoService->getDashboardDefinition($lang);

        $dashboard = (object) [
            'id' => 9999,
            'name' => $dashboardDef['name'][$lang] ?? $dashboardDef['name']['es'],
            'description' => $dashboardDef['description'][$lang] ?? $dashboardDef['description']['es'],
            'controls' => $dashboardDef['controls'],
        ];

        $widgets = $dashboardDef['widgets'];

        return view('demo.show', [
            'dashboard' => $dashboard,
            'widgets' => $widgets,
            'assetGroups' => $assetGroups,
            'channelAssetGroupMap' => $channelAssetGroupMap,
            'currentLang' => $lang,
            'isDemo' => true,
            'currentStep' => 3,
        ]);
    }

    /**
     * Display Step 1: Data Sources demo page.
     */
    public function dataSources(Request $request, ?string $locale = null)
    {
        $lang = $locale ?? $request->query('lang') ?? $request->cookie('pv_lang') ?? 'es';
        if (!in_array($lang, ['en', 'es'])) {
            $lang = 'es';
        }
        app()->setLocale($lang);

        $demoData = $this->demoService->getDemoDataSourcesData($lang);

        return view('demo.data-sources', [
            'currentLang' => $lang,
            'providers' => $demoData['providers'],
            'stats' => $demoData['stats'],
            'isDemo' => true,
            'currentStep' => 1,
        ]);
    }

    /**
     * Display Step 2: Asset Groups demo page.
     */
    public function assetGroups(Request $request, ?string $locale = null)
    {
        $lang = $locale ?? $request->query('lang') ?? $request->cookie('pv_lang') ?? 'es';
        if (!in_array($lang, ['en', 'es'])) {
            $lang = 'es';
        }
        app()->setLocale($lang);

        $assetGroupsList = $this->demoService->getDemoAssetGroupsList($lang);

        return view('demo.asset-groups', [
            'currentLang' => $lang,
            'assetGroupsList' => $assetGroupsList,
            'isDemo' => true,
            'currentStep' => 2,
        ]);
    }

    /**
     * Display Step 4: Data Intelligence demo page (Alerts, API & MCP Server).
     */
    public function intelligence(Request $request, ?string $locale = null)
    {
        $lang = $locale ?? $request->query('lang') ?? $request->cookie('pv_lang') ?? 'es';
        if (!in_array($lang, ['en', 'es'])) {
            $lang = 'es';
        }
        app()->setLocale($lang);

        $intelligenceData = $this->demoService->getDemoIntelligenceData($lang);

        return view('demo.intelligence', [
            'currentLang' => $lang,
            'alerts' => $intelligenceData['alerts'],
            'apiEndpoints' => $intelligenceData['api_endpoints'],
            'mcpTools' => $intelligenceData['mcp_tools'],
            'isDemo' => true,
            'currentStep' => 4,
        ]);
    }

    /**
     * Display Step 5: Team Collaboration and Shareable Projects demo page.
     */
    public function collaboration(Request $request, ?string $locale = null)
    {
        $lang = $locale ?? $request->query('lang') ?? $request->cookie('pv_lang') ?? 'es';
        if (!in_array($lang, ['en', 'es'])) {
            $lang = 'es';
        }
        app()->setLocale($lang);

        $collabData = $this->demoService->getDemoCollaborationData($lang);

        return view('demo.collaboration', [
            'currentLang' => $lang,
            'project' => $collabData['project'],
            'collaborators' => $collabData['collaborators'],
            'shareableDashboards' => $collabData['shareable_dashboards'],
            'isDemo' => true,
            'currentStep' => 5,
        ]);
    }

    /**
     * Provide instant mock data for demo widgets.
     */
    public function widgetData(Request $request, int $widget): JsonResponse
    {
        $lang = $request->query('lang') ?? app()->getLocale() ?? 'es';
        $controls = $request->input('controls', []);

        // Resolve asset group: either from controls.asset_group or default to '1'
        $assetGroup = '1';
        if (!empty($controls['asset_group'])) {
            $raw = is_array($controls['asset_group']) ? reset($controls['asset_group']) : $controls['asset_group'];
            if (in_array((string) $raw, ['1', '2', '3'], true)) {
                $assetGroup = (string) $raw;
            }
        }

        $dateStart = $controls['date_start'] ?? $request->query('date_start');
        $dateEnd = $controls['date_end'] ?? $request->query('date_end');
        $granularity = $controls['granularity'] ?? $request->query('granularity');

        $data = $this->demoService->getWidgetData($widget, $assetGroup, $lang, $dateStart, $dateEnd, $granularity);

        return response()->json($data, 200, [], JSON_UNESCAPED_UNICODE);
    }
}
