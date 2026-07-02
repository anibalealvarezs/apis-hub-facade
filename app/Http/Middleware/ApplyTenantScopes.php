<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyTenantScopes
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Filament::getTenant();

        if ($tenant) {
            if (function_exists('setPermissionsTeamId')) {
                setPermissionsTeamId($tenant->id);
                app(\Spatie\Permission\PermissionRegistrar::class)->clearClassPermissions();
            }

            $lastTenantId = session()->get('current_tenant_id');

            if ($request->isMethod('GET') && $lastTenantId && $lastTenantId !== $tenant->id) {
                // Flash confirmation notification
                \Filament\Notifications\Notification::make()
                    ->title("Switched to {$tenant->name}")
                    ->success()
                    ->send();

                // Handle redirect to same relative page
                $referer = $request->headers->get('referer');
                if ($referer) {
                    $parsedReferer = parse_url($referer);
                    if (($parsedReferer['host'] ?? '') === $request->getHost()) {
                        $refererPath = $parsedReferer['path'] ?? '';

                        // Match paths like /app/mabe-prueba-dev/telemetry
                        if (preg_match('#^/app/([^/]+)/(.*)$#', $refererPath, $matches)) {
                            $relativeUrl = $matches[2];

                            if ($relativeUrl !== '') {
                                // Add query string back if it existed
                                if (isset($parsedReferer['query'])) {
                                    $relativeUrl .= '?' . $parsedReferer['query'];
                                }

                                // Check if the path contains a numeric ID or UUID (e.g. /dashboards/123/edit)
                                // If it does, it's a record-specific page that likely doesn't exist in the new tenant.
                                // In this case, we fallback to the new tenant's dashboard root.
                                $hasId = preg_match('#/(?:\d+|[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12})(?:/|$)#', '/' . $relativeUrl);

                                // If landing on the dashboard root for the new tenant, intercept and redirect
                                if ($request->path() === 'app/' . $tenant->subdomain) {
                                    session()->put('current_tenant_id', $tenant->id);
                                    
                                    if ($hasId) {
                                        return redirect('/app/' . $tenant->subdomain); // Fallback
                                    }
                                    
                                    return redirect('/app/' . $tenant->subdomain . '/' . $relativeUrl);
                                }
                            }
                        }
                    }
                }
            }

            if ($request->isMethod('GET')) {
                session()->put('current_tenant_id', $tenant->id);
            }
        }

        return $next($request);
    }
}
