<?php

namespace App\Services;

use App\Models\Project;
use Anibalealvarezs\FacebookGraphApi\FacebookGraphApi;
use Anibalealvarezs\GoogleApi\Services\AnalyticsAdmin\AnalyticsAdminApi;
use Anibalealvarezs\GoogleApi\Services\GoogleAds\GoogleAdsApi;
use Anibalealvarezs\GoogleApi\Services\SearchConsole\SearchConsoleApi;
use App\Models\ApisHubRelease;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class LocalAssetDiscoveryService
{
    /**
     * Map channel names to their asset container key in discovery responses.
     */
    public const RESOURCE_KEY_MAP = [
        'google_search_console' => 'sites',
        'google_analytics'      => 'properties',
        'google_ads'            => 'ad_accounts',
        'facebook_marketing'    => 'ad_accounts',
        'facebook_organic'      => 'pages',
        'shopify'               => 'stores',
        'mailchimp'             => 'audiences',
    ];

    /**
     * Synchronize and merge discovered assets for a given channel directly into the project's sync_config.
     * Flag lost_access on assets no longer available and register new ones.
     */
    public function syncChannelAssets(Project $project, string $channel): array
    {
        try {
            $response = $this->fetchAssets($project, $channel);

            if (!isset($response['success']) || !$response['success'] || !isset($response['assets'])) {
                Log::warning("LocalAssetDiscoveryService: Failed to fetch assets for channel {$channel} on project {$project->id}: " . ($response['message'] ?? 'Unknown error'));
                return $response;
            }

            $resourceKey = self::RESOURCE_KEY_MAP[$channel] ?? $channel;
            $liveAssets = $response['assets'][$resourceKey] ?? [];

            // Fallback to first array found if mapped key is empty
            if (empty($liveAssets) && !empty($response['assets'])) {
                foreach ($response['assets'] as $key => $value) {
                    if (is_array($value)) {
                        $liveAssets = $value;
                        $resourceKey = $key;
                        break;
                    }
                }
            }

            $payload = [$resourceKey => $liveAssets];
            $updatedConfig = $this->mergeChannelAssets($project, $channel, $payload);

            return [
                'success' => true,
                'channel' => $channel,
                'count' => count($liveAssets),
                'config' => $updatedConfig,
            ];
        } catch (\Throwable $e) {
            Log::error("LocalAssetDiscoveryService: Exception during syncChannelAssets for channel {$channel} on project {$project->id}: " . $e->getMessage(), [
                'exception' => $e,
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Merge discovered assets with existing local configuration in project sync_config.
     */
    public function mergeChannelAssets(Project $project, string $channel, array $liveAssets): array
    {
        $release = $project->apisHubRelease ?? ApisHubRelease::where('is_active', true)->where('is_default', true)->first() ?? ApisHubRelease::where('is_active', true)->first();

        $fields = $release?->config_schemas[$channel]['fields'] ?? [];

        // Fallback safeguard: if fields are empty, attempt resolution from ChannelProfileRegistry
        if (empty($fields)) {
            try {
                $profile = app(\App\Domain\ChannelProfiles\ChannelProfileRegistry::class)->get($channel);
                $fields = $profile?->getSchemaDefinition()['fields'] ?? [];
            } catch (\Throwable $e) {}
        }

        if (empty($fields)) {
            return $project->sync_config ?? [];
        }

        $assetListKey = null;

        // Find which field is the array of assets (e.g., 'ad_accounts', 'pages')
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

        if (!$assetListKey) {
            return $project->sync_config ?? [];
        }

        $fullDbState = $project->sync_config ?? [];
        $localAssets = Arr::get($fullDbState, $channel . '.' . $assetListKey, []);

        $mergedAssets = [];
        $liveMap = [];

        $actualLiveAssets = [];
        if (!empty($liveAssets)) {
            $firstKey = array_key_first($liveAssets);
            if (is_string($firstKey) && is_array($liveAssets[$firstKey])) {
                $actualLiveAssets = $liveAssets[$firstKey];
            } else {
                $actualLiveAssets = $liveAssets;
            }
        }

        // Build map of live assets by their ID or URL
        foreach ($actualLiveAssets as $live) {
            $identifier = $live['id'] ?? $live['url'] ?? $live['platformId'] ?? null;
            if ($identifier) {
                $liveMap[$identifier] = $live;
            }
        }

        // Process existing local assets
        foreach ($localAssets as $local) {
            $identifier = $local['id'] ?? $local['url'] ?? $local['platformId'] ?? null;
            if ($identifier) {
                if ($channel === 'facebook_organic') {
                    $local['page_metrics'] = $local['page_metrics'] ?? true;
                    $local['posts'] = $local['posts'] ?? true;
                    $local['post_metrics'] = $local['post_metrics'] ?? true;
                    $local['ig_accounts'] = $local['ig_accounts'] ?? true;
                    $local['ig_account_metrics'] = $local['ig_account_metrics'] ?? true;
                    $local['ig_account_media'] = $local['ig_account_media'] ?? true;
                    $local['ig_account_media_metrics'] = $local['ig_account_media_metrics'] ?? true;
                }

                if (isset($liveMap[$identifier])) {
                    // Still alive, merge new data over it but keep user settings
                    $merged = array_merge($local, $liveMap[$identifier]);
                    $merged['lost_access'] = false;
                    $mergedAssets[$identifier] = $merged;
                    unset($liveMap[$identifier]);
                } else {
                    // It was local but not in live anymore -> lost access!
                    $local['lost_access'] = true;
                    $mergedAssets[$identifier] = $local;
                }
            }
        }

        // Add remaining brand new live assets
        foreach ($liveMap as $identifier => $live) {
            $live['lost_access'] = false;
            $live['enabled'] = false; // Default to false so user has to explicitly enable

            if ($channel === 'facebook_organic') {
                $live['page_metrics'] = true;
                $live['posts'] = false;
                $live['post_metrics'] = false;
                $live['ig_accounts'] = true;
                $live['ig_account_metrics'] = true;
                $live['ig_account_media'] = true;
                $live['ig_account_media_metrics'] = true;
            }

            $mergedAssets[$identifier] = $live;
        }

        Arr::set($fullDbState, $channel . '.' . $assetListKey, array_values($mergedAssets));

        $project->update(['sync_config' => $fullDbState]);

        return $fullDbState;
    }

    /**
     * Fetch live assets directly from the Facade without relying on the remote engine.
     */
    public function fetchAssets(Project $project, string $channel): array
    {
        try {
            switch ($channel) {
                case 'facebook_marketing':
                    return $this->fetchFacebookAdAccounts($project);
                case 'facebook_organic':
                    return $this->fetchFacebookPages($project);
                case 'google_search_console':
                    return $this->fetchGoogleSearchConsoleSites($project);
                case 'google_analytics':
                    return $this->fetchGoogleAnalyticsProperties($project);
                case 'google_ads':
                    return $this->fetchGoogleAdsAccounts($project);
                case 'mailchimp':
                    return $this->fetchMailchimpAssets($project);
                default:
                    return [
                        'success' => false,
                        'message' => "Channel '{$channel}' is not supported for local asset discovery."
                    ];
            }
        } catch (\Throwable $e) {
            $context = [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ];

            if (method_exists($e, 'getResponse') && $e->getResponse()) {
                try {
                    $body = (string)$e->getResponse()->getBody();
                    $context['response_body'] = $body;
                } catch (\Throwable) {}
            } elseif ($e->getPrevious() && method_exists($e->getPrevious(), 'getResponse') && $e->getPrevious()->getResponse()) {
                try {
                    $body = (string)$e->getPrevious()->getResponse()->getBody();
                    $context['response_body'] = $body;
                } catch (\Throwable) {}
            }

            // If it's a configuration issue like missing developer token, log as warning
            $isConfigWarning = str_contains($e->getMessage(), 'Google Ads Developer Token is not configured');

            if ($isConfigWarning) {
                Log::warning("Local Asset Discovery Notice: {$project->name} [{$channel}] - " . $e->getMessage());
            } else {
                Log::error("Local Asset Discovery Failed: {$project->name} [{$channel}]", $context);
            }

            $userMessage = $context['response_body'] ?? $e->getMessage();

            return [
                'success' => false,
                'warning' => $isConfigWarning,
                'message' => $userMessage,
            ];
        }
    }

    protected function fetchMailchimpAssets(Project $project): array
    {
        $syncConfig = $project->sync_config ?? [];
        $accounts = $syncConfig['mailchimp']['accounts'] ?? [];

        if (empty($accounts)) {
            return [
                'success' => true,
                'assets' => ['audiences' => []],
            ];
        }

        $allAudiences = [];

        foreach ($accounts as $accountId => $account) {
            $apiKey = $account['api_key'] ?? null;
            $accessToken = $account['access_token'] ?? null;
            $serverPrefix = $account['server_prefix'] ?? 'us1';
            $accountName = $account['account_name'] ?? $account['name'] ?? $accountId;

            if (empty($apiKey) && empty($accessToken)) {
                continue;
            }

            try {
                $client = new \Anibalealvarezs\MailchimpApi\Services\Marketing\MarketingApi(
                    apiKey: $apiKey,
                    serverPrefix: $serverPrefix,
                    accessToken: $accessToken,
                );

                $response = $client->getListsInfo(count: 1000);
                $lists = $response['lists'] ?? [];

                foreach ($lists as $list) {
                    if (empty($list['id'])) {
                        continue;
                    }
                    $allAudiences[] = [
                        'id' => (string)$list['id'],
                        'name' => ($list['name'] ?? 'Untitled Audience') . " ({$accountName})",
                        'account_id' => (string)$accountId,
                        'data' => [
                            'account_id' => (string)$accountId,
                            'account_name' => $accountName,
                            'stats' => $list['stats'] ?? [],
                            'date_created' => $list['date_created'] ?? null,
                        ],
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning("Failed to fetch Mailchimp lists for account {$accountId} on project {$project->id}: " . $e->getMessage());
            }
        }

        return [
            'success' => true,
            'assets' => ['audiences' => $allAudiences],
        ];
    }

    protected function getFacebookClient(Project $project): FacebookGraphApi
    {
        $profile = $project->facebookProfile;
        if (!$profile || !$profile->access_token) {
            throw new Exception("Facebook profile not linked or missing access token.");
        }

        return new FacebookGraphApi(
            userId: $profile->provider_account_id ?? '',
            appId: config('services.facebook.client_id') ?? config('services.facebook.app_id'),
            appSecret: config('services.facebook.client_secret') ?? config('services.facebook.app_secret'),
            redirectUrl: config('services.facebook.redirect'),
            pageId: null,
            userAccessToken: $profile->access_token,
            sleep: 0 // Eliminate sleep to prevent 408 Timeout in synchronous UI discovery
        );
    }

    protected function getGoogleSearchConsoleClient(Project $project): SearchConsoleApi
    {
        $profile = $project->googleProfile;
        if (!$profile || !$profile->refresh_token) {
            throw new Exception("Google profile not linked or missing refresh token.");
        }

        // SearchConsoleApi might expect the actual token to be populated or refreshed. 
        // We will pass the refresh_token so it handles OAuth internally if needed.
        return new SearchConsoleApi(
            redirectUrl: config('services.google.redirect'),
            clientId: config('services.google.client_id'),
            clientSecret: config('services.google.client_secret'),
            refreshToken: $profile->refresh_token,
            userId: $profile->provider_account_id ?? '',
            scopes: config('services.google.channel_scopes.google_search_console') ?? [],
            token: $profile->access_token ?? ''
        );
    }

    protected function getGoogleAnalyticsClient(Project $project): AnalyticsAdminApi
    {
        $profile = $project->googleProfile;
        if (!$profile || !$profile->refresh_token) {
            throw new Exception("Google profile not linked or missing refresh token.");
        }

        return new AnalyticsAdminApi(
            redirectUrl: config('services.google.redirect'),
            clientId: config('services.google.client_id'),
            clientSecret: config('services.google.client_secret'),
            refreshToken: $profile->refresh_token,
            userId: $profile->provider_account_id ?? '',
            scopes: config('services.google.channel_scopes.google_analytics') ?? [],
            token: $profile->access_token ?? ''
        );
    }

    protected function getGoogleAdsClient(Project $project): GoogleAdsApi
    {
        $profile = $project->googleProfile;
        if (!$profile || !$profile->refresh_token) {
            throw new Exception("Google profile not linked or missing refresh token.");
        }

        $syncConfig = $project->sync_config ?? [];
        $gadsConfig = $syncConfig['google_ads'] ?? [];

        $developerToken = !empty($gadsConfig['developer_token'])
            ? $gadsConfig['developer_token']
            : (config('services.google.developer_token') ?: env('GOOGLE_ADS_DEVELOPER_TOKEN'));

        if (empty($developerToken)) {
            throw new Exception("Google Ads Developer Token is not configured. Please set GOOGLE_ADS_DEVELOPER_TOKEN in your environment or provide it in the Google Ads configuration.");
        }

        $loginCustomerId = $gadsConfig['login_customer_id']
            ?? config('services.google.login_customer_id')
            ?? env('GOOGLE_ADS_LOGIN_CUSTOMER_ID')
            ?? null;

        return new GoogleAdsApi(
            redirectUrl: config('services.google.redirect'),
            clientId: config('services.google.client_id'),
            clientSecret: config('services.google.client_secret'),
            refreshToken: $profile->refresh_token,
            userId: $profile->provider_account_id ?? '',
            developerToken: $developerToken,
            loginCustomerId: $loginCustomerId ? (string)$loginCustomerId : null,
            scopes: config('services.google.channel_scopes.google_ads') ?? ['https://www.googleapis.com/auth/adwords'],
            token: $profile->access_token ?? ''
        );
    }

    protected function fetchGoogleAdsAccounts(Project $project): array
    {
        $client = $this->getGoogleAdsClient($project);
        $accessibleCustomers = $client->getAccessibleCustomers();
        $resourceNames = $accessibleCustomers['resourceNames'] ?? [];

        $adAccounts = [];

        foreach ($resourceNames as $resourceName) {
            $customerId = str_replace('customers/', '', $resourceName);

            try {
                $clientsResponse = $client->getCustomerClients($customerId);
                $clients = $clientsResponse['results'] ?? [];

                // Identify if this customer is an MCC
                $managerName = null;
                foreach ($clients as $clientWrapper) {
                    $c = $clientWrapper['customerClient'] ?? null;
                    if ($c && (string)($c['id'] ?? '') === (string)$customerId) {
                        if (!empty($c['manager'])) {
                            $managerName = $c['descriptiveName'] ?? "Manager ($customerId)";
                        }
                        break;
                    }
                }

                foreach ($clients as $clientWrapper) {
                    $cClient = $clientWrapper['customerClient'] ?? null;
                    if (!$cClient || !empty($cClient['hidden'])) {
                        continue;
                    }

                    // Filter out manager accounts
                    if (!empty($cClient['manager'])) {
                        continue;
                    }

                    $cleanId = (string)$cClient['id'];

                    if ($managerName) {
                        $cClient['login_customer_id'] = $customerId;
                        $cClient['manager_name'] = $managerName;
                    }

                    $adAccounts[$cleanId] = [
                        'id'   => $cleanId,
                        'name' => $cClient['descriptiveName'] ?? 'Account ' . $cleanId,
                        'data' => $cClient,
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning("Could not fetch customer clients for accessible customer {$customerId}: " . $e->getMessage());
            }
        }

        return [
            'success' => true,
            'assets'  => ['ad_accounts' => array_values($adAccounts)],
        ];
    }

    protected function fetchGoogleAnalyticsProperties(Project $project): array
    {
        $client = $this->getGoogleAnalyticsClient($project);
        $properties = $client->getProperties();

        // Normalize
        $assets = [];
        foreach ($properties as $property) {
            $propertyName = $property['property'] ?? '';
            $platformId = str_replace('properties/', '', $propertyName);

            $assets[] = [
                'platformId' => $platformId,
                'name'       => $property['displayName'] ?? '',
                'data'       => $property,
            ];
        }

        return [
            'success' => true,
            'assets' => ['properties' => $assets]
        ];
    }

    protected function fetchFacebookAdAccounts(Project $project): array
    {
        $client = $this->getFacebookClient($project);
        $response = $client->getAdAccounts(
            userId: $project->facebookProfile->provider_account_id,
            limit: 100
        );

        // Normalize
        $assets = [];
        if (isset($response['data']) && is_array($response['data'])) {
            foreach ($response['data'] as $account) {
                if (isset($account['id']) && isset($account['name'])) {
                    $assets[] = [
                        'id' => str_replace('act_', '', $account['id']),
                        'name' => $account['name']
                    ];
                }
            }
        }

        return [
            'success' => true,
            'assets' => ['ad_accounts' => $assets]
        ];
    }

    protected function fetchFacebookPages(Project $project): array
    {
        Log::info("Starting fetchFacebookPages for project: {$project->name} ({$project->id})");
        $client = $this->getFacebookClient($project);

        $userId = $project->facebookProfile->provider_account_id ?? null;
        if (!$userId) {
            Log::error("fetchFacebookPages: Missing provider_account_id for project {$project->id}");
            throw new Exception("Missing provider_account_id");
        }

        Log::info("fetchFacebookPages: Calling getPages for userId: {$userId}");
        
        try {
            $response = $client->getPages(
                userId: $userId,
                fields: 'id,name,link,website,created_time,instagram_business_account{id,name,username,website}'
            );
            
            Log::info("fetchFacebookPages: getPages response received. Raw Data count: " . (isset($response['data']) ? count($response['data']) : 0));
        } catch (\Throwable $e) {
            Log::error("fetchFacebookPages: getPages threw an exception!", [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }

        // Normalize using logic parity with APIs Hub FacebookOrganicDriver
        $assets = [];
        if (isset($response['data']) && is_array($response['data'])) {
            foreach ($response['data'] as $page) {
                if (isset($page['id']) && isset($page['name'])) {
                    $igAccount = $page['instagram_business_account'] ?? null;
                    
                    if (is_array($igAccount)) {
                        $igUsername = $igAccount['username'] ?? null;
                        $igHostname = $igUsername ? 'https://www.instagram.com/' . $igUsername : ($igAccount['website'] ?? null);
                        $igAccount['website'] = $igHostname;
                        $page['instagram_business_account'] = $igAccount;
                    } else {
                        $igUsername = null;
                        $igHostname = null;
                    }

                    $assets[] = [
                        'id'              => $page['id'],
                        'title'           => $page['name'],
                        'hostname'        => $page['website'] ?? null,
                        'url'             => $page['link'] ?? null,
                        'link'            => $page['link'] ?? null,
                        'created_time'    => $page['created_time'] ?? null,
                        'data'            => $page,
                        'ig_account'      => is_array($igAccount) ? ($igAccount['id'] ?? null) : $igAccount,
                        'ig_account_name' => $igUsername ?? (is_array($igAccount) ? ($igAccount['name'] ?? null) : null),
                        'ig_hostname'     => $igHostname,
                        'ig_data'         => is_array($igAccount) ? $igAccount : null,
                    ];
                }
            }
        }
        
        Log::info("fetchFacebookPages: Successfully normalized " . count($assets) . " pages.");

        return [
            'success' => true,
            'assets' => ['pages' => $assets]
        ];
    }

    protected function fetchGoogleSearchConsoleSites(Project $project): array
    {
        $client = $this->getGoogleSearchConsoleClient($project);
        $response = $client->getSites();

        // Normalize
        $assets = [];
        if (isset($response['siteEntry']) && is_array($response['siteEntry'])) {
            foreach ($response['siteEntry'] as $site) {
                if (isset($site['siteUrl'])) {
                    $assets[] = [
                        'id' => $site['siteUrl'],
                        // Strip protocol for name display
                        'name' => preg_replace('#^https?://#', '', rtrim($site['siteUrl'], '/'))
                    ];
                }
            }
        }

        return [
            'success' => true,
            'assets' => ['sites' => $assets]
        ];
    }
}
