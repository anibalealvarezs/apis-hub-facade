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
     */
    public function handle(Request $request, Closure $next): Response
    {
        dd('CENTINELA RECIBIÓ: ' . $request->getRequestUri());

        // Force refresh user from DB to handle Octane caching
        $user = Auth::guard('web')->user() ?? Auth::user();
        
        if ($user instanceof \Illuminate\Database\Eloquent\Model) {
            $user = $user->fresh();
        }

        if ($user) {
            // 🚨 Kick out inactive users immediately
            if (isset($user->is_active) && !$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('filament.app.auth.login')
                    ->with('error', 'Your account has been deactivated.');
            }

            // 🕒 Check session invalidation timestamp
            if ($user->logout_at) {
                $sessionCreatedAt = session()->get('session_start_time');
                $logoutAtTimestamp = $user->logout_at->getTimestamp();

                // If session_start_time is missing (pre-system session) or older than logout_at -> LOGOUT!
                if (!$sessionCreatedAt || ($sessionCreatedAt < $logoutAtTimestamp)) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect()->route('filament.app.auth.login')
                        ->with('error', 'Your session has been terminated by an administrator.');
                }
            }
        }

        return $next($request);
    }
}
