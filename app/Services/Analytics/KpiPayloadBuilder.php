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
                'startDate' => $runtimeOverrides['start_date'] ?? $state['start_date'] ?? '',
                'endDate' => $runtimeOverrides['end_date'] ?? $state['end_date'] ?? '',
                'groupBy' => [$runtimeOverrides['granularity'] ?? $state['granularity'] ?? 'daily'],
            ],
            'zero_handling' => $runtimeOverrides['zero_handling'] ?? $state['zero_handling'] ?? 'remove',
            'edge_case_handling' => [
                'weighted' => (bool)($state['edge_case_weighted'] ?? true),
                'grouping' => $state['edge_case_grouping'] ?? 'none',
                'group_column' => self::resolveGroupColumn($state),
                'remove_unknown' => (bool)($state['remove_unknown'] ?? false),
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

        // For Bivariate, we need to build the right side (independent variables)
        $independents = array_values($state['independent_variables'] ?? []);
        \Illuminate\Support\Facades\Log::info('[STEP KpiPayloadBuilder] Bivariate AST building', [
            'calculation_type' => $calculationType,
            'independent_vars_count' => count($independents),
            'independent_vars_raw' => $independents,
        ]);
        if (empty($independents)) {
            return $dependentNode; // Fallback
        }

        // Build right node (might be nested if multiple variables, usually added together)
        $rightNode = self::buildIndependentNodes($independents, $state['granularity'] ?? 'daily');

        \Illuminate\Support\Facades\Log::info('[STEP KpiPayloadBuilder] Full AST tree', [
            'operator' => '/',
            'left' => $dependentNode,
            'right' => $rightNode,
        ]);

        // Operator is usually division for regression/elasticity (dependent / independent)
        return [
            'type' => 'operator',
            'operator' => '/',
            'left' => $dependentNode,
            'right' => $rightNode,
        ];
    }

    private static function buildIndependentNodes(array $variables, string $granularity = 'daily'): array
    {
        if (count($variables) === 1) {
            $var = $variables[0];
            $indChannel = $var['independent_channel'] ?? '';
            $indMetric = $var['independent_metric'] ?? '';
            $indFullMetric = $indChannel . '.' . $indMetric;
            $node = [
                'type' => 'metric',
                'metric' => $indFullMetric,
            ];
            \Illuminate\Support\Facades\Log::info('[STEP KpiPayloadBuilder] Building independent node (single)', [
                'channel' => $indChannel,
                'metric' => $indMetric,
                'full_metric' => $indFullMetric,
                'has_asset_group' => !empty($var['independent_asset_group']),
                'has_asset_filter' => !empty($var['independent_asset_filter']),
                'asset_group' => $var['independent_asset_group'] ?? null,
                'asset_filter' => $var['independent_asset_filter'] ?? null,
            ]);
            
            if (!empty($var['independent_asset_group'])) {
                $group = \App\Models\AssetGroup::find($var['independent_asset_group']);
                $assets = $group ? $group->active_items->where('channel', $var['independent_channel'])->pluck('asset_id')->toArray() : [];
                
                if (!empty($assets)) {
                    $node['filters'] = ['asset_platform_id' => array_values($assets)];
                    \Illuminate\Support\Facades\Log::info('[STEP KpiPayloadBuilder] Independent asset group resolved', [
                        'group_id' => $var['independent_asset_group'],
                        'resolved_assets' => array_values($assets),
                    ]);
                } else {
                    $node['filters'] = ['asset_platform_id' => ['__empty_group__']];
                }
            } elseif (!empty($var['independent_asset_filter'])) {
                $node['filters'] = ['asset_platform_id' => $var['independent_asset_filter']];
                \Illuminate\Support\Facades\Log::info('[STEP KpiPayloadBuilder] Independent asset filter applied', [
                    'asset_filter' => $var['independent_asset_filter'],
                ]);
            }

            if (($var['independent_channel'] ?? '') === 'google_search_console'
                && $granularity !== 'search_appearance'
                && $granularity !== 'dimensions.searchAppearance'
                && !isset($node['filters']['dimensions.searchAppearance'])
            ) {
                $node['filters']['dimensions.searchAppearance'] = 'standard';
                \Illuminate\Support\Facades\Log::info('[STEP KpiPayloadBuilder] Added GSC searchAppearance=standard filter', [
                    'node_metric' => $indFullMetric,
                ]);
            }
            
            return $node;
        }

        // If multiple, chain them with '+'
        $first = array_shift($variables);
        $left = [
            'type' => 'metric',
            'metric' => ($first['independent_channel'] ?? '') . '.' . ($first['independent_metric'] ?? ''),
        ];
        
        if (!empty($first['independent_asset_group'])) {
            $group = \App\Models\AssetGroup::find($first['independent_asset_group']);
            $assets = $group ? $group->active_items->where('channel', $first['independent_channel'])->pluck('asset_id')->toArray() : [];
            
            if (!empty($assets)) {
                $left['filters'] = ['asset_platform_id' => array_values($assets)];
            } else {
                $left['filters'] = ['asset_platform_id' => ['__empty_group__']];
            }
        } elseif (!empty($first['independent_asset_filter'])) {
            $left['filters'] = ['asset_platform_id' => $first['independent_asset_filter']];
        }

        if (($first['independent_channel'] ?? '') === 'google_search_console'
            && $granularity !== 'search_appearance'
            && $granularity !== 'dimensions.searchAppearance'
            && !isset($left['filters']['dimensions.searchAppearance'])
        ) {
            $left['filters']['dimensions.searchAppearance'] = 'standard';
        }

        return [
            'type' => 'operator',
            'operator' => '+',
            'left' => $left,
            'right' => self::buildIndependentNodes($variables, $granularity),
        ];
    }

}
