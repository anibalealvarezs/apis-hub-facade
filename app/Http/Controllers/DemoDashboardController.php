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

        $assetGroups = $this->demoService->getAssetGroups();
        $channelAssetGroupMap = $this->demoService->getChannelAssetGroupMap();
        $dashboardDef = $this->demoService->getDashboardDefinition();

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

        $data = $this->demoService->getWidgetData($widget, $assetGroup, $lang);

        return response()->json($data, 200, [], JSON_UNESCAPED_UNICODE);
    }
}
