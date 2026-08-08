<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(
            \App\Http\Middleware\ForceHttps::class
        );
        $middleware->web(append: [
            \App\Http\Middleware\SetUserLocale::class,
        ]);

        $middleware->alias([
            'channel.asset.access' => \App\Http\Middleware\ScopeChannelAssetAccess::class,
        ]);
        
        $middleware->trustProxies(at: '*', headers: \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_FOR | \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_HOST | \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_PORT | \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_PROTO);
        $middleware->validateCsrfTokens(except: [
            'api/heartbeat',
            'api/channels/auth-failed',
            'api/token-authority/refresh',
            'api/v1/tokens/refresh',
            'webhooks/paypal',
            'api/dashboard/widget/*/data',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if (!config('app.debug') && ($request->is('api/*') || $request->expectsJson())) {
                return response()->json([
                    'status' => 'error',
                    'error' => 'Internal Server Error',
                    'message' => 'An unexpected error occurred. Please contact support.',
                ], 500);
            }
        });
    })->create();

