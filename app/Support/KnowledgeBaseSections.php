<?php

namespace App\Support;

use App\Filament\App\Pages\AccountStructureReference;
use App\Filament\App\Pages\AssetBillingReference;
use App\Filament\App\Pages\ChannelsIntegrationsReference;
use App\Filament\App\Pages\DashboardsReference;
use App\Filament\App\Pages\DataExplorerReference;
use App\Filament\App\Pages\DerivedMetricsReference;
use App\Filament\App\Pages\SubscriptionFeatures;
use App\Filament\App\Pages\SyncingProcessReference;
use Illuminate\Support\Str;

class KnowledgeBaseSections
{
    /**
     * Section titles per Knowledge Base reference page. Each page renders its
     * sections with `<x-filament::section id="{{ Str::slug(__('Title')) }}">`,
     * so the anchor id for a title is computed with the exact same transform.
     * Keep this list in sync with the section headings in each reference blade.
     *
     * @var array<class-string, array<int, string>>
     */
    protected static array $titles = [
        AccountStructureReference::class => [
            'User Accounts vs. Billing Profiles',
            'Billing Profiles & Tiers',
            'Sharing Billing Profiles',
            'Project Ownership vs. Billing Ownership',
            'Project Collaboration Dynamics',
            'Free Tier Collaboration Limitations',
        ],
        AssetBillingReference::class => [
            'How Asset Billing Works',
            'The 2-Hour Grace Period (Staged Assets)',
            'Locked Assets & Quota Consumption',
            'Releasing Quota & Billing Rollover',
            'Payment Failure Grace Period',
            'Annual vs. Monthly Subscriptions',
            'Upgrades & Downgrades',
        ],
        ChannelsIntegrationsReference::class => [
            'Available Channels',
            'In Testing Phase',
            'In Development',
            'Further Integrations',
        ],
        DashboardsReference::class => [
            'What is a Dashboard?',
            'The Dashboard Builder',
            'Widget Types',
            'Widget Data Sources',
            'Asset Groups and Access',
            'Versioning',
            'Sharing and Public Views',
        ],
        DataExplorerReference::class => [
            'Exploring Cached Data',
            'Maximal Granularity and Interaction',
            'Performance Correlations',
        ],
        DerivedMetricsReference::class => [
            'What is a Derived Metric?',
            'Source Series',
            'The Formula',
            'Predefined Templates',
            'Evaluation and Caching',
            'Where Derived Metrics Can Be Used',
            'Versioning',
        ],
        SyncingProcessReference::class => [
            'What is the Syncing Engine?',
            "Daily Granularity & Today's Data",
            'Historic Caching Schedule',
            'Recent Syncing Schedule',
            'Workers Availability',
            'Sync Engine Resilience',
            'How to read the Telemetry',
        ],
        SubscriptionFeatures::class => [
            'Free',
            'Pro',
            'Ultra',
            'Enterprise',
            'Important Note on API Access',
        ],
    ];

    /**
     * Cached `url => anchors` map for the current request.
     *
     * @var array<string, array<int, array{title: string, id: string}>>|null
     */
    protected static ?array $anchorsByUrl = null;

    /**
     * @return array<int, class-string>
     */
    public static function registeredPages(): array
    {
        return array_keys(static::$titles);
    }

    /**
     * @return array<int, array{title: string, id: string}>
     */
    public static function anchorsFor(string $url): array
    {
        static::buildUrlMap();

        $url = static::normalizeUrl($url);

        return static::$anchorsByUrl[$url] ?? [];
    }

    /**
     * @return array<int, array{title: string, id: string}>
     */
    public static function anchorsForPage(string $pageClass): array
    {
        if (! isset(static::$titles[$pageClass])) {
            return [];
        }

        return array_map(
            fn (string $title): array => [
                'title' => __($title),
                'id' => Str::slug(__($title)),
            ],
            static::$titles[$pageClass],
        );
    }

    protected static function buildUrlMap(): void
    {
        if (static::$anchorsByUrl !== null) {
            return;
        }

        static::$anchorsByUrl = [];

        foreach (static::$titles as $page => $titles) {
            try {
                $pageUrl = static::normalizeUrl($page::getUrl());
            } catch (\Throwable) {
                continue;
            }

            static::$anchorsByUrl[$pageUrl] = static::anchorsForPage($page);
        }
    }

    protected static function normalizeUrl(string $url): string
    {
        return rtrim($url, '/');
    }
}
