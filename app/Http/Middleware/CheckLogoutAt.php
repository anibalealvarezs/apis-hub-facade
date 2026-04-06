<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckLogoutAt
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
        // 🛰️ RADAR PULSANDO (Verificación de existencia)
        \Illuminate\Support\Facades\Log::info('CENTINELA PULSANDO: ' . $request->getRequestUri());

        // 🚀 Dynamic User Detection
        $user = Auth::guard('web')->user() ?? Auth::user();
        
        if ($user) {
            $user = $user->fresh();
            
            if ($user && $user->logout_at) {
                $sessionCreatedAt = session()->get('session_start_time');
                $logoutAtTs = $user->logout_at->getTimestamp();

                \Illuminate\Support\Facades\Log::info('Centinela Radar: ' . $user->email, [
                    'should_logout' => (!$sessionCreatedAt || ($sessionCreatedAt < $logoutAtTs))
                ]);

                if (!$sessionCreatedAt || ($sessionCreatedAt < $logoutAtTs)) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    if ($request->header('X-Livewire') || $request->expectsJson()) {
                        return response('', 401)->header('X-Livewire-Redirect', route('filament.app.auth.login'));
                    }

                    return redirect()->route('filament.app.auth.login')
                        ->with('error', 'Your session has been terminated.');
                }
            }
        }

        return $next($request);
    }
}
