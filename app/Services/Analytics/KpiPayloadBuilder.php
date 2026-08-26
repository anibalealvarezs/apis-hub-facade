<?php

namespace App\Services\Analytics;

class KpiPayloadBuilder
{
    /**
     * Build the full JSON payload expected by the Analytics Engine.
     *
     * @param string $calculationType
     * @param array $ast
     * @param array $scope
     * @return array
     */
    public static function build(string $calculationType, array $state, array $runtimeOverrides = []): array
    {
        $ast = self::buildAstFromState($calculationType, $state);

        $groupColumn = self::resolveGroupColumn($state);
        \Illuminate\Support\Facades\Log::info('KpiPayloadBuilder group_column', [
            'group_column' => $groupColumn,
            'independent_metric' => collect($state['independent_variables'] ?? [])->first()['independent_metric'] ?? null,
        ]);

        return [
            'ast' => $ast,
            'filters' => [
                'startDate' => !empty($runtimeOverrides['start_date']) ? $runtimeOverrides['start_date'] : (!empty($state['start_date']) ? $state['start_date'] : null),
                'endDate' => !empty($runtimeOverrides['end_date']) ? $runtimeOverrides['end_date'] : (!empty($state['end_date']) ? $state['end_date'] : null),
                'groupBy' => [!empty($runtimeOverrides['granularity']) ? $runtimeOverrides['granularity'] : (!empty($state['granularity']) ? $state['granularity'] : 'daily')],
            ],
            'zero_handling' => $runtimeOverrides['zero_handling'] ?? $state['zero_handling'] ?? 'remove',
            'edge_case_handling' => [
                'weighted' => (bool)($state['edge_case_weighted'] ?? true),
                'grouping' => $state['edge_case_grouping'] ?? 'none',
                'group_column' => self::resolveGroupColumn($state),
                'remove_unknown' => (bool)($state['remove_unknown'] ?? true),
            ],
            'max_ratio' => $state['max_ratio'] ?? null,
            $calculationType => true,
        ];
    }

    private static function resolveGroupColumn(array $state): ?string
    {
        $independents = $state['independent_variables'] ?? [];
        foreach ($independents as $var) {
            $metric = $var['independent_metric'] ?? '';
            if ($metric === 'position') {
                return 'y';
            }
        }
        return null;
    }

