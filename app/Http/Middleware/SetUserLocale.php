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
        if (auth()->check()) {
            $user = auth()->user();
            
            // If the user has a locale saved, enforce it.
            if (!empty($user->locale)) {
                App::setLocale($user->locale);
            }
        } else {
            // For guests, attempt to detect from browser
            $preferred = $request->getPreferredLanguage(['en', 'es']);
            if ($preferred) {
                App::setLocale($preferred);
            }
        }

        return $next($request);
    }
}
