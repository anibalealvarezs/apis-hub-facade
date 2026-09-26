<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LegalController extends Controller
{
    private function getViewData(): array
    {
        $gtmId = config('services.gtm.id');

        return [
            'portals' => [
                'app' => base64_encode('/app'),
                'admin' => base64_encode('/admin'),
                'docs' => base64_encode(config('services.docs.url', 'https://docs.apis-hub.cloud')),
            ],
            'gtmId' => ($gtmId && $gtmId !== 'GTM-XXXXXXX') ? $gtmId : null,
        ];
    }

    public function privacy(Request $request, $locale = null)
    {
        if ($locale === 'es' || $request->is('es/*')) {
            app()->setLocale('es');
            return view('legal.privacy-es', $this->getViewData());
        }
        app()->setLocale('en');
        return view('legal.privacy', $this->getViewData());
    }

    public function tos(Request $request, $locale = null)
    {
        if ($locale === 'es' || $request->is('es/*')) {
            app()->setLocale('es');
            return view('legal.tos-es', $this->getViewData());
        }
        app()->setLocale('en');
        return view('legal.tos', $this->getViewData());
    }

    public function dataDeletion(Request $request, $locale = null)
    {
        if ($locale === 'es' || $request->is('es/*')) {
            app()->setLocale('es');
            return view('legal.data-deletion-es', $this->getViewData());
        }
        app()->setLocale('en');
        return view('legal.data-deletion', $this->getViewData());
    }
}