    public static function buildAstFromState(string $calculationType, array $state): array
    {
        $depSourceType = $state['dependent_source_type'] ?? 'channel';

        if ($depSourceType === 'derived_metric') {
            $depDmId = $state['dependent_dm_id'] ?? null;
            $depFullMetric = $depDmId ? 'dm_' . $depDmId : '';
            $dependentNode = [
                'type' => 'metric',
                'metric' => $depFullMetric,
            ];
            \Illuminate\Support\Facades\Log::info('[STEP KpiPayloadBuilder] Building AST dependent node (derived metric)', [
                'dm_id' => $depDmId,
                'full_metric' => $depFullMetric,
            ]);

            if (in_array($calculationType, [
                'calculate_autocorrelation', 'calculate_anomaly',
                'calculate_trend_linear', 'calculate_trend_sma',
                'calculate_trend_ema', 'calculate_trend_holt_winters',
                'calculate_trend_logarithmic'
            ])) {
                return $dependentNode;
            }

            $independents = array_values($state['independent_variables'] ?? []);
            if (empty($independents)) {
                return $dependentNode;
            }

            $rightNode = self::buildIndependentNodes($independents, $state['granularity'] ?? 'daily');

            return [
                'type' => 'operator',
                'operator' => '/',
                'left' => $dependentNode,
                'right' => $rightNode,
            ];
        }

        $depChannel = $state['dependent_channel'] ?? '';
        $depMetric = $state['dependent_metric'] ?? '';
        $depFullMetric = $depChannel . '.' . $depMetric;
        $dependentNode = [
            'type' => 'metric',
            'metric' => $depFullMetric,
        ];
        \Illuminate\Support\Facades\Log::info('[STEP KpiPayloadBuilder] Building AST dependent node', [
            'channel' => $depChannel,
            'metric' => $depMetric,
            'full_metric' => $depFullMetric,
            'has_asset_group' => !empty($state['dependent_asset_group']),
            'has_asset_filter' => !empty($state['dependent_asset_filter']),
            'asset_group' => $state['dependent_asset_group'] ?? null,
            'asset_filter' => $state['dependent_asset_filter'] ?? null,
            'granularity' => $state['granularity'] ?? 'daily',
        ]);

        if (!empty($state['dependent_asset_group'])) {
            $group = \App\Models\AssetGroup::find($state['dependent_asset_group']);
            $assets = $group ? $group->active_items->where('channel', $state['dependent_channel'])->pluck('asset_id')->toArray() : [];
            
            if (!empty($assets)) {
                $dependentNode['filters'] = ['asset_platform_id' => array_values($assets)];
            } else {
                $dependentNode['filters'] = ['asset_platform_id' => ['__empty_group__']];
            }
        } elseif (!empty($state['dependent_asset_filter'])) {
            $dependentNode['filters'] = ['asset_platform_id' => $state['dependent_asset_filter']];
        }

        // Allow explicit filters configured in state or template (e.g. account_type => 'instagram_account' or 'facebook_page')
        if (!empty($state['filters']) && is_array($state['filters'])) {
            $dependentNode['filters'] = array_merge($dependentNode['filters'] ?? [], $state['filters']);
        }
        if (!empty($state['account_type'])) {
            $dependentNode['filters']['account_type'] = $state['account_type'];
        }

        // If channel is facebook_organic:
        // Automatically inject account_type = 'instagram_account' if dependency is instagram_account
        // OR if the metric requested is an Instagram-specific metric (views, profile_views, website_clicks, profile_links_taps, etc.)
        // Otherwise set 'facebook_page' if dependency is facebook_page
        if (($state['dependent_channel'] ?? '') === 'facebook_organic' && empty($dependentNode['filters']['account_type'])) {
            $igMetrics = ['likes', 'comments', 'views', 'profile_views', 'website_clicks', 'profile_links_taps', 'saves', 'shares', 'replies', 'accounts_engaged', 'content_views'];
            $depMetric = $state['dependent_metric'] ?? '';
            $depDep = $state['dependent_dependency'] ?? $state['dependency'] ?? '';
            $isIgScope = $depDep === 'instagram_account' || in_array($depMetric, $igMetrics, true);
            if ($isIgScope) {
                $dependentNode['filters']['account_type'] = 'instagram_account';
            } elseif ($depDep === 'facebook_page') {
                $dependentNode['filters']['account_type'] = 'facebook_page';
            }
        }

        // GSC stores data separated by searchAppearance (standard vs AMP/etc).
        // Without filtering to 'standard', metrics double-count because both
        // dimension sets are summed independently.
        // Only apply when NOT grouping BY searchAppearance itself.
        $granularity = $state['granularity'] ?? 'daily';
        if (($state['dependent_channel'] ?? '') === 'google_search_console'
            && $granularity !== 'search_appearance'
            && $granularity !== 'dimensions.searchAppearance'
            && !isset($dependentNode['filters']['dimensions.searchAppearance'])
        ) {
            $dependentNode['filters']['dimensions.searchAppearance'] = 'standard';
        }

        // For Univariate, AST is just the dependent node
        if (in_array($calculationType, [
            'calculate_autocorrelation', 'calculate_anomaly',
            'calculate_trend_linear', 'calculate_trend_sma',
            'calculate_trend_ema', 'calculate_trend_holt_winters',
            'calculate_trend_logarithmic'
        ])) {
            \Illuminate\Support\Facades\Log::info('[STEP KpiPayloadBuilder] Univariate AST (no independent vars)', [
                'calculation_type' => $calculationType,
                'dependent_node' => $dependentNode,
            ]);
            return $dependentNode;
        }

        // Check for additional numerator variables (multi-channel sum in numerator)
        $additionalNumeratorVars = $state['dependent_additional_variables'] ?? [];
        if (!empty($additionalNumeratorVars)) {
            $dependentNode = self::buildNumeratorSum($dependentNode, $additionalNumeratorVars, $granularity);
        }

        // For Bivariate, we need to build the right side (independent variables)
        $independents = array_values($state['independent_variables'] ?? []);
        \Illuminate\Support\Facades\Log::info('[STEP KpiPayloadBuilder] Bivariate AST building', [
            'calculation_type' => $calculationType,
            'independent_vars_count' => count($independents),
            'independent_vars_raw' => $independents,
        ]);

        // Build right node (might be nested if multiple variables, usually added together)
        $rightNode = self::buildIndependentNodes($independents, $granularity);
        if (empty($rightNode)) {
            $fallbackCh = $state['dependent_channel'] ?? 'facebook_marketing';
            $rightNode = self::buildSingleIndependentNode([
                'independent_source_type' => 'channel',
                'independent_channel' => $fallbackCh,
                'independent_metric' => 'spend',
                'independent_asset_filter' => $state['dependent_asset_filter'] ?? [],
            ], $granularity);
        }

        \Illuminate\Support\Facades\Log::info('[STEP KpiPayloadBuilder] Full AST tree', [
            'operator' => '/',
            'left' => $dependentNode,
            'right' => $rightNode,
        ]);

        // Operator is division for regression/elasticity/granger (dependent / independent)
        return [
            'type' => 'operator',
            'operator' => '/',
            'left' => $dependentNode,
            'right' => $rightNode,
        ];
    }

