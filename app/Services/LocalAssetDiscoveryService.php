<?php

namespace App\Services;

use App\Models\Project;
use Anibalealvarezs\FacebookGraphApi\FacebookGraphApi;
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
                default:
                    return [
                        'success' => false,
                        'message' => "Channel '{$channel}' is not supported for local asset discovery."
                    ];
            }
        } catch (Exception $e) {
            Log::error("Local Asset Discovery Failed: {$project->name} [{$channel}]", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
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
            userAccessToken: $profile->access_token
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
        $client = $this->getFacebookClient($project);
        $response = $client->getPages(
            userId: $project->facebookProfile->provider_account_id
        );

        // Normalize
        $assets = [];
        if (isset($response['data']) && is_array($response['data'])) {
            foreach ($response['data'] as $page) {
                if (isset($page['id']) && isset($page['name'])) {
                    $assets[] = [
                        'id' => $page['id'],
                        'name' => $page['name']
                    ];
                }
            }
        }

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
