<?php

namespace App\Http\Controllers;

use App\Services\PublicViewService;
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

        $locale = $request->query('lang') ?? $request->cookie('pv_lang') ?? session('locale') ?? app()->getLocale();
        if (in_array($locale, ['en', 'es'])) {
            app()->setLocale($locale);
            cookie()->queue('pv_lang', $locale, 60 * 24 * 365);
        }

        app()->instance('current_public_project', $dashboard->project);

        $isEmbedded = $request->boolean('embedded');
        $viewModel = new PublicDashboardViewModel($pv, $isEmbedded);

        return view('public-view.show', [
            'viewModel' => $viewModel,
            'pv' => $pv,
            'dashboard' => $dashboard,
            'project' => $dashboard->project,
            'widgets' => $viewModel->widgets,
            'isEmbedded' => $isEmbedded,
        ]);
    }
}
