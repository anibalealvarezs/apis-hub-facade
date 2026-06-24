<?php

namespace App\Services;

use App\Models\ApisHubRelease;
use App\Models\Project;

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
            'google_analytics' => 'ga',
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
        } elseif ($channel === 'google_analytics') {
            $payload['max_workers'] = 3;

            $payload['feature_toggles'] = [
                'cache_aggregations' => false,
            ];
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

            // Force extraction granularity to Ad Level (Level 4)
            $entLevel = 'ad';
            $metLevel = 'ad';

            $payload['feature_toggles'] = [
                'campaigns' => true, // API always expects this
                'adsets' => true,
                'ads' => true,
                'creatives' => false,

                'ad_account_metrics' => false,
                'campaign_metrics' => false,
                'adset_metrics' => false,
                'ad_metrics' => true,
                'creative_metrics' => false,
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

        // FORCE CACHE HISTORY RANGE UNCONDITIONALLY
        $maxRanges = [
            'google_search_console' => '16 months',
            'google_analytics'      => '2 years',
            'facebook_marketing'    => '2 years',
            'facebook_organic'      => '2 years',
        ];
        $payload['cache_history_range'] = $maxRanges[$channel] ?? '1 year';

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
            $payload['cache_history_range'] = '6 months';
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
                $uiId = $uiAsset['id'] ?? $uiAsset['url'] ?? $uiAsset['platformId'] ?? null;
                $dbAsset = null;

                // Match by ID/URL if possible, otherwise by index
                if ($uiId) {
                    foreach ($assetsListDb as $dbA) {
                        if (($dbA['id'] ?? $dbA['url'] ?? $dbA['platformId'] ?? null) === $uiId) {
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

                    // Filter out nulls and empty arrays from the UI asset since Hidden fields
                    // may fail to hydrate arrays or strings and submit them as null or []
                    $uiAssetFiltered = array_filter($uiAsset, function ($value) {
                        return $value !== null && $value !== [];
                    });

                    $newAssetsList[] = array_merge($dbAsset, $uiAssetFiltered);
                } else {
                    // Completely new asset added from UI
                    $newAssetsList[] = $uiAsset;
                }
            }
            $assetsListDb = $newAssetsList;
        }

        // Filter out malformed/empty entries (e.g. YAML stubs '-' or null URLs).
        // The driver exits early and saves NOTHING if the incoming list is empty and type !== 'global'.
        $assetsListDb = array_values(array_filter($assetsListDb, function ($item) {
            $id = $item['url'] ?? $item['id'] ?? $item['platformId'] ?? null;
            return !empty($id) && $id !== '-';
        }));

        // Normalize: the driver calls getCleanString($sel['url']) and will crash if url is null.
        // The Facade stores GSC assets by 'id' (e.g. "sc-domain:...") but leaves url as null.
        // Populate url from id so the driver always receives a valid string.
        $assetsListDb = array_map(function ($item) {
            if (empty($item['url']) && !empty($item['id'])) {
                $item['url'] = $item['id'];
            }
            if (empty($item['url']) && !empty($item['platformId'])) {
                $item['url'] = $item['platformId'];
            }
            return $item;
        }, $assetsListDb);

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
