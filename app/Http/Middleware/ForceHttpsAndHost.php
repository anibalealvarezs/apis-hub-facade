<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class ForceHttpsAndHost
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (str_starts_with(config('app.url'), 'https://')) {
            URL::forceScheme('https');
            URL::forceRootUrl(config('app.url'));
            
            // Critical for Octane/Swoole/FPM: Force server variables per request
            $request->server->set('HTTPS', 'on');
            $request->server->set('HTTP_X_FORWARDED_PROTO', 'https');
            $request->server->set('SERVER_PORT', 443);
            
            // Match the Host from APP_URL to avoid signature mismatches due to ports (e.g., 8001)
            if ($host = parse_url(config('app.url'), PHP_URL_HOST)) {
                $request->headers->set('HOST', $host);
                $request->server->set('HTTP_HOST', $host);
            }

            if (config('app.debug')) {
                \Illuminate\Support\Facades\Log::info('Validating signature for URL: ' . $request->fullUrl());
            }
        }

        return $next($request);
    }
}
