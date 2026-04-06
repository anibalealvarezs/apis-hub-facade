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

        if ($user && $user->logout_at) {
            $sessionCreatedAt = session()->get('session_start_time');

            // If the session was created before the logout_at timestamp, log out!
            if ($sessionCreatedAt && $sessionCreatedAt < $user->logout_at->getTimestamp()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('filament.app.auth.login')
                    ->with('error', 'Your session has been terminated by an administrator.');
            }
        }

        return $next($request);
    }
}
