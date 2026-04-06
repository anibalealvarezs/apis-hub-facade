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
        // Get fresh user from DB to avoid Octane model caching issues
        $user = Auth::user()?->fresh();

        if ($user) {
            \Illuminate\Support\Facades\Log::info('CheckLogoutAt for user: ' . $user->email, [
                'is_active' => $user->is_active,
                'logout_at' => $user->logout_at?->toDateTimeString(),
                'session_start' => session()->get('session_start_time'),
            ]);

            // 🚨 Kick out inactive users immediately
            if (isset($user->is_active) && !$user->is_active) {
                \Illuminate\Support\Facades\Log::info('Force Logout: User is inactive');
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

                \Illuminate\Support\Facades\Log::info('Checking Invalidation:', [
                    'session_start' => $sessionCreatedAt,
                    'logout_at_ts' => $logoutAtTimestamp,
                    'should_logout' => (!$sessionCreatedAt || ($sessionCreatedAt < $logoutAtTimestamp))
                ]);

                // If session_start_time is missing (it's an OLD session) or older than logout_at -> LOGOUT!
                if (!$sessionCreatedAt || ($sessionCreatedAt < $logoutAtTimestamp)) {
                    \Illuminate\Support\Facades\Log::info('Force Logout: Condition Met');
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
