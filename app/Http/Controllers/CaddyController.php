<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CaddyController extends Controller
{
    /**
     * Check if a domain/subdomain is allowed for On-Demand TLS.
     * Caddy will hit this endpoint with a 'domain' query parameter.
     */
    public function check(Request $request): Response
    {
        $domain = $request->query('domain');

        if (!$domain) {
            return response('No domain provided', 400);
        }

        // Assuming your setup is client1.yourdomain.com
        // We'll extract the subdomain part
        $subdomain = explode('.', $domain)[0];

        $projectExists = Project::where('subdomain', $subdomain)
            ->where('is_active', true)
            ->exists();

        if ($projectExists) {
            return response('OK', 200);
        }

        return response('Forbidden', 403);
    }
}
