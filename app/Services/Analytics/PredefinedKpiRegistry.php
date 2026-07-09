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
                'categories' => ['performance', 'cost', 'cross-channel', 'agency', 'scope_global', 'org_mkt_marketing', 'source_src'],
                'required_tags' => ['spendable', 'clickable'],
                'calculation_type' => 'calculate_regression',
                'compatible_widgets' => ['gauge', 'tile', 'table', 'scatter_plot'],
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__SPENDABLE_CHANNEL_1__',
                            'metric' => 'spend',
                        ],
                        'right' => [
                            'type' => 'operator',
                            'operator' => '+',
                            'left' => [
                                'type' => 'metric',
                                'channel' => '__CLICKABLE_CHANNEL_1__',
                                'metric' => 'clicks',
                            ],
                            'right' => [
                                'type' => 'metric',
                                'channel' => '__CLICKABLE_CHANNEL_2__',
                                'metric' => 'clicks',
                            ],
                        ],
                    ],
                ],
            ],
            'spend_elasticity' => [
                'name' => 'Spend Scalability / Elasticity',
                'description' => 'Find the diminishing returns ceiling. Example: How much does a 10% increase in Paid Spend actually yield in Clicks?',
                'scope' => 'channel',
                'categories' => ['performance', 'cost', 'scalability', 'scope_channel', 'org_mkt_marketing', 'source_src'],
                'required_tags' => ['spendable', 'clickable'],
                'calculation_type' => 'calculate_elasticity',
                'compatible_widgets' => ['gauge', 'tile', 'table', 'scatter_plot'],
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__CLICKABLE_CHANNEL_1__',
                            'metric' => 'clicks',
                        ],
                        'right' => [
                            'type' => 'metric',
                            'channel' => '__SPENDABLE_CHANNEL_1__',
                            'metric' => 'spend',
                        ],
                    ],
                ],
            ],
            'weekly_organic_seasonality' => [
                'name' => 'Weekly Organic Seasonality',
                'description' => 'Prove weekly seasonality for an organic or reach-driven metric.',
                'scope' => 'asset',
                'categories' => ['seasonality', 'organic', 'scope_asset', 'org_mkt_organic', 'source_src'],
                'required_tags' => ['organic_social'],
                'calculation_type' => 'calculate_autocorrelation',
                'compatible_widgets' => ['bar_chart'],
                'template' => [
                    'ast' => [
                        'type' => 'metric',
                        'channel' => '__ORGANIC_SOCIAL_CHANNEL_1__',
                        'metric' => 'reach',
                    ],
                ],
            ],
            'paid_to_organic_halo_effect' => [
                'name' => 'Paid to Organic Halo Effect',
                'description' => 'Predictive Attribution / Halo Effect. Example: Does spending money on Paid Ads cause a delayed spike in Organic Reach?',
                'scope' => 'global',
                'categories' => ['performance', 'cross-channel', 'organic', 'agency', 'scope_global', 'org_mkt_organic', 'org_mkt_marketing', 'source_src'],
                'required_tags' => ['spendable', 'organic_social'],
                'calculation_type' => 'calculate_granger',
                'compatible_widgets' => ['table', 'tile'],
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__ORGANIC_SOCIAL_CHANNEL_1__',
                            'metric' => 'reach',
                        ],
                        'right' => [
                            'type' => 'metric',
                            'channel' => '__SPENDABLE_CHANNEL_1__',
                            'metric' => 'spend',
                        ],
                    ],
                ],
            ],
            'cpc_momentum' => [
                'name' => 'CPC Momentum (MACD)',
                'description' => 'Detect when performance momentum flips. Example: Is our CPC getting cheaper or more expensive on a rolling basis?',
                'scope' => 'channel',
                'categories' => ['cost', 'trends', 'scope_channel', 'org_mkt_marketing', 'source_src'],
                'required_tags' => ['spendable', 'clickable'],
                'calculation_type' => 'calculate_macd',
                'compatible_widgets' => ['combo_chart', 'line_chart'],
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__SPENDABLE_CHANNEL_1__',
                            'metric' => 'spend',
                        ],
                        'right' => [
                            'type' => 'metric',
                            'channel' => '__CLICKABLE_CHANNEL_1__',
                            'metric' => 'clicks',
                        ],
                    ],
                ],
            ],
            'impression_anomaly_alert' => [
                'name' => 'Impression Anomaly Alert',
                'description' => 'Automated Alerting for unexpected spikes in impressions.',
                'scope' => 'asset',
                'categories' => ['impressions', 'alerts', 'scope_asset', 'org_mkt_marketing', 'source_src'],
                'required_tags' => ['impressionable'],
                'calculation_type' => 'calculate_anomaly',
                'compatible_widgets' => ['anomaly_chart', 'tile', 'gauge'],
                'template' => [
                    'ast' => [
                        'type' => 'metric',
                        'channel' => '__IMPRESSIONABLE_CHANNEL_1__',
                        'metric' => 'impressions',
                    ],
                ],
            ],
            'seo_click_momentum' => [
                'name' => 'SEO Click Momentum',
                'description' => 'Detect whether your organic search clicks are gaining or losing momentum over time.',
                'scope' => 'asset',
                'categories' => ['clicks', 'trends', 'seo', 'scope_asset', 'org_mkt_organic', 'source_src'],
                'required_tags' => ['seo', 'clickable'],
                'calculation_type' => 'calculate_macd',
                'compatible_widgets' => ['combo_chart', 'line_chart'],
                'template' => [
                    'ast' => [
                        'type' => 'metric',
                        'channel' => '__SEO_CHANNEL_1__',
                        'metric' => 'clicks',
                    ],
                ],
            ],
            'reach_elasticity' => [
                'name' => 'Reach Scalability / Elasticity',
                'description' => 'Find your audience saturation ceiling. How much does a 10% increase in spend actually expand your reach?',
                'scope' => 'channel',
                'categories' => ['impressions', 'scalability', 'cost', 'scope_channel', 'org_mkt_marketing', 'source_src'],
                'required_tags' => ['spendable', 'impressionable'],
                'calculation_type' => 'calculate_elasticity',
                'compatible_widgets' => ['gauge', 'tile', 'table', 'scatter_plot'],
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__IMPRESSIONABLE_CHANNEL_1__',
                            'metric' => 'impressions',
                        ],
                        'right' => [
                            'type' => 'metric',
                            'channel' => '__SPENDABLE_CHANNEL_1__',
                            'metric' => 'spend',
                        ],
                    ],
                ],
            ],
            'content_half_life' => [
                'name' => 'Content Half-Life',
                'description' => 'Measure how quickly your organic content engagement decays over time.',
                'scope' => 'asset',
                'categories' => ['seasonality', 'organic', 'performance', 'scope_asset', 'org_mkt_organic', 'source_src'],
                'required_tags' => ['organic_social', 'reach_driven'],
                'calculation_type' => 'calculate_autocorrelation',
                'compatible_widgets' => ['bar_chart'],
                'template' => [
                    'ast' => [
                        'type' => 'metric',
                        'channel' => '__ORGANIC_SOCIAL_CHANNEL_1__',
                        'metric' => 'reach',
                    ],
                ],
            ],
            'paid_organic_cannibalization' => [
                'name' => 'Paid to Organic Cannibalization',
                'description' => 'Detect if paid campaigns are stealing reach from your organic content.',
                'scope' => 'global',
                'categories' => ['cross-channel', 'performance', 'organic', 'scope_global', 'org_mkt_organic', 'org_mkt_marketing', 'source_src'],
                'required_tags' => ['spendable', 'organic_social', 'impressionable'],
                'calculation_type' => 'calculate_granger',
                'compatible_widgets' => ['table', 'tile'],
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__ORGANIC_SOCIAL_CHANNEL_1__',
                            'metric' => 'reach',
                        ],
                        'right' => [
                            'type' => 'metric',
                            'channel' => '__SPENDABLE_CHANNEL_1__',
                            'metric' => 'spend',
                        ],
                    ],
                ],
            ],
            'ctr_efficiency_page' => [
                'name' => 'CTR Efficiency (By Page)',
                'description' => 'Evaluates by landing page: Is your organic search presence becoming more or less effective at turning impressions into clicks?',
                'scope' => 'asset',
                'categories' => ['clicks', 'impressions', 'seo', 'scope_asset', 'org_mkt_organic', 'source_src'],
                'required_tags' => ['seo', 'clickable', 'impressionable'],
                'calculation_type' => 'calculate_regression',
                'compatible_widgets' => ['gauge', 'tile', 'table', 'scatter_plot'],
                'default_granularity' => 'page',
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__SEO_CHANNEL_1__',
                            'metric' => 'clicks',
                        ],
                        'right' => [
                            'type' => 'metric',
                            'channel' => '__SEO_CHANNEL_1__',
                            'metric' => 'impressions',
                        ],
                    ],
                ],
            ],
            'ctr_efficiency_query' => [
                'name' => 'CTR Efficiency (By Keyword)',
                'description' => 'Evaluates by search term: Is your organic search presence becoming more or less effective at turning impressions into clicks?',
                'scope' => 'asset',
                'categories' => ['clicks', 'impressions', 'seo', 'scope_asset', 'org_mkt_organic', 'source_src'],
                'required_tags' => ['seo', 'clickable', 'impressionable'],
                'calculation_type' => 'calculate_regression',
                'compatible_widgets' => ['gauge', 'tile', 'table', 'scatter_plot'],
                'default_granularity' => 'query',
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__SEO_CHANNEL_1__',
                            'metric' => 'clicks',
                        ],
                        'right' => [
                            'type' => 'metric',
                            'channel' => '__SEO_CHANNEL_1__',
                            'metric' => 'impressions',
                        ],
                    ],
                ],
            ],
            'revenue_elasticity' => [
                'name' => 'Revenue Elasticity (ROAS)',
                'description' => 'Measures how revenue scales with your ad spend. Tells you if you\'re still in the sweet spot or past it.',
                'scope' => 'global',
                'categories' => ['performance', 'results', 'scalability', 'agency', 'scope_global', 'org_mkt_marketing', 'source_src', 'source_tracking'],
                'required_tags' => ['spendable', 'revenue_tracked'],
                'calculation_type' => 'calculate_elasticity',
                'compatible_widgets' => ['gauge', 'tile', 'table', 'scatter_plot'],
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__REVENUE_TRACKED_CHANNEL_1__',
                            'metric' => 'revenue',
                        ],
                        'right' => [
                            'type' => 'metric',
                            'channel' => '__SPENDABLE_CHANNEL_1__',
                            'metric' => 'spend',
                        ],
                    ],
                ],
            ],
            'cpa_trend' => [
                'name' => 'CPA Trend (Cost per Acquisition)',
                'description' => 'Detect whether your cost per acquisition is trending up or down over time.',
                'scope' => 'channel',
                'categories' => ['cost', 'results', 'trends', 'agency', 'scope_channel', 'org_mkt_marketing', 'source_src', 'source_tracking'],
                'required_tags' => ['spendable', 'conversion_tracked'],
                'calculation_type' => 'calculate_macd',
                'compatible_widgets' => ['combo_chart', 'line_chart'],
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__SPENDABLE_CHANNEL_1__',
                            'metric' => 'spend',
                        ],
                        'right' => [
                            'type' => 'metric',
                            'channel' => '__CONVERSION_TRACKED_CHANNEL_1__',
                            'metric' => 'conversions',
                        ],
                    ],
                ],
            ],
            'seo_to_revenue_influence' => [
                'name' => 'SEO to Revenue Influence',
                'description' => 'Tests whether your organic search traffic predicts future revenue. Is SEO driving the bottom line?',
                'scope' => 'global',
                'categories' => ['seo', 'results', 'cross-channel', 'agency', 'scope_global', 'org_mkt_organic', 'org_mkt_marketing', 'source_src', 'source_tracking'],
                'required_tags' => ['seo', 'revenue_tracked'],
                'calculation_type' => 'calculate_granger',
                'compatible_widgets' => ['table', 'tile'],
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__REVENUE_TRACKED_CHANNEL_1__',
                            'metric' => 'revenue',
                        ],
                        'right' => [
                            'type' => 'metric',
                            'channel' => '__SEO_CHANNEL_1__',
                            'metric' => 'clicks',
                        ],
                    ],
                ],
            ],
            'result_efficiency' => [
                'name' => 'Result Efficiency',
                'description' => 'How efficiently does spend convert to results? The regression slope reveals your marginal cost per result and whether campaign efficiency is improving over time.',
                'scope' => 'channel',
                'categories' => ['results', 'performance', 'cost', 'agency', 'scope_channel', 'org_mkt_marketing', 'source_src', 'source_tracking'],
                'required_tags' => ['spendable'],
                'calculation_type' => 'calculate_regression',
                'compatible_widgets' => ['gauge', 'tile', 'table', 'scatter_plot'],
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__SPENDABLE_CHANNEL_1__',
                            'metric' => 'results',
                        ],
                        'right' => [
                            'type' => 'metric',
                            'channel' => '__SPENDABLE_CHANNEL_1__',
                            'metric' => 'spend',
                        ],
                    ],
                ],
            ],
            'result_rate_momentum' => [
                'name' => 'Result Rate Momentum',
                'description' => 'Is your conversion rate gaining or losing momentum? Early warning signal for campaign effectiveness changes before they impact aggregate numbers.',
                'scope' => 'channel',
                'categories' => ['results', 'trends', 'agency', 'scope_channel', 'org_mkt_marketing', 'source_src', 'source_tracking'],
                'required_tags' => ['spendable', 'impressionable'],
                'calculation_type' => 'calculate_macd',
                'compatible_widgets' => ['combo_chart', 'line_chart'],
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__SPENDABLE_CHANNEL_1__',
                            'metric' => 'results',
                        ],
                        'right' => [
                            'type' => 'metric',
                            'channel' => '__IMPRESSIONABLE_CHANNEL_1__',
                            'metric' => 'impressions',
                        ],
                    ],
                ],
            ],
            'organic_engagement_efficiency' => [
                'name' => 'Organic Engagement Efficiency',
                'description' => 'Measures how engaging your organic content is per person reached. A declining trend signals audience fatigue or declining content relevance.',
                'scope' => 'asset',
                'categories' => ['organic', 'performance', 'agency', 'scope_asset', 'org_mkt_organic', 'source_src'],
                'required_tags' => ['organic_social', 'reach_driven'],
                'calculation_type' => 'calculate_regression',
                'compatible_widgets' => ['gauge', 'tile', 'table', 'scatter_plot'],
                'default_granularity' => 'post',
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__ORGANIC_SOCIAL_CHANNEL_1__',
                            'metric' => 'engaged_users',
                        ],
                        'right' => [
                            'type' => 'metric',
                            'channel' => '__ORGANIC_SOCIAL_CHANNEL_1__',
                            'metric' => 'reach',
                        ],
                    ],
                ],
            ],
            'roas_momentum' => [
                'name' => 'ROAS Momentum',
                'description' => 'Detects shifts in Return on Ad Spend momentum before they become obvious in aggregate numbers. Early warning system for profitability changes.',
                'scope' => 'channel',
                'categories' => ['results', 'trends', 'cost', 'agency', 'scope_channel', 'org_mkt_marketing', 'source_tracking'],
                'required_tags' => ['spendable'],
                'calculation_type' => 'calculate_macd',
                'compatible_widgets' => ['combo_chart', 'line_chart'],
                'template' => [
                    'ast' => [
                        'type' => 'metric',
                        'channel' => '__SPENDABLE_CHANNEL_1__',
                        'metric' => 'purchase_roas',
                    ],
                ],
            ],
            'cpc_anomaly' => [
                'name' => 'CPC Anomaly Alert',
                'description' => 'Alerts when your Cost Per Click deviates significantly from normal range. Helps catch auction changes, competitive shifts, or tracking issues early.',
                'scope' => 'asset',
                'categories' => ['cost', 'alerts', 'agency', 'scope_asset', 'org_mkt_marketing', 'source_src'],
                'required_tags' => ['spendable'],
                'calculation_type' => 'calculate_anomaly',
                'compatible_widgets' => ['anomaly_chart', 'tile', 'gauge'],
                'template' => [
                    'ast' => [
                        'type' => 'metric',
                        'channel' => '__SPENDABLE_CHANNEL_1__',
                        'metric' => 'cpc',
                    ],
                ],
            ],
            'search_position_efficiency_page' => [
                'name' => 'Search Position Efficiency (By Page)',
                'description' => 'Evaluates by landing page: How many clicks do you get per unit of search position? Higher means more compelling snippets.',
                'scope' => 'asset',
                'categories' => ['seo', 'performance', 'agency', 'scope_asset', 'org_mkt_organic', 'source_src'],
                'required_tags' => ['seo'],
                'calculation_type' => 'calculate_regression',
                'compatible_widgets' => ['gauge', 'tile', 'table', 'scatter_plot'],
                'default_granularity' => 'page',
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__SEO_CHANNEL_1__',
                            'metric' => 'clicks',
                        ],
                        'right' => [
                            'type' => 'metric',
                            'channel' => '__SEO_CHANNEL_1__',
                            'metric' => 'position',
                        ],
                    ],
                ],
            ],
            'search_position_efficiency_query' => [
                'name' => 'Search Position Efficiency (By Keyword)',
                'description' => 'Evaluates by search term: How many clicks do you get per unit of search position? Higher means more compelling snippets.',
                'scope' => 'asset',
                'categories' => ['seo', 'performance', 'agency', 'scope_asset', 'org_mkt_organic', 'source_src'],
                'required_tags' => ['seo'],
                'calculation_type' => 'calculate_regression',
                'compatible_widgets' => ['gauge', 'tile', 'table', 'scatter_plot'],
                'default_granularity' => 'query',
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__SEO_CHANNEL_1__',
                            'metric' => 'clicks',
                        ],
                        'right' => [
                            'type' => 'metric',
                            'channel' => '__SEO_CHANNEL_1__',
                            'metric' => 'position',
                        ],
                    ],
                ],
            ],
            'seo_structural_inertia' => [
                'name' => 'SEO Structural Inertia (Linear + SMA)',
                'description' => 'Calculates the underlying growth trend of your organic search presence by combining Linear Regression and a 28-day Simple Moving Average, filtering out minor algorithmic updates.',
                'scope' => 'asset',
                'categories' => ['seo', 'trends', 'performance', 'scope_asset', 'org_mkt_organic', 'source_src'],
                'required_tags' => ['seo', 'impressionable'],
                'calculation_type' => 'calculate_trend_linear',
                'compatible_widgets' => ['line_chart', 'sparkline', 'tile'],
                'template' => [
                    'ast' => [
                        'type' => 'metric',
                        'channel' => '__SEO_CHANNEL_1__',
                        'metric' => 'impressions',
                    ],
                ],
            ],
            'fb_algorithmic_inertia' => [
                'name' => 'Algorithmic Basal Inertia (Holt-Winters)',
                'description' => 'Isolates the true algorithmic distribution floor of your Facebook Page by mathematically removing weekly seasonality using Triple Exponential Smoothing.',
                'scope' => 'asset',
                'categories' => ['organic', 'trends', 'seasonality', 'scope_asset', 'org_mkt_organic', 'source_src'],
                'required_tags' => ['organic_social', 'reach_driven'],
                'calculation_type' => 'calculate_trend_holt_winters',
                'compatible_widgets' => ['line_chart', 'sparkline', 'tile'],
                'template' => [
                    'ast' => [
                        'type' => 'metric',
                        'channel' => '__ORGANIC_SOCIAL_CHANNEL_1__',
                        'metric' => 'reach',
                    ],
                ],
            ],
            'ig_viral_momentum' => [
                'name' => 'Viral Momentum (Logarithmic Trend)',
                'description' => 'Determines if an Instagram post has broken the standard temporal decay curve by applying a Logarithmic Regression against interaction velocity.',
                'scope' => 'asset',
                'categories' => ['organic', 'trends', 'performance', 'scope_asset', 'org_mkt_organic', 'source_src'],
                'required_tags' => ['organic_social', 'reach_driven'],
                'calculation_type' => 'calculate_trend_logarithmic',
                'compatible_widgets' => ['line_chart', 'sparkline', 'tile'],
                'template' => [
                    'ast' => [
                        'type' => 'metric',
                        'channel' => '__ORGANIC_SOCIAL_CHANNEL_1__',
                        'metric' => 'total_interactions',
                    ],
                ],
            ],
            'paid_learning_inertia' => [
                'name' => 'Learning Phase Inertia (EMA Crossover)',
                'description' => 'Uses Exponential Moving Average crossovers (EMA 7 vs EMA 14) to mathematically confirm when an ad campaign\'s cost efficiency trend has genuinely shifted due to optimization.',
                'scope' => 'channel',
                'categories' => ['cost', 'trends', 'performance', 'scope_channel', 'org_mkt_marketing', 'source_src'],
                'required_tags' => ['spendable'],
                'calculation_type' => 'calculate_trend_ema',
                'compatible_widgets' => ['line_chart', 'sparkline', 'tile'],
                'template' => [
                    'ast' => [
                        'type' => 'metric',
                        'channel' => '__SPENDABLE_CHANNEL_1__',
                        'metric' => 'cost_per_result',
                    ],
                ],
            ],
            'seo_intent_match' => [
                'name' => 'SEO Intent Match (Bounce Rate vs Clicks)',
                'description' => 'Compares organic search clicks with bounce rate to identify if the content matches search intent.',
                'scope' => 'asset',
                'categories' => ['seo', 'performance', 'scope_asset', 'org_mkt_organic', 'source_src'],
                'required_tags' => ['seo', 'behavior_tracked'],
                'calculation_type' => 'calculate_regression',
                'compatible_widgets' => ['gauge', 'tile', 'table', 'scatter_plot'],
                'default_granularity' => 'page',
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__BEHAVIOR_TRACKED_CHANNEL_1__',
                            'metric' => 'bounce_rate',
                        ],
                        'right' => [
                            'type' => 'metric',
                            'channel' => '__SEO_CHANNEL_1__',
                            'metric' => 'clicks',
                        ],
                    ],
                ],
            ],
            'organic_conversion_elasticity' => [
                'name' => 'Organic Conversion Elasticity',
                'description' => 'Measures how much real site conversions scale for every point improved in organic search position/clicks.',
                'scope' => 'global',
                'categories' => ['seo', 'results', 'scalability', 'scope_global', 'org_mkt_organic', 'source_src'],
                'required_tags' => ['seo', 'conversion_tracked'],
                'calculation_type' => 'calculate_elasticity',
                'compatible_widgets' => ['gauge', 'tile', 'table', 'scatter_plot'],
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__CONVERSION_TRACKED_CHANNEL_1__',
                            'metric' => 'conversions',
                        ],
                        'right' => [
                            'type' => 'metric',
                            'channel' => '__SEO_CHANNEL_1__',
                            'metric' => 'clicks',
                        ],
                    ],
                ],
            ],
            'seo_engagement_quality' => [
                'name' => 'SEO Engagement Quality',
                'description' => 'A quality indicator measuring session duration driven by organic landing pages.',
                'scope' => 'asset',
                'categories' => ['seo', 'performance', 'scope_asset', 'org_mkt_organic', 'source_src'],
                'required_tags' => ['seo', 'behavior_tracked'],
                'calculation_type' => 'calculate_regression',
                'compatible_widgets' => ['gauge', 'tile', 'table', 'scatter_plot'],
                'default_granularity' => 'page',
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__BEHAVIOR_TRACKED_CHANNEL_1__',
                            'metric' => 'average_session_duration',
                        ],
                        'right' => [
                            'type' => 'metric',
                            'channel' => '__SEO_CHANNEL_1__',
                            'metric' => 'clicks',
                        ],
                    ],
                ],
            ],
            'toxic_page_detector' => [
                'name' => 'Toxic Page Detector',
                'description' => 'Identifies specific landing pages with high propensity for bouncing.',
                'scope' => 'asset',
                'categories' => ['seo', 'alerts', 'scope_asset', 'org_mkt_organic', 'source_src'],
                'required_tags' => ['seo', 'behavior_tracked'],
                'calculation_type' => 'calculate_regression',
                'compatible_widgets' => ['gauge', 'tile', 'table', 'scatter_plot'],
                'default_granularity' => 'page',
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__BEHAVIOR_TRACKED_CHANNEL_1__',
                            'metric' => 'bounce_rate',
                        ],
                        'right' => [
                            'type' => 'metric',
                            'channel' => '__SEO_CHANNEL_1__',
                            'metric' => 'clicks',
                        ],
                    ],
                ],
            ],
            'paid_acquisition_saturation' => [
                'name' => 'Paid Acquisition Saturation',
                'description' => 'Crosses Meta ad spend against New Users acquired to reveal audience saturation.',
                'scope' => 'channel',
                'categories' => ['performance', 'scalability', 'cost', 'scope_channel', 'org_mkt_marketing', 'source_src'],
                'required_tags' => ['spendable', 'traffic_tracked'],
                'calculation_type' => 'calculate_elasticity',
                'compatible_widgets' => ['gauge', 'tile', 'table', 'scatter_plot'],
                'default_granularity' => 'country',
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__TRAFFIC_TRACKED_CHANNEL_1__',
                            'metric' => 'new_users',
                        ],
                        'right' => [
                            'type' => 'metric',
                            'channel' => '__SPENDABLE_CHANNEL_1__',
                            'metric' => 'spend',
                        ],
                    ],
                ],
            ],
            'click_to_session_drop_off' => [
                'name' => 'Click-to-Session Drop-off',
                'description' => 'Detects percentage loss between ad clicks charged and actual web sessions.',
                'scope' => 'channel',
                'categories' => ['performance', 'alerts', 'scope_channel', 'org_mkt_marketing', 'source_src'],
                'required_tags' => ['paid_media', 'traffic_tracked'],
                'calculation_type' => 'calculate_regression',
                'compatible_widgets' => ['gauge', 'tile', 'table', 'scatter_plot'],
                'default_granularity' => 'device',
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__TRAFFIC_TRACKED_CHANNEL_1__',
                            'metric' => 'sessions',
                        ],
                        'right' => [
                            'type' => 'metric',
                            'channel' => '__PAID_MEDIA_CHANNEL_1__',
                            'metric' => 'link_clicks',
                        ],
                    ],
                ],
            ],
            'social_viral_to_revenue_pipeline' => [
                'name' => 'Social Viral to Revenue Pipeline',
                'description' => 'Evaluates if organic social virality statistically translates into website revenue in subsequent days.',
                'scope' => 'global',
                'categories' => ['organic', 'cross-channel', 'results', 'scope_global', 'org_mkt_organic', 'source_src'],
                'required_tags' => ['organic_social', 'revenue_tracked'],
                'calculation_type' => 'calculate_granger',
                'compatible_widgets' => ['table', 'tile'],
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__REVENUE_TRACKED_CHANNEL_1__',
                            'metric' => 'revenue',
                        ],
                        'right' => [
                            'type' => 'metric',
                            'channel' => '__ORGANIC_SOCIAL_CHANNEL_1__',
                            'metric' => 'reach',
                        ],
                    ],
                ],
            ],
            'social_traffic_stickiness' => [
                'name' => 'Social Traffic Stickiness',
                'description' => 'Measures how engaged the audience from social media remains after landing on the website.',
                'scope' => 'asset',
                'categories' => ['organic', 'performance', 'scope_asset', 'org_mkt_organic', 'source_src'],
                'required_tags' => ['organic_social', 'behavior_tracked'],
                'calculation_type' => 'calculate_regression',
                'compatible_widgets' => ['gauge', 'tile', 'table', 'scatter_plot'],
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__BEHAVIOR_TRACKED_CHANNEL_1__',
                            'metric' => 'average_session_duration',
                        ],
                        'right' => [
                            'type' => 'metric',
                            'channel' => '__ORGANIC_SOCIAL_CHANNEL_1__',
                            'metric' => 'reach',
                        ],
                    ],
                ],
            ],
            'brand_search_halo_effect' => [
                'name' => 'Brand Search Halo Effect',
                'description' => 'Analyzes if social media efforts generate a delayed increase in brand searches on Google.',
                'scope' => 'global',
                'categories' => ['cross-channel', 'seo', 'performance', 'scope_global', 'org_mkt_marketing', 'source_src'],
                'required_tags' => ['seo', 'spendable', 'organic_social'],
                'calculation_type' => 'calculate_regression',
                'compatible_widgets' => ['gauge', 'tile', 'table', 'scatter_plot'],
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__SEO_CHANNEL_1__',
                            'metric' => 'impressions',
                        ],
                        'right' => [
                            'type' => 'operator',
                            'operator' => '+',
                            'left' => [
                                'type' => 'metric',
                                'channel' => '__SPENDABLE_CHANNEL_1__',
                                'metric' => 'spend',
                            ],
                            'right' => [
                                'type' => 'metric',
                                'channel' => '__ORGANIC_SOCIAL_CHANNEL_1__',
                                'metric' => 'reach',
                            ],
                        ],
                    ],
                ],
            ],
            'omnichannel_revenue_attribution' => [
                'name' => 'Omnichannel Revenue Attribution',
                'description' => 'Multiple regression determining which marketing effort statistically pushes more global revenue.',
                'scope' => 'global',
                'categories' => ['cross-channel', 'results', 'performance', 'scope_global', 'org_mkt_marketing', 'source_src'],
                'required_tags' => ['revenue_tracked', 'seo', 'spendable', 'organic_social'],
                'calculation_type' => 'calculate_regression',
                'compatible_widgets' => ['gauge', 'tile', 'table', 'scatter_plot'],
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__REVENUE_TRACKED_CHANNEL_1__',
                            'metric' => 'revenue',
                        ],
                        'right' => [
                            'type' => 'operator',
                            'operator' => '+',
                            'left' => [
                                'type' => 'metric',
                                'channel' => '__SEO_CHANNEL_1__',
                                'metric' => 'clicks',
                            ],
                            'right' => [
                                'type' => 'operator',
                                'operator' => '+',
                                'left' => [
                                    'type' => 'metric',
                                    'channel' => '__SPENDABLE_CHANNEL_1__',
                                    'metric' => 'spend',
                                ],
                                'right' => [
                                    'type' => 'metric',
                                    'channel' => '__ORGANIC_SOCIAL_CHANNEL_1__',
                                    'metric' => 'reach',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'traffic_to_conversion_inertia' => [
                'name' => 'Traffic to Conversion Inertia',
                'description' => 'Determines the natural conversion rhythm of the isolated site.',
                'scope' => 'global',
                'categories' => ['performance', 'results', 'scope_global', 'org_mkt_marketing', 'source_src'],
                'required_tags' => ['traffic_tracked', 'conversion_tracked'],
                'calculation_type' => 'calculate_elasticity',
                'compatible_widgets' => ['gauge', 'tile', 'table', 'scatter_plot'],
                'template' => [
                    'ast' => [
                        'type' => 'operator',
                        'operator' => '/',
                        'left' => [
                            'type' => 'metric',
                            'channel' => '__CONVERSION_TRACKED_CHANNEL_1__',
                            'metric' => 'conversions',
                        ],
                        'right' => [
                            'type' => 'metric',
                            'channel' => '__TRAFFIC_TRACKED_CHANNEL_1__',
                            'metric' => 'sessions',
                        ],
                    ],
                ],
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
            if (($kpi['status'] ?? 'active') === 'unavailable') {
                return false;
            }

            $requiredTags = $kpi['required_tags'] ?? [];
            return count(array_intersect($requiredTags, $availableTags)) === count($requiredTags);
        })->toArray();
    }
}
