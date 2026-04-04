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

        // Always allow the main domains
        if (in_array($domain, ['apis-hub.cloud', 'dev.apis-hub.cloud'])) {
            return response('OK', 200);
        }

        // Allow project subdomains
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