    private static function buildNumeratorSum(array $firstNode, array $additionalVars, string $granularity = 'daily'): array
    {
        $rest = [];
        foreach ($additionalVars as $var) {
            $channel = $var['dependent_channel'] ?? '';
            $metric = $var['dependent_metric'] ?? '';
            if (empty($channel) || empty($metric)) {
                continue;
            }
            $rest[] = [
                'type' => 'metric',
                'metric' => $channel . '.' . $metric,
            ];
        }

        if (empty($rest)) {
            return $firstNode;
        }

        $result = $firstNode;
        foreach ($rest as $node) {
            $result = [
                'type' => 'operator',
                'operator' => '+',
                'left' => $result,
                'right' => $node,
            ];
        }

        return $result;
    }

    private static function buildIndependentNodes(array $variables, string $granularity = 'daily'): array
    {
        // Filter out empty/unconfigured variables that have neither a valid metric nor dm_id
        $validVariables = array_values(array_filter($variables, function ($var) {
            $sourceType = $var['independent_source_type'] ?? 'channel';
            if ($sourceType === 'derived_metric') {
                return ! empty($var['independent_dm_id']);
            }
            return ! empty($var['independent_metric']) && ! empty($var['independent_channel']);
        }));

        if (empty($validVariables)) {
            return [];
        }

        if (count($validVariables) === 1) {
            return self::buildSingleIndependentNode($validVariables[0], $granularity);
        }

        // If multiple, chain them with '+'
        $first = array_shift($validVariables);
        $left = self::buildSingleIndependentNode($first, $granularity);

        return [
            'type' => 'operator',
            'operator' => '+',
            'left' => $left,
            'right' => self::buildIndependentNodes($validVariables, $granularity),
        ];
    }

    private static function buildSingleIndependentNode(array $var, string $granularity = 'daily'): array
    {
        $indSourceType = $var['independent_source_type'] ?? 'channel';

        if ($indSourceType === 'derived_metric') {
            $indDmId = $var['independent_dm_id'] ?? null;
            $indFullMetric = $indDmId ? 'dm_' . $indDmId : '';
            return [
                'type' => 'metric',
                'metric' => $indFullMetric,
            ];
        }

        $indChannel = $var['independent_channel'] ?? '';
        $indMetric = $var['independent_metric'] ?? '';
        $indFullMetric = $indChannel . '.' . $indMetric;
        $node = [
            'type' => 'metric',
            'metric' => $indFullMetric,
        ];
        
        if (!empty($var['independent_asset_group'])) {
            $group = \App\Models\AssetGroup::find($var['independent_asset_group']);
            $assets = $group ? $group->active_items->where('channel', $var['independent_channel'])->pluck('asset_id')->toArray() : [];
            
            if (!empty($assets)) {
                $node['filters'] = ['asset_platform_id' => array_values($assets)];
            } else {
                $node['filters'] = ['asset_platform_id' => ['__empty_group__']];
            }
        } elseif (!empty($var['independent_asset_filter'])) {
            $node['filters'] = ['asset_platform_id' => $var['independent_asset_filter']];
        }

        if (!empty($var['filters']) && is_array($var['filters'])) {
            $node['filters'] = array_merge($node['filters'] ?? [], $var['filters']);
        }
        if (!empty($var['account_type'])) {
            $node['filters']['account_type'] = $var['account_type'];
        }

        if (($var['independent_channel'] ?? '') === 'facebook_organic' && empty($node['filters']['account_type'])) {
            $igMetrics = ['likes', 'comments', 'views', 'profile_views', 'website_clicks', 'profile_links_taps', 'saves', 'shares', 'replies', 'accounts_engaged', 'content_views'];
            $indDep = $var['independent_dependency'] ?? $var['dependency'] ?? '';
            $isIgScope = $indDep === 'instagram_account' || in_array($indMetric, $igMetrics, true);
            if ($isIgScope) {
                $node['filters']['account_type'] = 'instagram_account';
            } elseif ($indDep === 'facebook_page') {
                $node['filters']['account_type'] = 'facebook_page';
            }
        }

        if (($var['independent_channel'] ?? '') === 'google_search_console'
            && $granularity !== 'search_appearance'
            && $granularity !== 'dimensions.searchAppearance'
            && !isset($node['filters']['dimensions.searchAppearance'])
        ) {
            $node['filters']['dimensions.searchAppearance'] = 'standard';
        }
        
        return $node;
    }

