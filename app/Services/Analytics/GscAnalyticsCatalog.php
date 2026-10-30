<?php

namespace App\Services\Analytics;

use Illuminate\Validation\ValidationException;

/** The cached analytics surface, distinct from everything Google can provide. */
class GscAnalyticsCatalog
{
    public const SEARCH_TYPES = ['web', 'image', 'video', 'news', 'discover', 'googleNews'];

    public static function searchTypes(): array
    {
        return array_combine(self::SEARCH_TYPES, array_map(fn ($label) => __($label), [
            'Web', 'Images', 'Videos', 'News (Google Search tab)', 'Discover', 'Google News (site and app)',
        ]));
    }

    public static function tree(): array
    {
        $search = ['queries', 'pages', 'countries', 'devices', 'appearances'];
        $feed = ['pages', 'countries'];
        $types = [];
        foreach (self::searchTypes() as $type => $label) {
            $isFeed = in_array($type, ['discover', 'googleNews'], true);
            $types[$type] = [
                'label' => $label,
                'metrics' => $isFeed ? ['clicks', 'impressions', 'ctr'] : ['clicks', 'impressions', 'ctr', 'position'],
                'tabs' => $isFeed ? $feed : $search,
                'source_tabs' => $type === 'googleNews' ? ['pages', 'countries', 'devices', 'appearances'] : ($type === 'discover' ? ['pages', 'countries', 'appearances'] : $search),
                'scopes' => $isFeed ? ['non-searchAppearance'] : ['non-searchAppearance', 'searchAppearance'],
            ];
        }
        return [
            'channel' => 'google_search_console', 'minimum_release' => '1.18.3', 'default_search_type' => 'web',
            'search_types' => $types,
            'metrics' => [
                'clicks' => ['label' => __('Clicks'), 'unit' => 'count', 'reducer' => 'sum'],
                'impressions' => ['label' => __('Impressions'), 'unit' => 'count', 'reducer' => 'sum'],
                'ctr' => ['label' => __('CTR'), 'unit' => 'ratio', 'reducer' => 'clicks / impressions'],
                'position' => ['label' => __('Average Position'), 'unit' => 'rank', 'reducer' => 'impression_weighted_average'],
            ],
            'breakdowns' => ['queries' => 'query', 'pages' => 'dimensions.page', 'countries' => 'country', 'devices' => 'device', 'appearances' => 'dimensions.searchAppearance'],
            'dimensions' => self::dimensionDefinitions(),
            'granularities' => array_diff_key(ChannelGranularityRegistry::getGranularitiesForChannel('google_search_console'), self::dimensionDefinitions(), ['dimensions.search_type' => true]),
            'entities' => ['channeledAccount' => 'property', 'query' => 'query', 'country' => 'country', 'device' => 'device'],
            'ingestion' => ['search_types_default' => self::SEARCH_TYPES, 'history' => 'Available Google history within the configured cache range', 'feed_grain' => ['daily', 'dimensions.page', 'country']],
            'scopes' => [
                'non-searchAppearance' => ['label' => __('Standard Search (Non-Appearance)'), 'filter' => ['dimensions.searchAppearance' => 'standard'], 'aggregation_profiles' => ['gsc_search_cube', 'gsc_page_flow']],
                'searchAppearance' => ['label' => __('Search Appearance'), 'filter' => ['dimensions.searchAppearance' => ['operator' => 'neq', 'value' => 'standard']], 'grain' => ['daily', 'dimensions.searchAppearance'], 'aggregation_profiles' => ['gsc_search_cube_by_appearance'], 'overlaps_standard' => true, 'merge_with_standard' => false],
            ],
            'filters' => ['search_type' => 'dimensions.search_type', 'semantic' => ['dimensions.intent', 'dimensions.brand_relation', 'dimensions.business_relevance']],
        ];
    }

