<?php

namespace App\Services\Analytics;

class ChannelCapabilityRegistry
{
    /**
     * Define the capabilities/tags for each channel.
     *
     * @return array
     */
    public static function getTags(): array
    {
        return [
            'facebook_marketing' => ['spendable', 'clickable', 'impressionable', 'paid_media'],
            'google_search_console' => ['clickable', 'impressionable', 'seo'],
            'facebook_organic' => ['organic_social', 'reach_driven', 'impressionable'],
            // Future channels:
            // 'google_ads' => ['spendable', 'clickable', 'impressionable', 'paid_media'],
            // 'google_analytics' => ['clickable', 'conversion_tracked', 'revenue_tracked'],
            // 'klaviyo' => ['revenue_tracked', 'conversion_tracked'],
        ];
    }

    /**
     * Get all channels that have a specific tag.
     *
     * @param string $tag
     * @return array
     */
    public static function getChannelsByTag(string $tag): array
    {
        return array_keys(array_filter(self::getTags(), fn($tags) => in_array($tag, $tags)));
    }
}
