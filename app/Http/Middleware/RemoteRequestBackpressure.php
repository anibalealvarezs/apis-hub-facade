<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RemoteRequestBackpressure
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $capacity = $request->attributes->get('remote_request_backpressure');
        if (!$capacity || (!$request->is('api/*') && !$request->expectsJson())) return $response;
        return response()->json($capacity + ['error' => $capacity['message']], $capacity['http_status'])
            ->header('Retry-After', (string)$capacity['retry_after']);
    }
}
