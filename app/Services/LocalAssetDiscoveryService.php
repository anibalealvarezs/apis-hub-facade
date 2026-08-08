<?php

namespace App\Services;

use App\Models\Project;
use Anibalealvarezs\FacebookGraphApi\FacebookGraphApi;
use Anibalealvarezs\GoogleApi\Services\AnalyticsAdmin\AnalyticsAdminApi;
use Anibalealvarezs\GoogleApi\Services\SearchConsole\SearchConsoleApi;
use Exception;
use Illuminate\Support\Facades\Log;

class LocalAssetDiscoveryService
{
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
                default:
                    return [
                        'success' => false,
                        'message' => "Channel '{$channel}' is not supported for local asset discovery."
                    ];
            }
        } catch (\Throwable $e) {
            Log::error("Local Asset Discovery Failed: {$project->name} [{$channel}]", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine(),
            ];
        }
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
