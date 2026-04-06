<?php

namespace App\Http\Middleware;

use Anibalealvarezs\GoogleApi\Services\Recaptcha\RecaptchaApi;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        // 🚨 SESSION SECURITY GUARD (Force Logout Check)
        $user = Auth::guard('web')->user() ?? Auth::user();
        if ($user) {
            $user = $user->fresh();
            if ($user && $user->logout_at) {
                $sessionCreatedAt = session()->get('session_start_time');
                $logoutAtTs = $user->logout_at->getTimestamp();
                
                \Illuminate\Support\Facades\Log::info('Centinela (via ReCaptcha) Radar: ' . $user->email, [
                    'uri' => $request->getRequestUri(),
                    'should_logout' => (!$sessionCreatedAt || ($sessionCreatedAt < $logoutAtTs))
                ]);

                if (!$sessionCreatedAt || ($sessionCreatedAt < $logoutAtTs)) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    return redirect()->route('filament.app.auth.login')
                        ->with('error', 'Your session has been expired.');
                }
            }
        }

        // 0. Only process POST requests (form submissions) for reCAPTCHA
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
        $token = $request->input('recaptcha_token');

        if (!$token) {
            return redirect()->back()
                ->withErrors(['recaptcha_token' => 'reCAPTCHA verification is required.'])
                ->withInput();
        }

        try {
            // 3. Initialize SDK via App Container to allow Mocking in tests
            $api = app(RecaptchaApi::class, [
                'projectId' => $projectId,
                'apiKey' => $apiKey
            ]);

            // 4. Determine expected action based on route
            $expectedAction = match (true) {
                $request->routeIs('filament.app.auth.register') => 'register',
                $request->routeIs('landing.subscribe') => 'subscribe',
                default => 'login',
            };

            // 5. Verify token with SDK (Enterprise Assessment)
            $isValid = $api->verifyToken(
                token: $token,
                siteKey: $siteKey,
                expectedAction: $expectedAction,
                threshold: 0.3
            );

            if (!$isValid) {
                return redirect()->back()
                    ->withErrors(['recaptcha_token' => 'Security verification failed. Please try again.'])
                    ->withInput();
            }

        } catch (Exception $e) {
            // Log the error but allow the request if it's a connectivity/API issue to avoid blocking users
            report($e);
        }

        return $next($request);
    }
}
