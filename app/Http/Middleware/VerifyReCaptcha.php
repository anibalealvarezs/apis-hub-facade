<?php

namespace App\Http\Middleware;

use Anibalealvarezs\GoogleApi\Services\Recaptcha\RecaptchaApi;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyReCaptcha
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): (Response) $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 0. Only process POST requests (form submissions)
        if (!$request->isMethod('POST')) {
            return $next($request);
        }

        // 1. Check if reCAPTCHA is enabled
        $siteKey = config('services.recaptcha.site_key');
        $projectId = config('services.recaptcha.project_id');
        $apiKey = config('services.recaptcha.api_key');

        if (!$siteKey || !$projectId || !$apiKey) {
            return $next($request);
        }

        // 2. Validate token from request
        $token = $request->input('g-recaptcha-response');

        if (!$token) {
            return redirect()->back()
                ->withErrors(['g-recaptcha-response' => 'reCAPTCHA verification is required.'])
                ->withInput();
        }

        try {
            // 3. Initialize SDK
            $api = new RecaptchaApi(
                projectId: $projectId,
                apiKey: $apiKey
            );

            // 4. Determine expected action based on route
            $expectedAction = $request->routeIs('filament.app.auth.register') ? 'register' : 'login';

            // 5. Verify token with SDK (Enterprise Assessment)
            $isValid = $api->verifyToken(
                token: $token,
                siteKey: $siteKey,
                expectedAction: $expectedAction,
                threshold: 0.3
            );

            if (!$isValid) {
                return redirect()->back()
                    ->withErrors(['g-recaptcha-response' => 'Security verification failed. Please try again.'])
                    ->withInput();
            }

        } catch (Exception $e) {
            // Log the error but allow the request if it's a connectivity/API issue to avoid blocking users
            report($e);
        }

        return $next($request);
    }
}
