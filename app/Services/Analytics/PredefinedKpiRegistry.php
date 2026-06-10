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
