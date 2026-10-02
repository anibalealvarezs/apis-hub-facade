<?php

namespace App\Domain\ChannelProfiles;

use App\Domain\ChannelProfiles\Contracts\ChannelProfileInterface;

abstract class AbstractChannelProfile implements ChannelProfileInterface
{
    /**
     * Helper to define a user-configurable schema field.
     */
    protected function configurableField(string $type, $default, array $options = null, array $extra = []): array
    {
        $field = [
            'type' => $type,
            'default' => $default,
            'user_configurable' => true,
        ];

        if ($options !== null) {
            $field['options'] = $options;
        }

        return array_merge($field, $extra);
    }

    /**
     * Helper to define a fixed system field that the user cannot edit.
     */
    protected function systemField(string $type, $default, array $extra = []): array
    {
        return array_merge([
            'type' => $type,
            'default' => $default,
            'user_configurable' => false,
        ], $extra);
    }

    /**
     * Determine if this channel is connected for the given project.
     * Checks ChannelProfile (OAuth), ProjectCredential, or sync_config accounts.
     */
    public function isConnected(\App\Models\Project $project): bool
    {
        $channelKey = $this->getChannelKey();
        $provider = explode('_', $channelKey)[0];

        // 1. ChannelProfile check (Facebook, Google, etc.)
        $profileIdColumn = "{$provider}_profile_id";
        if (!empty($project->{$profileIdColumn})) {
            $profile = $project->getRelationValue($provider . 'Profile')
                ?? \App\Models\ChannelProfile::find($project->{$profileIdColumn});

            if ($profile) {
                if (is_array($profile->authorized_channels)) {
                    return in_array($channelKey, $profile->authorized_channels, true) && !empty($profile->access_token);
                }

                return !empty($profile->access_token);
            }
        }

        // Legacy fallback for Facebook / Google before profile column was linked
        if ($provider === 'facebook' && $project->facebook_user_id !== null && !empty($project->facebook_user_token)) {
            return true;
        }
        if ($provider === 'google' && $project->google_user_id !== null && !empty($project->google_refresh_token)) {
            return true;
        }

        // 2. ProjectCredential check (tokens stored in database credentials table)
        if ($project->credentials()->where('provider', $provider)->whereNotNull('token')->exists()) {
            return true;
        }

        // 3. sync_config accounts check (API keys, multi-account providers like Mailchimp)
        $accounts = $project->sync_config[$channelKey]['accounts'] ?? [];
        if (is_array($accounts) && !empty($accounts)) {
            foreach ($accounts as $acc) {
                if (!empty($acc['api_key']) || !empty($acc['access_token']) || !empty($acc['token'])) {
                    return true;
                }
            }
        }

        return false;
    }
}
