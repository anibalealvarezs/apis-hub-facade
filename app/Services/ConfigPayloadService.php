<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ApisHubRelease;

class ConfigPayloadService
{
    /**
     * Build the configuration payload expected by the APIs Hub remote node.
     *
     * @param Project $tenant
     * @param ApisHubRelease $release
     * @param string $channel
     * @param array $channelConfig The channel's configuration state from the UI (or DB if called from a background job)
     * @param array $dbChannelConfig The pristine channel configuration from the DB (to preserve unmapped keys)
     * @return array|null Returns ['payload' => array, 'assetListKey' => string, 'remoteAssetKey' => string, 'assetsListDb' => array] or null if invalid schema
     */
    public function buildPayload(Project $tenant, ApisHubRelease $release, string $channel, array $channelConfig, array $dbChannelConfig = []): ?array
    {
        $remoteAssetKeyMap = [
            'google_search_console' => 'gsc',
            'facebook_marketing' => 'ad_accounts',
            'facebook_organic' => 'pages',
        ];

        $fields = $release->config_schemas[$channel]['fields'] ?? [];
        $assetListKey = null;

        // Dynamically find which field is the array of assets (e.g., 'ad_accounts', 'assets.sites')
        foreach ($fields as $key => $def) {
            if (($def['type'] ?? '') === 'array' && isset($def['item_schema'])) {
                $assetListKey = $key;
                break;
            } elseif (($def['type'] ?? '') === 'object' && isset($def['schema'])) {
                foreach ($def['schema'] as $subKey => $subDef) {
                    if (($subDef['type'] ?? '') === 'array' && isset($subDef['item_schema'])) {
                        $assetListKey = $key . '.' . $subKey;
                        break 2;
                    }
                }
            }
        }

        if (! $assetListKey) {
            return null; // No assets array found for this channel
        }

        $remoteAssetKey = $remoteAssetKeyMap[$channel] ?? 'assets';

        $payload = $channelConfig;
        $payload['type'] = $channel;

        // Map the correct 'enabled' state from the toggle name
        $payload['enabled'] = filter_var($channelConfig[$channel . '_enabled'] ?? $channelConfig['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
        unset($payload[$channel . '_enabled']);

        // Enforce Global Defaults for Jobs
        $payload['granular_sync'] = true;
        
        if ($channel === 'google_search_console') {
            $payload['max_workers'] = 4;
            if (isset($channelConfig['calculate_synthetics'])) {
                $payload['feature_toggles']['calculate_synthetics'] = filter_var($channelConfig['calculate_synthetics'], FILTER_VALIDATE_BOOLEAN);
            }
        } elseif ($channel === 'facebook_organic') {
            $payload['max_workers'] = 1;

            // Force global extraction granularity instructions for the worker cache
            $payload['feature_toggles'] = [
                'page_metrics' => true,
                'posts' => true,
                'post_metrics' => true,
                'ig_accounts' => true,
                'ig_account_metrics' => true,
                'ig_account_media' => true,
                'ig_account_media_metrics' => true,
            ];
        } elseif ($channel === 'facebook_marketing') {
            $payload['max_workers'] = 2;

            // Map Custom UI to APIs Hub Payload schema
            $entLevel = strtolower($channelConfig['entity_sync_depth'] ?? 'ad');
            $metLevel = strtolower($channelConfig['metrics_level'] ?? 'ad');

            if ($entLevel === 'account') {
                $entLevel = 'ad_account';
            }
            if ($metLevel === 'account') {
                $metLevel = 'ad_account';
            }

            $payload['feature_toggles'] = [
                'campaigns' => true, // API always expects this
                'adsets' => in_array($entLevel, ['adset', 'ad', 'creative']),
                'ads' => in_array($entLevel, ['ad', 'creative']),
                'creatives' => ($entLevel === 'creative'),

                'ad_account_metrics' => ($metLevel === 'ad_account'),
                'campaign_metrics' => ($metLevel === 'campaign'),
                'adset_metrics' => ($metLevel === 'adset'),
                'ad_metrics' => ($metLevel === 'ad'),
                'creative_metrics' => ($metLevel === 'creative'),
            ];

            $payload['metrics_strategy'] = 'default';
            $payload['metrics_config'] = [];

            $payload['entity_filters'] = [
                'CAMPAIGN' => $channelConfig['CAMPAIGN']['cache_include'] ?? '',
                'ADSET' => $channelConfig['ADSET']['cache_include'] ?? '',
                'AD' => $channelConfig['AD']['cache_include'] ?? '',
                'CREATIVE' => $channelConfig['CREATIVE']['cache_include'] ?? '',
            ];

            unset($payload['entity_sync_depth']);
            unset($payload['metrics_level']);
        }

        // ENFORCE FREE TIER CONSTRAINT RULES:
        $isFree = $tenant->billingProfile?->tier === \App\Enums\UserTier::FREE;
        if ($isFree) {
            // 1. Capping workers to a maximum of 1
            $payload['max_workers'] = 1;

            // 2. Disabling synthetic GSC calculations
            $payload['calculate_synthetics'] = false;
            if (isset($payload['google_search_console'])) {
                $payload['google_search_console']['calculate_synthetics'] = false;
            }

            // 3. Capping historical sync range to a maximum of 6 months
            $requestedRange = $payload['cache_history_range'] ?? '16 months';
            $months = match ($requestedRange) {
                '1 month' => 1,
                '3 months' => 3,
                '6 months' => 6,
                '1 year' => 12,
                '16 months' => 16,
                '2 years' => 24,
                default => 16,
            };
            if ($months > 6) {
                $payload['cache_history_range'] = '6 months';
            }
        }

        // Merge UI boolean toggles back into the pristine DB state to preserve unmapped keys (id, url, data)
        $assetsListUi = array_values(\Illuminate\Support\Arr::get($channelConfig, $assetListKey, []));
        $assetsListDb = array_values(\Illuminate\Support\Arr::get($dbChannelConfig, $assetListKey, []));

        // If the db config is empty (e.g. first time), we just use the UI config
        if (empty($assetsListDb)) {
            $assetsListDb = $assetsListUi;
        } else {
            $newAssetsList = [];
            foreach ($assetsListUi as $index => $uiAsset) {
                $uiId = $uiAsset['id'] ?? $uiAsset['url'] ?? null;
                $dbAsset = null;
                
                // Match by ID/URL if possible, otherwise by index
                if ($uiId) {
                    foreach ($assetsListDb as $dbA) {
                        if (($dbA['id'] ?? $dbA['url'] ?? null) === $uiId) {
                            $dbAsset = $dbA;
                            break;
                        }
                    }
                } else {
                    $dbAsset = $assetsListDb[$index] ?? null;
                }

                if ($dbAsset) {
                    // Merge UI into DB. UI takes precedence for submitted keys (name, toggles),
                    // DB preserves unmapped keys (deep tokens, raw data)
                    $newAssetsList[] = array_merge($dbAsset, $uiAsset);
                } else {
                    // Completely new asset added from UI
                    $newAssetsList[] = $uiAsset;
                }
            }
            $assetsListDb = $newAssetsList;
        }

        // Clean up the top-level list to avoid duplicate or conflicting structures
        \Illuminate\Support\Arr::forget($payload, $assetListKey);

        // Re-map the pristine assets list to the nested structure the backend drivers expect
        $payload['assets'] = [
            $remoteAssetKey => $assetsListDb,
        ];

        return [
            'payload' => $payload,
            'assetListKey' => $assetListKey,
            'remoteAssetKey' => $remoteAssetKey,
            'assetsListDb' => $assetsListDb,
        ];
    }
}
