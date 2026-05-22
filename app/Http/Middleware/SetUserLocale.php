<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\App;

class SetUserLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $sessionLocale = session()->get('locale');

        if (auth()->check()) {
            $user = auth()->user();
            
            if ($sessionLocale && $sessionLocale !== $user->locale) {
                // User changed language via Filament Language Switch UI
                $user->update(['locale' => $sessionLocale]);
            } elseif (!$sessionLocale && $user->locale) {
                // First login on new device, no session locale yet, restore DB preference
                session()->put('locale', $user->locale);
                App::setLocale($user->locale);
            } elseif ($user->locale) {
                // Keep app locale in sync just in case
                App::setLocale($user->locale);
            }
        } else {
            // For guests, attempt to detect from browser if session is empty
            if (!$sessionLocale) {
                $preferred = $request->getPreferredLanguage(['en', 'es']);
                if ($preferred) {
                    session()->put('locale', $preferred);
                    App::setLocale($preferred);
                }
            } else {
                App::setLocale($sessionLocale);
            }
        }

        return $next($request);
    }
}
