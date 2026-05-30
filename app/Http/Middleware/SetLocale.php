<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // First check if user explicitly set a language in session
        if (session()->has('locale')) {
            App::setLocale(session()->get('locale'));
        } else {
            // Otherwise automatically detect from browser
            $preferred = $request->getPreferredLanguage(['en', 'es']);
            App::setLocale($preferred);
        }

        return $next($request);
    }
}
