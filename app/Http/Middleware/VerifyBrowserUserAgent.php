<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyBrowserUserAgent
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.public_view_skip_ua_check', false)) {
            return $next($request);
        }

        $userAgent = $request->userAgent() ?? '';

        if (empty($userAgent)) {
            return response('', 403);
        }

        $deniedKeywords = [
            'googlebot',
            'bingbot',
            'slurp',
            'duckduckbot',
            'baiduspider',
            'yandexbot',
            'sogou',
            'exabot',
            'facebookexternalhit',
            'twitterbot',
            'rogerbot',
            'linkedinbot',
            'embedly',
            'quora link preview',
            'showyouhavebot',
            'outbrain',
            'pinterest/0.',
            'developers.google.com/+/web/snippet',
            'slackbot',
            'vkshare',
            'w3c_validator',
            'redditbot',
            'applebot',
            'whatsapp',
            'flipboard',
            'tumblr',
            'bitlybot',
            'skypeuripreview',
            'nuzzel',
            'discordbot',
            'google page speed',
            'qwantify',
            'bitrix link preview',
            'xxfetchedoptengraph',
            'telegrambot',
            'ahrefsbot',
            'semrushbot',
            'mj12bot',
            'dotbot',
            'curl',
            'python-requests',
            'wget',
            'go-http-client',
            'java/',
            'headlesschrome',
        ];

        $uaLower = strtolower($userAgent);
        foreach ($deniedKeywords as $keyword) {
            if (str_contains($uaLower, $keyword)) {
                return response('', 403);
            }
        }

        return $next($request);
    }
}
