<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiDocsController extends Controller
{
    /**
     * Show the public API documentation developer portal.
     */
    public function show(Request $request, ?string $locale = null)
    {
        $host = $request->getHost();
        $mainDomain = parse_url(config('app.url'), PHP_URL_HOST) ?? config('app.network_domain', 'apis-hub.cloud');

        if ($host !== $mainDomain && $host !== "www.{$mainDomain}") {
            $subdomain = explode('.', $host)[0];
            $projectExists = \App\Models\Project::where('subdomain', $subdomain)->exists();
            if (!$projectExists) {
                return redirect()->away("https://{$mainDomain}");
            }
        }

        if ($locale === 'es' || $request->is('es/*')) {
            app()->setLocale('es');
            session()->put('locale', 'es');
        } else {
            app()->setLocale('en');
            session()->put('locale', 'en');
        }

        $gtmId = config('services.gtm.id');

        $specUrl = (app()->getLocale() === 'es') ? route('docs.api.spec.es') : route('docs.api.spec');

        return view('docs.api-docs', [
            'portals' => [
                'app' => base64_encode('/app'),
                'admin' => base64_encode('/admin'),
                'docs' => base64_encode(app()->getLocale() === 'es' ? '/es/docs/api' : '/docs/api'),
            ],
            'gtmId' => ($gtmId && $gtmId !== 'GTM-XXXXXXX') ? $gtmId : null,
            'specUrl' => $specUrl,
        ]);
    }

    /**
     * Return the sanitized OpenAPI 3.1 JSON specification.
     */
    public function spec(\App\Services\OpenApiSpecificationService $specService, ?string $locale = null): \Illuminate\Http\JsonResponse
    {
        $targetLocale = $locale ?? (request()->is('es/*') ? 'es' : app()->getLocale());
        
        return response()->json($specService->buildSpecification($targetLocale), 200, [
            'Access-Control-Allow-Origin' => '*',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
