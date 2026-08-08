<?php

namespace App\Services\Analytics;

class ChannelGranularityRegistry
{
    /**
     * Get available granularities for a specific channel.
     * Optionally filtered by a channel-specific dependency (e.g., GA4 matrix, GSC search appearance).
     */
    public static function getGranularitiesForChannel(string $channel, ?string $dependency = null): array
    {
        $commonTimely = [
            'daily' => __('Daily'),
            'weekly' => __('Weekly'),
            'monthly' => __('Monthly'),
            'quarterly' => __('Quarterly'),
            'semiannual' => __('Semiannual'),
            'annually' => __('Annually'),
            'lifetime' => __('Lifetime'),
        ];

        $dimensions = match ($channel) {
            'google_search_console' => self::getGscDimensions($dependency),
            'google_analytics' => self::getGa4Dimensions($dependency),
            'facebook_marketing' => self::getFbMarketingDimensions($dependency),
            'facebook_organic' => self::getFbOrganicDimensions($dependency),
            default => [],
        };

        return array_merge($commonTimely, $dimensions);
    }

    public static function getDependenciesForChannel(string $channel): array
    {
        return match ($channel) {
            'google_search_console' => [
                'non-searchAppearance' => __('Standard Search (Non-Appearance)'),
                'searchAppearance' => __('Search Appearance'),
            ],
            'facebook_organic' => [
                'facebook_page' => __('Facebook Page'),
                'instagram_account' => __('Instagram Account'),
            ],
            'facebook_marketing' => [
                'account_level' => __('Account Level'),
                'campaign_level' => __('Campaign Level'),
                'adset_level' => __('Ad Set Level'),
                'ad_level' => __('Ad Level'),
            ],
            'google_analytics' => [
                'traffic_matrix' => __('Traffic Matrix (Session)'),
                'acquisition_matrix' => __('Acquisition Matrix (First User)'),
                'event_matrix' => __('Event Matrix'),
                'ad_touchpoint_matrix' => __('Ad Touchpoint Matrix'),
            ],
            default => [],
        };
    }

    private static function getGscDimensions(?string $dependency): array
    {
        $dependency = $dependency ?? 'non-searchAppearance';

        return match ($dependency) {
            'searchAppearance' => [
                'dimensions.searchAppearance' => __('Search Appearance'),
            ],
            'non-searchAppearance' => [
                'query' => __('Query / Keyword'),
                'dimensions.page' => __('Page / URL'),
                'country' => __('Country / Geo'),
                'device' => __('Device'),
            ],
            default => [],
        };
    }

    private static function getFbOrganicDimensions(?string $dependency): array
    {
        $dependency = $dependency ?? 'facebook_page';

        return match ($dependency) {
            'instagram_account' => [
                'ig_post' => __('Instagram Post / Media'),
            ],
            'facebook_page' => [
                'fb_post' => __('Facebook Post / Media'),
            ],
            default => [],
        };
    }

    private static function getFbMarketingDimensions(?string $dependency): array
    {
        $dependency = $dependency ?? 'account_level';

        return match ($dependency) {
            'account_level' => [
                'level' => __('Account Level'),
            ],
            'campaign_level' => [
                'level' => __('Campaign Level'),
            ],
            'adset_level' => [
                'level' => __('Ad Set Level'),
            ],
            'ad_level' => [
                'level' => __('Ad Level'),
            ],
            default => [],
        };
    }

    private static function getGa4Dimensions(?string $matrix): array
    {
        // If no specific matrix is provided, we can return a union of common ones
        // or just the default traffic dimensions.
        $matrix = $matrix ?? 'traffic_matrix';

        return match ($matrix) {
            'traffic_matrix' => [
                'channeledCampaign' => __('Campaign'),
                'channeledAdGroup' => __('Ad Group'),
                'dimensions.sessionDefaultChannelGroup' => __('Default Channel Group'),
                'dimensions.sessionSourceMedium' => __('Source / Medium'),
                'dimensions.landing_page' => __('Landing Page'),
                'country' => __('Country'),
                'device' => __('Device'),
            ],
            'acquisition_matrix' => [
                'channeledCampaign' => __('Campaign (First User)'),
                'channeledAdGroup' => __('Ad Group (First User)'),
                'dimensions.firstUserDefaultChannelGroup' => __('Default Channel Group (First User)'),
                'dimensions.firstUserSourceMedium' => __('Source / Medium (First User)'),
            ],
            'event_matrix' => [
                'event' => __('Event Name'),
            ],
            'ad_touchpoint_matrix' => [
                'channeledCampaign' => __('Campaign (Touchpoint)'),
                'channeledAdGroup' => __('Ad Group (Touchpoint)'),
                'channeledAd' => __('Ad (Touchpoint)'),
            ],
            default => [],
        };
    }

    public static function allowsMultipleAssets(string $channel): bool
    {
        return match ($channel) {
            'facebook_marketing' => true,
            // 'klaviyo', 'shopify', 'mailchimp' etc... add here if they should allow multiple
            default => false,
        };
    }
}
