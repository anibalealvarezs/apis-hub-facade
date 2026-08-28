<?php

namespace App\Traits;

use App\Models\Project;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Redirect;

trait HasFeatureGate
{
    /**
     * The features required to access this resource/page.
     * Key = feature name, Value = minimum version (semver).
     */
    protected array $requiredFeatures = [];

    /**
     * Check if the current project supports all required features.
     */
    protected function checkFeatures(): bool
    {
        $project = Filament::getTenant();

        if (!$project instanceof Project) {
            return true;
        }

        foreach ($this->requiredFeatures as $feature => $minVersion) {
            if (!$this->supportsFeature($project, $feature, $minVersion)) {
                $this->redirectWithFeatureNotice($project, $feature, $minVersion);
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a project supports a specific feature at the given version.
     */
    protected function supportsFeature(Project $project, string $feature, string $minVersion): bool
    {
        if (!$project->apisHubRelease) {
            return true;
        }

        $version = ltrim($project->apisHubRelease->version_tag, 'v');
        return version_compare($version, $minVersion, '>=');
    }

    /**
     * Redirect to dashboard with a feature unavailable notification.
     */
    protected function redirectWithFeatureNotice(Project $project, string $feature, string $minVersion): void
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

        $redirectUrl = route('filament.app.pages.dashboard', ['tenant' => $project->subdomain]);

        // If we're in a Filament page context, use redirect()
        // Otherwise use the global redirect helper
        if (method_exists($this, 'redirect')) {
            $this->redirect($redirectUrl);
        } else {
            Redirect::to($redirectUrl)->send();
        }
    }

    /**
     * Get human-readable feature name for notifications.
     */
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