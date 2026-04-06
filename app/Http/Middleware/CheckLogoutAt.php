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
        // 🚀 Dynamic User Detection (Octane-aware)
        $user = Auth::guard('web')->user() ?? Auth::user();
        
        if ($user instanceof \Illuminate\Database\Eloquent\Model) {
            $user = $user->fresh();
            
            if ($user && $user->logout_at) {
                $sessionCreatedAt = session()->get('session_start_time');
                $logoutAtTs = $user->logout_at->getTimestamp();

                \Illuminate\Support\Facades\Log::info('Centinela Check: ' . $user->email, [
                    'uri' => $request->getRequestUri(),
                    'should_logout' => (!$sessionCreatedAt || ($sessionCreatedAt < $logoutAtTs))
                ]);

                if (!$sessionCreatedAt || ($sessionCreatedAt < $logoutAtTs)) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    // 🚨 Livewire/AJAX Redirect (The missing piece)
                    if ($request->header('X-Livewire') || $request->expectsJson()) {
                        return response('', 401)->header('X-Livewire-Redirect', route('filament.app.auth.login'));
                    }

                    return redirect()->route('filament.app.auth.login')
                        ->with('error', 'Your session has been terminated by an administrator.');
                }
            }
        }

        return $next($request);
    }
}
