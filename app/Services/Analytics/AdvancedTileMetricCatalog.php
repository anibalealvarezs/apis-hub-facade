<?php

namespace App\Services\Analytics;

class AdvancedTileMetricCatalog
{
    public static function channels(): array
    {
        return [
            'google_search_console' => [
                'metrics' => array_keys(array_filter(GscAnalyticsCatalog::tree()['metrics'], static fn ($metric) => $metric['reducer'] === 'sum')),
                'dependencies' => ['', 'non-searchAppearance'],
                'filters' => true,
            ],
            'google_ads' => [
                'metrics' => ['spend', 'clicks', 'impressions', 'conversions'],
                'dependencies' => null,
                'filters' => true,
            ],
            'google_analytics' => [
                'metrics' => ['sessions'],
                'dependencies' => ['', 'traffic_matrix'],
                'filters' => false,
                'query_dependency' => 'traffic_matrix',
            ],
        ];
    }
}
