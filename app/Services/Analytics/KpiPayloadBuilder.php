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
            ],
            'max_ratio' => $state['max_ratio'] ?? null,
            $calculationType => true,
        ];
    }

    public static function buildWithGroupColumnDebug(string $calculationType, array $state, array $runtimeOverrides = []): array
    {
        $payload = self::build($calculationType, $state, $runtimeOverrides);
        $groupColumn = self::resolveGroupColumn($state);
        \Illuminate\Support\Facades\Log::info('KPI payload group_column test', [
            'group_column' => $groupColumn,
            'has_group_column_in_payload' => isset($payload['edge_case_handling']['group_column']),
            'payload_edge_case' => $payload['edge_case_handling'],
        ]);
        return $payload;
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
        $dependentNode = [
            'type' => 'metric',
            'metric' => ($state['dependent_channel'] ?? '') . '.' . ($state['dependent_metric'] ?? ''),
        ];

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

        // For Univariate, AST is just the dependent node
        if (in_array($calculationType, [
            'calculate_autocorrelation', 'calculate_anomaly',
            'calculate_trend_linear', 'calculate_trend_sma', 
            'calculate_trend_ema', 'calculate_trend_holt_winters', 
            'calculate_trend_logarithmic'
        ])) {
            return $dependentNode;
        }

        // For Bivariate, we need to build the right side (independent variables)
        $independents = array_values($state['independent_variables'] ?? []);
        if (empty($independents)) {
            return $dependentNode; // Fallback
        }

        // Build right node (might be nested if multiple variables, usually added together)
        $rightNode = self::buildIndependentNodes($independents);

        // Operator is usually division for regression/elasticity (dependent / independent)
        return [
            'type' => 'operator',
            'operator' => '/',
            'left' => $dependentNode,
            'right' => $rightNode,
        ];
    }

    private static function buildIndependentNodes(array $variables): array
    {
        if (count($variables) === 1) {
            $var = $variables[0];
            $node = [
                'type' => 'metric',
                'metric' => ($var['independent_channel'] ?? '') . '.' . ($var['independent_metric'] ?? ''),
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

        return [
            'type' => 'operator',
            'operator' => '+',
            'left' => $left,
            'right' => self::buildIndependentNodes($variables),
        ];
    }

}
