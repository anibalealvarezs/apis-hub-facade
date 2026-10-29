<?php

namespace App\Services;

use App\Models\ApisHubRelease;
use App\Models\Project;

class UpgradeAdvisory
{
    public function describe(Project $project, ?ApisHubRelease $target): string
    {
        if (!$project->hasBeenDeployed()) return __('The new version will be used when this project is first deployed.');
        $parts = [__('Your dashboards and synchronization may be unavailable during the upgrade.')];
        $current = ltrim($project->apisHubRelease?->version_tag ?? '0', 'v');
        $rules = $target?->upgrade_impact ?? [];
        if ($target?->exists) {
            foreach (ApisHubRelease::whereNotNull('upgrade_impact')->get(['version_tag', 'upgrade_impact']) as $release) {
                if (version_compare(ltrim($release->version_tag, 'v'), $current, '>') && version_compare(ltrim($release->version_tag, 'v'), ltrim($target->version_tag, 'v'), '<=')) {
                    $rules = array_merge($rules, $release->upgrade_impact ?? []);
                }
            }
        }
        // Also covers older release registrations created before impact metadata existed.
        if ($target && version_compare(ltrim($target->version_tag, 'v'), '1.18.3', '>=')) {
            $rules[] = ['introduced_in' => '1.18.3', 'channel' => 'google_search_console', 'label' => 'Google Search Console'];
        }
        $seen = [];
        foreach ($rules as $rule) {
            if (!isset($rule['introduced_in'], $rule['channel'], $rule['label'])) continue;
            if (version_compare($current, $rule['introduced_in'], '>=')) continue;
            if ($target && version_compare($rule['introduced_in'], ltrim($target->version_tag, 'v'), '>')) continue;
            $key = $rule['introduced_in'].':'.$rule['channel'];
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $configured = array_key_exists($rule['channel'], $project->sync_config ?? []);
            $reported = array_key_exists($rule['channel'], $project->health_metrics['channels'] ?? []);
            if ($configured || $reported) {
                $parts[] = __('This release reorganizes cached data for :channel. Large histories or many duplicate records can make this upgrade take more than an hour.', ['channel' => $rule['label']]);
            } else {
                $parts[] = __('If this project contains previously cached data for :channel, reorganizing that history can take more than an hour.', ['channel' => $rule['label']]);
            }
        }
        $catalog = $project->health_metrics['catalog'] ?? [];
        if ((int)($catalog['queries'] ?? 0) >= 50000 || (int)($catalog['pages'] ?? 0) >= 10000) {
            $parts[] = __('The latest health report indicates a large amount of saved data. Allow extra maintenance time.');
        }
        $parts[] = __('Schedule upgrades outside synchronization hours and during low-activity periods. Allow additional time for large cached histories; a completion time cannot be guaranteed.');
        return implode(' ', $parts);
    }
}
