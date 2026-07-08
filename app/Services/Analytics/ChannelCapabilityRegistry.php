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
            'meta' => ['spendable', 'clickable', 'impressionable', 'paid_media', 'organic_social', 'reach_driven'],
            'google' => ['spendable', 'clickable', 'impressionable', 'seo', 'traffic_tracked', 'conversion_tracked', 'revenue_tracked', 'behavior_tracked', 'analytics', 'paid_media'],
            'klaviyo' => ['revenue_tracked', 'conversion_tracked', 'email_marketing'],
            'shopify' => ['revenue_tracked', 'conversion_tracked', 'ecommerce'],
            // Future channels
            // 'tiktok' => ['spendable', 'clickable', 'impressionable', 'paid_media'],
            // 'pinterest' => ['spendable', 'clickable', 'impressionable', 'paid_media'],
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
