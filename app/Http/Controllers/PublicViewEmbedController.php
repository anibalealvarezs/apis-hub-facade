<?php

namespace App\Http\Controllers;

use App\Services\PublicViewService;
use Illuminate\Http\Request;

class PublicViewEmbedController extends Controller
{
    public function __construct(protected PublicViewService $pvService)
    {
    }

    public function script(Request $request, string $token)
    {
        $pv = $this->pvService->verifyToken($token);
        if (!$pv || !$pv->is_active || $pv->trashed()) {
            abort(404);
        }

        $dashboard = $pv->dashboard;
        if (!$dashboard || !$dashboard->is_public || $dashboard->trashed()) {
            abort(404);
        }

        $js = $this->pvService->getEmbedJs($pv);

        return response($js, 200)
            ->header('Content-Type', 'application/javascript; charset=utf-8');
    }
}
