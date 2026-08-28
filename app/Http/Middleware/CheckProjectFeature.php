<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Closure;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\Response;

class CheckProjectFeature
{
    /**
     * Map of route patterns to required features (feature => min version).
     * Patterns use Laravel route parameter syntax.
     */
    protected array $featureRoutes = [
        'filament.app.resources.alerts.*' => ['alerts' => '1.15.0'],
        // Add more feature gates here:
        // 'filament.app.resources.derived-metrics.*' => ['derived_metrics' => '1.16.0'],
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Only check on tenant-aware routes (app panel)
        $tenant = Filament::getTenant();
        
        if (!$tenant instanceof Project) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if (!$routeName) {
            return $next($request);
        }

        foreach ($this->featureRoutes as $pattern => $features) {
            if ($this->matchesPattern($routeName, $pattern)) {
                foreach ($features as $feature => $minVersion) {
                    if (!$this->supportsFeature($tenant, $feature, $minVersion)) {
                        return $this->redirectWithNotice($tenant, $feature, $minVersion);
                    }
                }
            }
        }

        return $next($request);
    }

    protected function matchesPattern(string $routeName, string $pattern): bool
    {
        // Convert wildcard pattern to regex
        $regex = preg_quote($pattern, '/');
        $regex = str_replace('\*', '.*', $regex);
        return (bool) preg_match("/^{$regex}$/", $routeName);
    }

    protected function supportsFeature(Project $project, string $feature, string $minVersion): bool
    {
        if (!$project->apisHubRelease) {
            return true;
        }

        $version = ltrim($project->apisHubRelease->version_tag, 'v');
        return version_compare($version, $minVersion, '>=');
    }

    protected function redirectWithNotice(Project $project, string $feature, string $minVersion): Response
    {
        $featureName = $this->getFeatureDisplayName($feature);

        Notification::make()
            ->title(__('Feature Unavailable'))
            ->body(__(':feature requires project version :version or higher. Your project is on :currentVersion.', [
                'feature' => $featureName,
                'version' => $minVersion,
                'currentVersion' => $project->apisHubRelease?->version_tag ?? 'unknown',
            ]))
            ->warning()
            ->persistent()
            ->send();

        return Redirect::route('filament.app.pages.dashboard', ['tenant' => $project->subdomain]);
    }

    protected function getFeatureDisplayName(string $feature): string
    {
        return match ($feature) {
            'alerts' => __('Alerts'),
            'derived_metrics' => __('Derived Metrics'),
            'custom_kpis' => __('Custom KPIs'),
            'dashboards' => __('Dashboards'),
            'data_sources' => __('Data Sources'),
            default => ucfirst(str_replace('_', ' ', $feature)),
        };
    }
}