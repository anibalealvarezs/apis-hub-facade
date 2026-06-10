<?php

namespace App\Services\Analytics;

class PredefinedKpiRegistry
{
    /**
     * Get all predefined KPIs.
     *
     * @return array
     */
    public static function getPredefinedKpis(): array
    {
        return [
            'true_blended_marginal_cost' => [
                'name' => 'True Blended Marginal Cost',
                'description' => 'Discover true marginal costs across blended channels.',
                'scope' => 'global',
                'categories' => ['performance', 'cost', 'cross-channel', 'agency', 'scope_global'],
                'required_tags' => ['spendable', 'clickable'],
                'calculation_type' => 'calculate_regression',
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__SPENDABLE_CHANNEL_1__',
                            'metric' => 'spend'
                        ],
                        'right' => [
                            'type' => 'operator',
                            'operator' => '+',
                            'left' => [
                                'type' => 'metric',
                                'channel' => '__CLICKABLE_CHANNEL_1__',
                                'metric' => 'clicks'
                            ],
                            'right' => [
                                'type' => 'metric',
                                'channel' => '__CLICKABLE_CHANNEL_2__',
                                'metric' => 'clicks'
                            ]
                        ]
                    ]
                ]
            ],
            'spend_elasticity' => [
                'name' => 'Spend Scalability / Elasticity',
                'description' => 'Find the diminishing returns ceiling. Example: How much does a 10% increase in Paid Spend actually yield in Clicks?',
                'scope' => 'channel',
                'categories' => ['performance', 'cost', 'scalability', 'scope_channel'],
                'required_tags' => ['spendable', 'clickable'],
                'calculation_type' => 'calculate_elasticity',
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__CLICKABLE_CHANNEL_1__',
                            'metric' => 'clicks'
                        ],
                        'right' => [
                            'type' => 'metric',
                            'channel' => '__SPENDABLE_CHANNEL_1__',
                            'metric' => 'spend'
                        ]
                    ]
                ]
            ],
            'weekly_organic_seasonality' => [
                'name' => 'Weekly Organic Seasonality',
                'description' => 'Prove weekly seasonality for an organic or reach-driven metric.',
                'scope' => 'asset',
                'categories' => ['seasonality', 'organic', 'scope_asset'],
                'required_tags' => ['organic_social'],
                'calculation_type' => 'calculate_autocorrelation',
                'template' => [
                    'ast' => [
                        'type' => 'metric',
                        'channel' => '__ORGANIC_SOCIAL_CHANNEL_1__',
                        'metric' => 'reach'
                    ]
                ]
            ],
            'paid_to_organic_halo_effect' => [
                'name' => 'Paid to Organic Halo Effect',
                'description' => 'Predictive Attribution / Halo Effect. Example: Does spending money on Paid Ads cause a delayed spike in Organic Reach?',
                'scope' => 'global',
                'categories' => ['performance', 'cross-channel', 'organic', 'agency', 'scope_global'],
                'required_tags' => ['spendable', 'organic_social'],
                'calculation_type' => 'calculate_granger',
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__ORGANIC_SOCIAL_CHANNEL_1__',
                            'metric' => 'reach'
                        ],
                        'right' => [
                            'type' => 'metric',
                            'channel' => '__SPENDABLE_CHANNEL_1__',
                            'metric' => 'spend'
                        ]
                    ]
                ]
            ],
            'cpc_momentum' => [
                'name' => 'CPC Momentum (MACD)',
                'description' => 'Detect when performance momentum flips. Example: Is our CPC getting cheaper or more expensive on a rolling basis?',
                'scope' => 'channel',
                'categories' => ['cost', 'trends', 'scope_channel'],
                'required_tags' => ['spendable', 'clickable'],
                'calculation_type' => 'calculate_macd',
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__SPENDABLE_CHANNEL_1__',
                            'metric' => 'spend'
                        ],
                        'right' => [
                            'type' => 'metric',
                            'channel' => '__CLICKABLE_CHANNEL_1__',
                            'metric' => 'clicks'
                        ]
                    ]
                ]
            ],
            'impression_anomaly_alert' => [
                'name' => 'Impression Anomaly Alert',
                'description' => 'Automated Alerting for unexpected spikes in impressions.',
                'scope' => 'asset',
                'categories' => ['impressions', 'alerts', 'scope_asset'],
                'required_tags' => ['impressionable'],
                'calculation_type' => 'calculate_anomaly',
                'template' => [
                    'ast' => [
                        'type' => 'metric',
                        'channel' => '__IMPRESSIONABLE_CHANNEL_1__',
                        'metric' => 'impressions'
                    ]
                ]
            ],
            'seo_click_momentum' => [
                'name' => 'SEO Click Momentum',
                'description' => 'Detect whether your organic search clicks are gaining or losing momentum over time.',
                'scope' => 'asset',
                'categories' => ['clicks', 'trends', 'seo', 'scope_asset'],
                'required_tags' => ['seo', 'clickable'],
                'calculation_type' => 'calculate_macd',
                'template' => [
                    'ast' => [
                        'type' => 'metric',
                        'channel' => '__SEO_CHANNEL_1__',
                        'metric' => 'clicks'
                    ]
                ]
            ],
            'reach_elasticity' => [
                'name' => 'Reach Scalability / Elasticity',
                'description' => 'Find your audience saturation ceiling. How much does a 10% increase in spend actually expand your reach?',
                'scope' => 'channel',
                'categories' => ['impressions', 'scalability', 'cost', 'scope_channel'],
                'required_tags' => ['spendable', 'impressionable'],
                'calculation_type' => 'calculate_elasticity',
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__IMPRESSIONABLE_CHANNEL_1__',
                            'metric' => 'impressions'
                        ],
                        'right' => [
                            'type' => 'metric',
                            'channel' => '__SPENDABLE_CHANNEL_1__',
                            'metric' => 'spend'
                        ]
                    ]
                ]
            ],
            'content_half_life' => [
                'name' => 'Content Half-Life',
                'description' => 'Measure how quickly your organic content engagement decays over time.',
                'scope' => 'asset',
                'categories' => ['seasonality', 'organic', 'performance', 'scope_asset'],
                'required_tags' => ['organic_social', 'reach_driven'],
                'calculation_type' => 'calculate_autocorrelation',
                'template' => [
                    'ast' => [
                        'type' => 'metric',
                        'channel' => '__ORGANIC_SOCIAL_CHANNEL_1__',
                        'metric' => 'reach'
                    ]
                ]
            ],
            'paid_organic_cannibalization' => [
                'name' => 'Paid to Organic Cannibalization',
                'description' => 'Detect if paid campaigns are stealing reach from your organic content.',
                'scope' => 'global',
                'categories' => ['cross-channel', 'performance', 'organic', 'scope_global'],
                'required_tags' => ['spendable', 'organic_social', 'impressionable'],
                'calculation_type' => 'calculate_granger',
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__ORGANIC_SOCIAL_CHANNEL_1__',
                            'metric' => 'reach'
                        ],
                        'right' => [
                            'type' => 'metric',
                            'channel' => '__SPENDABLE_CHANNEL_1__',
                            'metric' => 'spend'
                        ]
                    ]
                ]
            ],
            'ctr_efficiency' => [
                'name' => 'CTR Efficiency (SEO)',
                'description' => 'Is your organic search presence becoming more or less effective at turning impressions into clicks?',
                'scope' => 'channel',
                'categories' => ['clicks', 'impressions', 'seo', 'scope_channel'],
                'required_tags' => ['seo', 'clickable', 'impressionable'],
                'calculation_type' => 'calculate_regression',
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__SEO_CHANNEL_1__',
                            'metric' => 'clicks'
                        ],
                        'right' => [
                            'type' => 'metric',
                            'channel' => '__SEO_CHANNEL_1__',
                            'metric' => 'impressions'
                        ]
                    ]
                ]
            ],
            'revenue_elasticity' => [
                'name' => 'Revenue Elasticity (ROAS)',
                'description' => 'Measures how revenue scales with your ad spend. Tells you if you\'re still in the sweet spot or past it.',
                'scope' => 'global',
                'categories' => ['performance', 'results', 'scalability', 'agency', 'scope_global'],
                'required_tags' => ['spendable', 'revenue_tracked'],
                'calculation_type' => 'calculate_elasticity',
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__REVENUE_TRACKED_CHANNEL_1__',
                            'metric' => 'revenue'
                        ],
                        'right' => [
                            'type' => 'metric',
                            'channel' => '__SPENDABLE_CHANNEL_1__',
                            'metric' => 'spend'
                        ]
                    ]
                ]
            ],
            'cpa_trend' => [
                'name' => 'CPA Trend (Cost per Acquisition)',
                'description' => 'Detect whether your cost per acquisition is trending up or down over time.',
                'scope' => 'channel',
                'categories' => ['cost', 'results', 'trends', 'agency', 'scope_channel'],
                'required_tags' => ['spendable', 'conversion_tracked'],
                'calculation_type' => 'calculate_macd',
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__SPENDABLE_CHANNEL_1__',
                            'metric' => 'spend'
                        ],
                        'right' => [
                            'type' => 'metric',
                            'channel' => '__CONVERSION_TRACKED_CHANNEL_1__',
                            'metric' => 'conversions'
                        ]
                    ]
                ]
            ],
            'seo_to_revenue_influence' => [
                'name' => 'SEO to Revenue Influence',
                'description' => 'Tests whether your organic search traffic predicts future revenue. Is SEO driving the bottom line?',
                'scope' => 'global',
                'categories' => ['seo', 'results', 'cross-channel', 'agency', 'scope_global'],
                'required_tags' => ['seo', 'revenue_tracked'],
                'calculation_type' => 'calculate_granger',
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__REVENUE_TRACKED_CHANNEL_1__',
                            'metric' => 'revenue'
                        ],
                        'right' => [
                            'type' => 'metric',
                            'channel' => '__SEO_CHANNEL_1__',
                            'metric' => 'clicks'
                        ]
                    ]
                ]
            ],
            'result_efficiency' => [
                'name' => 'Result Efficiency',
                'description' => 'How efficiently does spend convert to results? The regression slope reveals your marginal cost per result and whether campaign efficiency is improving over time.',
                'scope' => 'channel',
                'categories' => ['results', 'performance', 'cost', 'agency', 'scope_channel'],
                'required_tags' => ['spendable'],
                'calculation_type' => 'calculate_regression',
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__SPENDABLE_CHANNEL_1__',
                            'metric' => 'results'
                        ],
                        'right' => [
                            'type' => 'metric',
                            'channel' => '__SPENDABLE_CHANNEL_1__',
                            'metric' => 'spend'
                        ]
                    ]
                ]
            ],
            'result_rate_momentum' => [
                'name' => 'Result Rate Momentum',
                'description' => 'Is your conversion rate gaining or losing momentum? Early warning signal for campaign effectiveness changes before they impact aggregate numbers.',
                'scope' => 'channel',
                'categories' => ['results', 'trends', 'agency', 'scope_channel'],
                'required_tags' => ['spendable', 'impressionable'],
                'calculation_type' => 'calculate_macd',
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__SPENDABLE_CHANNEL_1__',
                            'metric' => 'results'
                        ],
                        'right' => [
                            'type' => 'metric',
                            'channel' => '__IMPRESSIONABLE_CHANNEL_1__',
                            'metric' => 'impressions'
                        ]
                    ]
                ]
            ],
            'organic_engagement_efficiency' => [
                'name' => 'Organic Engagement Efficiency',
                'description' => 'Measures how engaging your organic content is per person reached. A declining trend signals audience fatigue or declining content relevance.',
                'scope' => 'asset',
                'categories' => ['organic', 'performance', 'agency', 'scope_asset'],
                'required_tags' => ['organic_social', 'reach_driven'],
                'calculation_type' => 'calculate_regression',
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__ORGANIC_SOCIAL_CHANNEL_1__',
                            'metric' => 'engaged_users'
                        ],
                        'right' => [
                            'type' => 'metric',
                            'channel' => '__ORGANIC_SOCIAL_CHANNEL_1__',
                            'metric' => 'reach'
                        ]
                    ]
                ]
            ],
            'roas_momentum' => [
                'name' => 'ROAS Momentum',
                'description' => 'Detects shifts in Return on Ad Spend momentum before they become obvious in aggregate numbers. Early warning system for profitability changes.',
                'scope' => 'channel',
                'categories' => ['results', 'trends', 'cost', 'agency', 'scope_channel'],
                'required_tags' => ['spendable'],
                'calculation_type' => 'calculate_macd',
                'template' => [
                    'ast' => [
                        'type' => 'metric',
                        'channel' => '__SPENDABLE_CHANNEL_1__',
                        'metric' => 'purchase_roas'
                    ]
                ]
            ],
            'cpc_anomaly' => [
                'name' => 'CPC Anomaly Alert',
                'description' => 'Alerts when your Cost Per Click deviates significantly from normal range. Helps catch auction changes, competitive shifts, or tracking issues early.',
                'scope' => 'asset',
                'categories' => ['cost', 'alerts', 'agency', 'scope_asset'],
                'required_tags' => ['spendable'],
                'calculation_type' => 'calculate_anomaly',
                'template' => [
                    'ast' => [
                        'type' => 'metric',
                        'channel' => '__SPENDABLE_CHANNEL_1__',
                        'metric' => 'cpc'
                    ]
                ]
            ],
            'search_position_efficiency' => [
                'name' => 'Search Position Efficiency',
                'description' => 'How many clicks do you get per unit of search position? Higher means more compelling snippets. A rising trend indicates your search listings are becoming more clickable.',
                'scope' => 'asset',
                'categories' => ['seo', 'performance', 'agency', 'scope_asset'],
                'required_tags' => ['seo'],
                'calculation_type' => 'calculate_regression',
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__SEO_CHANNEL_1__',
                            'metric' => 'clicks'
                        ],
                        'right' => [
                            'type' => 'metric',
                            'channel' => '__SEO_CHANNEL_1__',
                            'metric' => 'position'
                        ]
                    ]
                ]
            ],
        ];
    }

    /**
     * Get available KPIs for a list of active channels.
     * We determine availability by checking if the active channels collectively
     * possess all the required tags for a KPI.
     *
     * @param array $activeChannels
     * @return array
     */
    public static function getAvailableKpis(array $activeChannels): array
    {
        // Resolve all unique tags available across the user's active channels
        $availableTags = [];
        $registryTags = ChannelCapabilityRegistry::getTags();
        
        foreach ($activeChannels as $channel) {
            if (isset($registryTags[$channel])) {
                $availableTags = array_merge($availableTags, $registryTags[$channel]);
            }
        }
        $availableTags = array_unique($availableTags);

        // Filter KPIs where all required_tags are present in availableTags
        return collect(self::getPredefinedKpis())->filter(function ($kpi) use ($availableTags) {
            $requiredTags = $kpi['required_tags'] ?? [];
            return count(array_intersect($requiredTags, $availableTags)) === count($requiredTags);
        })->toArray();
    }
}
