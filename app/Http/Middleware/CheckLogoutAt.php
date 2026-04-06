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
        $user = Auth::user();

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

                // If session_start_time is missing (it's an OLD session) or older than logout_at -> LOGOUT!
                if (!$sessionCreatedAt || ($sessionCreatedAt < $user->logout_at->getTimestamp())) {
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
