<?php

namespace App\Services\Analytics;

class ChannelCapabilityRegistry
{
    public const AUDITED_CHANNELS = [
        'google_search_console',
        'google_analytics',
        'google_ads',
        'facebook_marketing',
        'facebook_organic',
        'mailchimp',
    ];

    public static function getAnalyticsTree(string $channel): ?array
    {
        return match ($channel) {
            'google_search_console' => GscAnalyticsCatalog::tree(),
            default => ChannelAnalyticsCatalog::tree($channel),
        };
    }

    /**
     * Return the full versioned metrics catalog trees for all audited channels.
     */
    public static function getAllAnalyticsTrees(): array
    {
        $trees = [];
        foreach (self::AUDITED_CHANNELS as $channel) {
            $tree = self::getAnalyticsTree($channel);
            if ($tree !== null) {
                $trees[$channel] = $tree;
            }
        }
        return $trees;
    }

    /**
     * Return a compact version manifest for each channel's metrics catalog.
     */
    public static function getCatalogVersionManifest(): array
    {
        $manifest = [];
        foreach (self::getAllAnalyticsTrees() as $channel => $tree) {
            $manifest[$channel] = [
                'channel' => $channel,
                'contract_version' => $tree['contract_version'] ?? 1,
                'query_contract_version' => $tree['query_contract_version'] ?? null,
                'minimum_release' => $tree['minimum_release'] ?? null,
                'audited_at' => $tree['audited_at'] ?? '2026-10-30',
                'metrics_count' => isset($tree['metrics']) && is_array($tree['metrics']) ? count($tree['metrics']) : 0,
                'scopes' => isset($tree['scopes']) && is_array($tree['scopes']) ? array_keys($tree['scopes']) : [],
                'ui_dependencies' => isset($tree['ui_dependencies']) && is_array($tree['ui_dependencies']) ? array_keys($tree['ui_dependencies']) : [],
            ];
        }
        return $manifest;
    }

    public static function isMetricAdditive(string $channel, string $metric): bool
    {
        $tree = self::getAnalyticsTree($channel);
        return ($tree['metrics'][$metric]['is_additive'] ?? false) === true;
    }

    /**
     * Define the capabilities/tags for each channel.
     *
     * @return array
     */
    public static function getTags(): array
    {
        return [
            // Top-level Providers (from sync_config)
            'meta' => ['spendable', 'clickable', 'impressionable', 'paid_media', 'organic_social', 'reach_driven'],
            'google' => ['spendable', 'clickable', 'impressionable', 'seo', 'traffic_tracked', 'conversion_tracked', 'revenue_tracked', 'behavior_tracked', 'analytics', 'paid_media'],
            'klaviyo' => ['revenue_tracked', 'conversion_tracked', 'email_marketing'],
            'shopify' => ['revenue_tracked', 'conversion_tracked', 'ecommerce'],
            'mailchimp' => ['email_marketing', 'clickable', 'retention_driven'],
            
            // Sub-channels / Drivers (from Asset Groups)
            'facebook_marketing' => ['spendable', 'clickable', 'impressionable', 'paid_media', 'conversion_tracked', 'revenue_tracked', 'reach_driven'],
            'facebook_organic' => ['organic_social', 'reach_driven', 'impressionable'],
            'google_search_console' => ['clickable', 'impressionable', 'seo'],
            'google_analytics' => ['traffic_tracked', 'conversion_tracked', 'revenue_tracked', 'behavior_tracked', 'analytics'],
            'google_ads' => ['spendable', 'clickable', 'impressionable', 'paid_media', 'conversion_tracked', 'revenue_tracked'],
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