    public static function dimensionDefinitions(): array
    {
        return [
            'query' => ['label' => __('Query / Keyword'), 'tab' => 'queries'],
            'dimensions.page' => ['label' => __('Landing Page'), 'tab' => 'pages'],
            'country' => ['label' => __('Country'), 'tab' => 'countries'],
            'device' => ['label' => __('Device'), 'tab' => 'devices'],
            'dimensions.searchAppearance' => ['label' => __('Search Appearance'), 'tab' => 'appearances'],
        ];
    }

    public static function selectedTypes(array $filters = [], ?string $type = null): array
    {
        $selection = $type ?? ($filters['dimensions.search_type'] ?? 'web');
        if (is_array($selection) && isset($selection['operator'])) {
            if (!in_array($selection['operator'], ['eq', 'in'], true)) self::invalid();
            $selection = $selection['value'] ?? [];
        }
        if (array_filter((array)$selection, fn ($value) => !is_string($value))) self::invalid();
        $selection = array_values(array_unique((array)$selection));
        if (!$selection || array_diff($selection, self::SEARCH_TYPES)) self::invalid();
        return $selection;
    }

    public static function supports(array $types, string $category, string $key): bool
    {
        $tree = self::tree();
        foreach ($types as $type) if (!in_array($key, $tree['search_types'][$type][$category] ?? [], true)) return false;
        return true;
    }

    public static function validateQuery(array $types, array $metrics, array $groups, array $filters): void
    {
        $aliases = ['search_appearance' => 'dimensions.searchAppearance', 'searchAppearance' => 'dimensions.searchAppearance', 'dimensions.query' => 'query', 'dimensions.device' => 'device', 'dimensions.country' => 'country'];
        $groups = array_map(fn ($key) => $aliases[$key] ?? $key, $groups);
        foreach ($filters as $key => $value) if (isset($aliases[$key])) $filters[$aliases[$key]] = $value;
        $standardScope = ($filters['dimensions.searchAppearance'] ?? null) === 'standard';
        if (isset($filters['dimensions.searchAppearance']) && is_array($scope = $filters['dimensions.searchAppearance'])) {
            if (array_filter((array)($scope['value'] ?? []), fn ($value) => !is_string($value))) self::invalid();
            $values = array_values(array_unique((array)($scope['value'] ?? [])));
            $operator = $scope['operator'] ?? 'in';
            $safeEquality = in_array($operator, ['eq', '='], true) && count($values) === 1;
            $safeList = $operator === 'in' && $values && (!in_array('standard', $values, true) || count($values) === 1);
            $safeExclusion = in_array($operator, ['neq', 'not_equal', 'not_in'], true) && $values === ['standard'];
            if (!$safeEquality && !$safeList && !$safeExclusion) self::invalid(__('Standard and appearance scopes must be queried separately.'));
            $standardScope = ($safeEquality || $safeList) && $values === ['standard'];
        }
        foreach ($metrics as $metric) {
            if (!self::supports($types, 'metrics', $metric)) self::invalid(__('This metric is not available for the selected search type.'));
        }
        $breakdowns = self::tree()['breakdowns'];
        foreach ($breakdowns as $tab => $dimension) {
            $used = in_array($dimension, $groups, true) || (isset($filters[$dimension]) && !($tab === 'appearances' && $standardScope));
            if ($used && !self::supports($types, 'tabs', $tab)) self::invalid(__('This breakdown is not cached for the selected search type.'));
        }
        foreach (array_keys($filters) as $key) {
            if ((str_starts_with($key, 'attributes.query.') && !self::supports($types, 'tabs', 'queries')) ||
                (str_starts_with($key, 'attributes.device.') && !self::supports($types, 'tabs', 'devices'))) {
                self::invalid(__('This breakdown is not cached for the selected search type.'));
            }
        }
        if (!self::supports($types, 'tabs', 'queries')) {
            foreach (self::tree()['filters']['semantic'] as $dimension) {
                if (isset($filters[$dimension]) || in_array($dimension, $groups, true)) self::invalid(__('Search intent filters require search queries.'));
            }
        }
    }

    private static function invalid(?string $message = null): never
    {
        throw ValidationException::withMessages(['searchType' => $message ?? __('Select a supported search type.')]);
    }
}