    /**
     * For facebook_organic KPI nodes that request instagram_account scope, the
     * asset_platform_id stored in KPI state is always the FB page platform_id
     * (both scopes share the same UI asset). apis-hub's translatePlatformIds
     * resolves the FB page platform_id → a ChanneledAccount of type=facebook_page.
     * The subsequent SQL then joins channeled_account_id=<FB_PAGE_CA_ID> AND
     * type='instagram_account', returning zero rows because the IG account is a
     * separate ChanneledAccount row with its own platform_id.
     *
     * This method fixes the AST in-place: before sending the payload to apis-hub,
     * it swaps the FB page platform_id to the linked IG account platform_id (sourced
     * from the project sync_config), so translatePlatformIds resolves the correct
     * instagram_account ChanneledAccount ID.
     *
     * @param array  $payload   The payload returned by build() — modified in-place.
     * @param array  $fboPages  $project->sync_config['facebook_organic']['assets']['pages'] (or ['pages'])
     */
    public static function swapFboIgPlatformIds(array &$payload, array $fboPages): void
    {
        if (empty($fboPages) || !isset($payload['ast'])) {
            return;
        }

        // Build fbPagePlatformId → igAccountPlatformId lookup
        $fbToIg = [];
        foreach ($fboPages as $page) {
            $fbPid = (string)($page['platformId'] ?? $page['platform_id'] ?? $page['id'] ?? '');
            $igPid = (string)($page['ig_account'] ?? $page['igAccountId'] ?? $page['ig_account_id'] ?? '');
            if ($fbPid !== '' && $igPid !== '') {
                $fbToIg[$fbPid] = $igPid;
            }
        }

        if (empty($fbToIg)) {
            return;
        }

        $walk = function (array &$node) use (&$walk, $fbToIg): void {
            $type = $node['type'] ?? '';
            if ($type === 'metric') {
                $metric      = $node['metric'] ?? '';
                $accountType = strtolower(trim((string)($node['filters']['account_type'] ?? '')));
                if (str_starts_with($metric, 'facebook_organic.')
                    && $accountType === 'instagram_account'
                    && isset($node['filters']['asset_platform_id'])
                ) {
                    $raw     = $node['filters']['asset_platform_id'];
                    $isArray = is_array($raw);
                    $ids     = $isArray ? $raw : [$raw];
                    $swapped = array_map(fn ($id) => $fbToIg[(string)$id] ?? $id, $ids);
                    $node['filters']['asset_platform_id'] = $isArray ? $swapped : $swapped[0];
                    \Illuminate\Support\Facades\Log::info('[KpiPayloadBuilder] Swapped FBO asset_platform_id: FB page → IG account', [
                        'original' => $ids,
                        'swapped'  => $swapped,
                    ]);
                }
            } elseif ($type === 'operator') {
                if (isset($node['left']) && is_array($node['left'])) {
                    $walk($node['left']);
                }
                if (isset($node['right']) && is_array($node['right'])) {
                    $walk($node['right']);
                }
            }
        };

        $walk($payload['ast']);
    }

}
