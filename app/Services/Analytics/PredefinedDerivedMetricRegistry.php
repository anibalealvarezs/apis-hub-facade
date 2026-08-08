<?php

namespace App\Services\Analytics;

class PredefinedDerivedMetricRegistry
{
    public static function getPredefined(): array
    {
        return [
            // ================================================================
            // Single-Channel: Paid Media (Facebook Marketing, Google Ads, etc.)
            // ================================================================

            'cpc' => [
                'name' => 'Cost per Click (CPC)',
                'description' => 'Average cost paid for each click on your ads. Lower is better — a rising CPC signals increased competition or audience saturation.',
                'format' => 'currency',
                'output_granularity' => 'daily',
                'categories' => ['cost', 'paid_media', 'performance'],
                'required_tags' => ['spendable', 'clickable'],
                'source_series' => [
                    ['key' => 'a', 'label' => 'Spend', 'channel' => '__SPENDABLE_CHANNEL_1__', 'metric' => 'spend', 'granularity' => 'daily'],
                    ['key' => 'b', 'label' => 'Clicks', 'channel' => '__CLICKABLE_CHANNEL_1__', 'metric' => 'clicks', 'granularity' => 'daily'],
                ],
                'ast' => [
                    'type' => 'operator',
                    'operator' => '/',
                    'left' => ['type' => 'metric', 'metric' => 'a'],
                    'right' => ['type' => 'metric', 'metric' => 'b'],
                ],
            ],

            'ctr' => [
                'name' => 'Click-Through Rate (CTR)',
                'description' => 'Percentage of impressions that resulted in a click. Higher CTR means your ad creative and targeting resonate with the audience.',
                'format' => 'percentage',
                'output_granularity' => 'daily',
                'categories' => ['performance', 'paid_media', 'engagement'],
                'required_tags' => ['clickable', 'impressionable'],
                'source_series' => [
                    ['key' => 'a', 'label' => 'Clicks', 'channel' => '__CLICKABLE_CHANNEL_1__', 'metric' => 'clicks', 'granularity' => 'daily'],
                    ['key' => 'b', 'label' => 'Impressions', 'channel' => '__IMPRESSIONABLE_CHANNEL_1__', 'metric' => 'impressions', 'granularity' => 'daily'],
                ],
                'ast' => [
                    'type' => 'operator',
                    'operator' => '/',
                    'left' => ['type' => 'metric', 'metric' => 'a'],
                    'right' => ['type' => 'metric', 'metric' => 'b'],
                ],
            ],

            'cpa' => [
                'name' => 'Cost per Acquisition / Result (CPA)',
                'description' => 'Average cost to generate one desired result (purchase, lead, signup). The ultimate efficiency metric for conversion-focused campaigns.',
                'format' => 'currency',
                'output_granularity' => 'daily',
                'categories' => ['cost', 'paid_media', 'results', 'performance'],
                'required_tags' => ['spendable'],
                'source_series' => [
                    ['key' => 'a', 'label' => 'Spend', 'channel' => '__SPENDABLE_CHANNEL_1__', 'metric' => 'spend', 'granularity' => 'daily'],
                    ['key' => 'b', 'label' => 'Results', 'channel' => '__SPENDABLE_CHANNEL_1__', 'metric' => 'results', 'granularity' => 'daily'],
                ],
                'ast' => [
                    'type' => 'operator',
                    'operator' => '/',
                    'left' => ['type' => 'metric', 'metric' => 'a'],
                    'right' => ['type' => 'metric', 'metric' => 'b'],
                ],
            ],

            'cvr' => [
                'name' => 'Conversion Rate (CVR)',
                'description' => 'Percentage of clicks that resulted in a conversion. Helps evaluate landing page effectiveness and audience quality.',
                'format' => 'percentage',
                'output_granularity' => 'daily',
                'categories' => ['results', 'paid_media', 'performance'],
                'required_tags' => ['spendable', 'clickable'],
                'source_series' => [
                    ['key' => 'a', 'label' => 'Results', 'channel' => '__SPENDABLE_CHANNEL_1__', 'metric' => 'results', 'granularity' => 'daily'],
                    ['key' => 'b', 'label' => 'Clicks', 'channel' => '__CLICKABLE_CHANNEL_1__', 'metric' => 'clicks', 'granularity' => 'daily'],
                ],
                'ast' => [
                    'type' => 'operator',
                    'operator' => '/',
                    'left' => ['type' => 'metric', 'metric' => 'a'],
                    'right' => ['type' => 'metric', 'metric' => 'b'],
                ],
            ],

            'roas' => [
                'name' => 'Return on Ad Spend (ROAS)',
                'description' => 'Revenue generated per dollar spent on ads. A ROAS of 3.0 means you earn $3 for every $1 spent — the north star for revenue-focused campaigns.',
                'format' => 'decimal',
                'output_granularity' => 'daily',
                'categories' => ['results', 'paid_media', 'revenue', 'performance'],
                'required_tags' => ['spendable', 'revenue_tracked'],
                'source_series' => [
                    ['key' => 'a', 'label' => 'Revenue', 'channel' => '__REVENUE_TRACKED_CHANNEL_1__', 'metric' => 'revenue', 'granularity' => 'daily'],
                    ['key' => 'b', 'label' => 'Spend', 'channel' => '__SPENDABLE_CHANNEL_1__', 'metric' => 'spend', 'granularity' => 'daily'],
                ],
                'ast' => [
                    'type' => 'operator',
                    'operator' => '/',
                    'left' => ['type' => 'metric', 'metric' => 'a'],
                    'right' => ['type' => 'metric', 'metric' => 'b'],
                ],
            ],

            'cost_per_conversion' => [
                'name' => 'Cost per Conversion',
                'description' => 'Average ad spend required to generate one tracked conversion (purchase, signup, lead). Unlike CPA which uses platform results, this uses your own conversion tracking.',
                'format' => 'currency',
                'output_granularity' => 'daily',
                'categories' => ['cost', 'paid_media', 'results', 'performance'],
                'required_tags' => ['spendable', 'conversion_tracked'],
                'source_series' => [
                    ['key' => 'a', 'label' => 'Spend', 'channel' => '__SPENDABLE_CHANNEL_1__', 'metric' => 'spend', 'granularity' => 'daily'],
                    ['key' => 'b', 'label' => 'Conversions', 'channel' => '__CONVERSION_TRACKED_CHANNEL_1__', 'metric' => 'conversions', 'granularity' => 'daily'],
                ],
                'ast' => [
                    'type' => 'operator',
                    'operator' => '/',
                    'left' => ['type' => 'metric', 'metric' => 'a'],
                    'right' => ['type' => 'metric', 'metric' => 'b'],
                ],
            ],

            'result_rate' => [
                'name' => 'Result Rate',
                'description' => 'Percentage of impressions that resulted in a platform result (purchase, lead, etc.). Measures how compelling your ads are at driving action per view.',
                'format' => 'percentage',
                'output_granularity' => 'daily',
                'categories' => ['results', 'paid_media', 'performance'],
                'required_tags' => ['spendable', 'impressionable'],
                'source_series' => [
                    ['key' => 'a', 'label' => 'Results', 'channel' => '__SPENDABLE_CHANNEL_1__', 'metric' => 'results', 'granularity' => 'daily'],
                    ['key' => 'b', 'label' => 'Impressions', 'channel' => '__IMPRESSIONABLE_CHANNEL_1__', 'metric' => 'impressions', 'granularity' => 'daily'],
                ],
                'ast' => [
                    'type' => 'operator',
                    'operator' => '/',
                    'left' => ['type' => 'metric', 'metric' => 'a'],
                    'right' => ['type' => 'metric', 'metric' => 'b'],
                ],
            ],

            'cost_per_engagement' => [
                'name' => 'Cost per Engagement',
                'description' => 'Average cost for each user engagement (like, share, comment). Key metric for brand awareness and engagement-optimized campaigns.',
                'format' => 'currency',
                'output_granularity' => 'daily',
                'categories' => ['cost', 'paid_media', 'engagement'],
                'required_tags' => ['spendable'],
                'source_series' => [
                    ['key' => 'a', 'label' => 'Spend', 'channel' => '__SPENDABLE_CHANNEL_1__', 'metric' => 'spend', 'granularity' => 'daily'],
                    ['key' => 'b', 'label' => 'Engagements', 'channel' => '__SPENDABLE_CHANNEL_1__', 'metric' => 'engagements', 'granularity' => 'daily'],
                ],
                'ast' => [
                    'type' => 'operator',
                    'operator' => '/',
                    'left' => ['type' => 'metric', 'metric' => 'a'],
                    'right' => ['type' => 'metric', 'metric' => 'b'],
                ],
            ],

            'engagement_click_rate' => [
                'name' => 'Engagement Click Rate',
                'description' => 'Percentage of engagements that were clicks. Helps distinguish between passive engagement (likes) and active interest (clicks to learn more).',
                'format' => 'percentage',
                'output_granularity' => 'daily',
                'categories' => ['engagement', 'paid_media', 'performance'],
                'required_tags' => ['spendable', 'clickable'],
                'source_series' => [
                    ['key' => 'a', 'label' => 'Clicks', 'channel' => '__CLICKABLE_CHANNEL_1__', 'metric' => 'clicks', 'granularity' => 'daily'],
                    ['key' => 'b', 'label' => 'Engagements', 'channel' => '__SPENDABLE_CHANNEL_1__', 'metric' => 'engagements', 'granularity' => 'daily'],
                ],
                'ast' => [
                    'type' => 'operator',
                    'operator' => '/',
                    'left' => ['type' => 'metric', 'metric' => 'a'],
                    'right' => ['type' => 'metric', 'metric' => 'b'],
                ],
            ],

            // ================================================================
            // Single-Channel: Organic Social (Facebook Organic)
            // ================================================================

            'organic_engagement_rate' => [
                'name' => 'Organic Engagement Rate',
                'description' => 'Percentage of people reached who engaged with your organic content. A declining trend signals audience fatigue or declining content relevance.',
                'format' => 'percentage',
                'output_granularity' => 'daily',
                'categories' => ['organic', 'engagement', 'social'],
                'required_tags' => ['organic_social', 'reach_driven'],
                'source_series' => [
                    ['key' => 'a', 'label' => 'Engaged Users', 'channel' => '__ORGANIC_SOCIAL_CHANNEL_1__', 'metric' => 'engaged_users', 'granularity' => 'daily'],
                    ['key' => 'b', 'label' => 'Reach', 'channel' => '__ORGANIC_SOCIAL_CHANNEL_1__', 'metric' => 'reach', 'granularity' => 'daily'],
                ],
                'ast' => [
                    'type' => 'operator',
                    'operator' => '/',
                    'left' => ['type' => 'metric', 'metric' => 'a'],
                    'right' => ['type' => 'metric', 'metric' => 'b'],
                ],
            ],

            'organic_reach_efficiency' => [
                'name' => 'Organic Reach per Impression',
                'description' => 'How many people were reached per impression served. Higher means your content has strong distribution in the feed; a drop may signal algorithmic reach suppression.',
                'format' => 'percentage',
                'output_granularity' => 'daily',
                'categories' => ['organic', 'reach', 'social'],
                'required_tags' => ['organic_social', 'impressionable'],
                'source_series' => [
                    ['key' => 'a', 'label' => 'Reach', 'channel' => '__ORGANIC_SOCIAL_CHANNEL_1__', 'metric' => 'reach', 'granularity' => 'daily'],
                    ['key' => 'b', 'label' => 'Impressions', 'channel' => '__IMPRESSIONABLE_CHANNEL_1__', 'metric' => 'impressions', 'granularity' => 'daily'],
                ],
                'ast' => [
                    'type' => 'operator',
                    'operator' => '/',
                    'left' => ['type' => 'metric', 'metric' => 'a'],
                    'right' => ['type' => 'metric', 'metric' => 'b'],
                ],
            ],

            'organic_impression_engagement' => [
                'name' => 'Engagement per Impression',
                'description' => 'Percentage of impressions that led to an engagement. A surface-level indicator of content appeal — low values suggest your content stops users from scrolling.',
                'format' => 'percentage',
                'output_granularity' => 'daily',
                'categories' => ['organic', 'engagement', 'social'],
                'required_tags' => ['organic_social', 'impressionable'],
                'source_series' => [
                    ['key' => 'a', 'label' => 'Engaged Users', 'channel' => '__ORGANIC_SOCIAL_CHANNEL_1__', 'metric' => 'engaged_users', 'granularity' => 'daily'],
                    ['key' => 'b', 'label' => 'Impressions', 'channel' => '__IMPRESSIONABLE_CHANNEL_1__', 'metric' => 'impressions', 'granularity' => 'daily'],
                ],
                'ast' => [
                    'type' => 'operator',
                    'operator' => '/',
                    'left' => ['type' => 'metric', 'metric' => 'a'],
                    'right' => ['type' => 'metric', 'metric' => 'b'],
                ],
            ],

            // ================================================================
            // Single-Channel: SEO / Search Console
            // ================================================================

            'seo_ctr' => [
                'name' => 'SEO Click-Through Rate (CTR)',
                'description' => 'Percentage of search impressions that resulted in a click. Low CTR for high rankings may indicate weak meta titles/descriptions or mismatch with search intent.',
                'format' => 'percentage',
                'output_granularity' => 'daily',
                'categories' => ['seo', 'performance', 'clicks'],
                'required_tags' => ['seo', 'clickable', 'impressionable'],
                'source_series' => [
                    ['key' => 'a', 'label' => 'Clicks', 'channel' => '__SEO_CHANNEL_1__', 'metric' => 'clicks', 'granularity' => 'daily'],
                    ['key' => 'b', 'label' => 'Impressions', 'channel' => '__SEO_CHANNEL_1__', 'metric' => 'impressions', 'granularity' => 'daily'],
                ],
                'ast' => [
                    'type' => 'operator',
                    'operator' => '/',
                    'left' => ['type' => 'metric', 'metric' => 'a'],
                    'right' => ['type' => 'metric', 'metric' => 'b'],
                ],
            ],

            'click_position_efficiency' => [
                'name' => 'Click Position Efficiency',
                'description' => 'Clicks generated per unit of search position. A higher number means your snippet earns more clicks than expected for its ranking — signals compelling meta data.',
                'format' => 'decimal',
                'output_granularity' => 'daily',
                'categories' => ['seo', 'performance', 'clicks'],
                'required_tags' => ['seo', 'clickable'],
                'source_series' => [
                    ['key' => 'a', 'label' => 'Clicks', 'channel' => '__SEO_CHANNEL_1__', 'metric' => 'clicks', 'granularity' => 'daily'],
                    ['key' => 'b', 'label' => 'Avg. Position', 'channel' => '__SEO_CHANNEL_1__', 'metric' => 'position', 'granularity' => 'daily'],
                ],
                'ast' => [
                    'type' => 'operator',
                    'operator' => '/',
                    'left' => ['type' => 'metric', 'metric' => 'a'],
                    'right' => ['type' => 'metric', 'metric' => 'b'],
                ],
            ],

            'impression_position_efficiency' => [
                'name' => 'Impression Position Efficiency',
                'description' => 'Impressions generated per unit of search position. Helps identify pages that rank lower but still earn substantial visibility — potential quick-win optimization targets.',
                'format' => 'decimal',
                'output_granularity' => 'daily',
                'categories' => ['seo', 'performance', 'impressions'],
                'required_tags' => ['seo', 'impressionable'],
                'source_series' => [
                    ['key' => 'a', 'label' => 'Impressions', 'channel' => '__SEO_CHANNEL_1__', 'metric' => 'impressions', 'granularity' => 'daily'],
                    ['key' => 'b', 'label' => 'Avg. Position', 'channel' => '__SEO_CHANNEL_1__', 'metric' => 'position', 'granularity' => 'daily'],
                ],
                'ast' => [
                    'type' => 'operator',
                    'operator' => '/',
                    'left' => ['type' => 'metric', 'metric' => 'a'],
                    'right' => ['type' => 'metric', 'metric' => 'b'],
                ],
            ],

            // ================================================================
            // Cross-Channel: Multiple Paid Channels
            // ================================================================

            'blended_cpc' => [
                'name' => 'Blended Cross-Channel CPC',
                'description' => 'Combined cost per click across multiple paid channels. Gives a true picture of your aggregate click efficiency — a single number to track overall paid performance.',
                'format' => 'currency',
                'output_granularity' => 'daily',
                'categories' => ['cross-channel', 'cost', 'paid_media', 'performance'],
                'required_tags' => ['spendable', 'clickable'],
                'source_series' => [
                    ['key' => 'a', 'label' => 'Spend (Channel 1)', 'channel' => '__SPENDABLE_CHANNEL_1__', 'metric' => 'spend', 'granularity' => 'daily'],
                    ['key' => 'b', 'label' => 'Spend (Channel 2)', 'channel' => '__SPENDABLE_CHANNEL_2__', 'metric' => 'spend', 'granularity' => 'daily'],
                    ['key' => 'c', 'label' => 'Clicks (Channel 1)', 'channel' => '__CLICKABLE_CHANNEL_1__', 'metric' => 'clicks', 'granularity' => 'daily'],
                    ['key' => 'd', 'label' => 'Clicks (Channel 2)', 'channel' => '__CLICKABLE_CHANNEL_2__', 'metric' => 'clicks', 'granularity' => 'daily'],
                ],
                'ast' => [
                    'type' => 'operator',
                    'operator' => '/',
                    'left' => [
                        'type' => 'operator',
                        'operator' => '+',
                        'left' => ['type' => 'metric', 'metric' => 'a'],
                        'right' => ['type' => 'metric', 'metric' => 'b'],
                    ],
                    'right' => [
                        'type' => 'operator',
                        'operator' => '+',
                        'left' => ['type' => 'metric', 'metric' => 'c'],
                        'right' => ['type' => 'metric', 'metric' => 'd'],
                    ],
                ],
            ],

            'blended_cpa' => [
                'name' => 'Blended Cross-Channel CPA',
                'description' => 'Combined cost per acquisition across multiple paid channels. Essential for understanding true aggregate conversion cost when running campaigns on multiple platforms.',
                'format' => 'currency',
                'output_granularity' => 'daily',
                'categories' => ['cross-channel', 'cost', 'paid_media', 'results'],
                'required_tags' => ['spendable'],
                'source_series' => [
                    ['key' => 'a', 'label' => 'Spend (Channel 1)', 'channel' => '__SPENDABLE_CHANNEL_1__', 'metric' => 'spend', 'granularity' => 'daily'],
                    ['key' => 'b', 'label' => 'Spend (Channel 2)', 'channel' => '__SPENDABLE_CHANNEL_2__', 'metric' => 'spend', 'granularity' => 'daily'],
                    ['key' => 'c', 'label' => 'Results (Channel 1)', 'channel' => '__SPENDABLE_CHANNEL_1__', 'metric' => 'results', 'granularity' => 'daily'],
                    ['key' => 'd', 'label' => 'Results (Channel 2)', 'channel' => '__SPENDABLE_CHANNEL_2__', 'metric' => 'results', 'granularity' => 'daily'],
                ],
                'ast' => [
                    'type' => 'operator',
                    'operator' => '/',
                    'left' => [
                        'type' => 'operator',
                        'operator' => '+',
                        'left' => ['type' => 'metric', 'metric' => 'a'],
                        'right' => ['type' => 'metric', 'metric' => 'b'],
                    ],
                    'right' => [
                        'type' => 'operator',
                        'operator' => '+',
                        'left' => ['type' => 'metric', 'metric' => 'c'],
                        'right' => ['type' => 'metric', 'metric' => 'd'],
                    ],
                ],
            ],

            'blended_ctr' => [
                'name' => 'Blended Cross-Channel CTR',
                'description' => 'Combined click-through rate across multiple paid channels. Isolates aggregate creative performance from channel-specific delivery differences.',
                'format' => 'percentage',
                'output_granularity' => 'daily',
                'categories' => ['cross-channel', 'paid_media', 'performance', 'engagement'],
                'required_tags' => ['clickable', 'impressionable'],
                'source_series' => [
                    ['key' => 'a', 'label' => 'Clicks (Channel 1)', 'channel' => '__CLICKABLE_CHANNEL_1__', 'metric' => 'clicks', 'granularity' => 'daily'],
                    ['key' => 'b', 'label' => 'Clicks (Channel 2)', 'channel' => '__CLICKABLE_CHANNEL_2__', 'metric' => 'clicks', 'granularity' => 'daily'],
                    ['key' => 'c', 'label' => 'Impressions (Channel 1)', 'channel' => '__IMPRESSIONABLE_CHANNEL_1__', 'metric' => 'impressions', 'granularity' => 'daily'],
                    ['key' => 'd', 'label' => 'Impressions (Channel 2)', 'channel' => '__IMPRESSIONABLE_CHANNEL_2__', 'metric' => 'impressions', 'granularity' => 'daily'],
                ],
                'ast' => [
                    'type' => 'operator',
                    'operator' => '/',
                    'left' => [
                        'type' => 'operator',
                        'operator' => '+',
                        'left' => ['type' => 'metric', 'metric' => 'a'],
                        'right' => ['type' => 'metric', 'metric' => 'b'],
                    ],
                    'right' => [
                        'type' => 'operator',
                        'operator' => '+',
                        'left' => ['type' => 'metric', 'metric' => 'c'],
                        'right' => ['type' => 'metric', 'metric' => 'd'],
                    ],
                ],
            ],

            'blended_roas' => [
                'name' => 'Blended Cross-Channel ROAS',
                'description' => 'Return on ad spend aggregated across all revenue-tracked paid channels. The single most important number for understanding whether your overall paid strategy is profitable.',
                'format' => 'decimal',
                'output_granularity' => 'daily',
                'categories' => ['cross-channel', 'paid_media', 'revenue', 'results'],
                'required_tags' => ['spendable', 'revenue_tracked'],
                'source_series' => [
                    ['key' => 'a', 'label' => 'Revenue (Channel 1)', 'channel' => '__REVENUE_TRACKED_CHANNEL_1__', 'metric' => 'revenue', 'granularity' => 'daily'],
                    ['key' => 'b', 'label' => 'Revenue (Channel 2)', 'channel' => '__REVENUE_TRACKED_CHANNEL_2__', 'metric' => 'revenue', 'granularity' => 'daily'],
                    ['key' => 'c', 'label' => 'Spend (Channel 1)', 'channel' => '__SPENDABLE_CHANNEL_1__', 'metric' => 'spend', 'granularity' => 'daily'],
                    ['key' => 'd', 'label' => 'Spend (Channel 2)', 'channel' => '__SPENDABLE_CHANNEL_2__', 'metric' => 'spend', 'granularity' => 'daily'],
                ],
                'ast' => [
                    'type' => 'operator',
                    'operator' => '/',
                    'left' => [
                        'type' => 'operator',
                        'operator' => '+',
                        'left' => ['type' => 'metric', 'metric' => 'a'],
                        'right' => ['type' => 'metric', 'metric' => 'b'],
                    ],
                    'right' => [
                        'type' => 'operator',
                        'operator' => '+',
                        'left' => ['type' => 'metric', 'metric' => 'c'],
                        'right' => ['type' => 'metric', 'metric' => 'd'],
                    ],
                ],
            ],

            'budget_share_ratio' => [
                'name' => 'Budget Allocation Ratio',
                'description' => 'Ratio of spend between two paid channels. A value of 2.0 means Channel A is receiving twice the budget of Channel B. Helps monitor portfolio balance.',
                'format' => 'decimal',
                'output_granularity' => 'daily',
                'categories' => ['cross-channel', 'cost', 'paid_media', 'budget'],
                'required_tags' => ['spendable'],
                'source_series' => [
                    ['key' => 'a', 'label' => 'Spend (Channel A)', 'channel' => '__SPENDABLE_CHANNEL_1__', 'metric' => 'spend', 'granularity' => 'daily'],
                    ['key' => 'b', 'label' => 'Spend (Channel B)', 'channel' => '__SPENDABLE_CHANNEL_2__', 'metric' => 'spend', 'granularity' => 'daily'],
                ],
                'ast' => [
                    'type' => 'operator',
                    'operator' => '/',
                    'left' => ['type' => 'metric', 'metric' => 'a'],
                    'right' => ['type' => 'metric', 'metric' => 'b'],
                ],
            ],

            // ================================================================
            // Cross-Channel: Paid + Organic / SEO
            // ================================================================

            'paid_organic_reach_ratio' => [
                'name' => 'Paid-to-Organic Reach Ratio',
                'description' => 'How many units of organic reach you earn per dollar of paid spend. Higher values mean your organic content is thriving alongside your paid campaigns — a healthy ecosystem signal.',
                'format' => 'decimal',
                'output_granularity' => 'daily',
                'categories' => ['cross-channel', 'paid_media', 'organic', 'social'],
                'required_tags' => ['spendable', 'organic_social', 'reach_driven'],
                'source_series' => [
                    ['key' => 'a', 'label' => 'Organic Reach', 'channel' => '__ORGANIC_SOCIAL_CHANNEL_1__', 'metric' => 'reach', 'granularity' => 'daily'],
                    ['key' => 'b', 'label' => 'Paid Spend', 'channel' => '__SPENDABLE_CHANNEL_1__', 'metric' => 'spend', 'granularity' => 'daily'],
                ],
                'ast' => [
                    'type' => 'operator',
                    'operator' => '/',
                    'left' => ['type' => 'metric', 'metric' => 'a'],
                    'right' => ['type' => 'metric', 'metric' => 'b'],
                ],
            ],

            'paid_organic_reach_abs_diff' => [
                'name' => 'Reach Gap (Paid vs Organic)',
                'description' => 'Absolute difference between paid reach and organic reach. A widening gap may indicate over-reliance on paid distribution or algorithmic organic reach suppression.',
                'format' => 'decimal',
                'output_granularity' => 'daily',
                'categories' => ['cross-channel', 'paid_media', 'organic', 'reach', 'social'],
                'required_tags' => ['spendable', 'organic_social', 'impressionable'],
                'source_series' => [
                    ['key' => 'a', 'label' => 'Paid Reach', 'channel' => '__SPENDABLE_CHANNEL_1__', 'metric' => 'reach', 'granularity' => 'daily'],
                    ['key' => 'b', 'label' => 'Organic Reach', 'channel' => '__ORGANIC_SOCIAL_CHANNEL_1__', 'metric' => 'reach', 'granularity' => 'daily'],
                ],
                'ast' => [
                    'type' => 'operator',
                    'operator' => 'abs_diff',
                    'left' => ['type' => 'metric', 'metric' => 'a'],
                    'right' => ['type' => 'metric', 'metric' => 'b'],
                ],
            ],

            'seo_paid_click_ratio' => [
                'name' => 'SEO-to-Paid Click Ratio',
                'description' => 'Number of organic search clicks earned for every paid click. Declining values may signal that paid campaigns are cannibalizing organic search traffic.',
                'format' => 'decimal',
                'output_granularity' => 'daily',
                'categories' => ['cross-channel', 'seo', 'paid_media', 'clicks', 'performance'],
                'required_tags' => ['seo', 'clickable', 'spendable'],
                'source_series' => [
                    ['key' => 'a', 'label' => 'SEO Clicks', 'channel' => '__SEO_CHANNEL_1__', 'metric' => 'clicks', 'granularity' => 'daily'],
                    ['key' => 'b', 'label' => 'Paid Clicks', 'channel' => '__CLICKABLE_CHANNEL_1__', 'metric' => 'clicks', 'granularity' => 'daily'],
                ],
                'ast' => [
                    'type' => 'operator',
                    'operator' => '/',
                    'left' => ['type' => 'metric', 'metric' => 'a'],
                    'right' => ['type' => 'metric', 'metric' => 'b'],
                ],
            ],

            'paid_organic_impression_ratio' => [
                'name' => 'Paid-to-Organic Impression Ratio',
                'description' => 'Ratio of organic impressions to paid impressions. Helps evaluate whether your organic presence is holding its own against paid competition for visibility.',
                'format' => 'decimal',
                'output_granularity' => 'daily',
                'categories' => ['cross-channel', 'paid_media', 'organic', 'impressions'],
                'required_tags' => ['impressionable', 'organic_social'],
                'source_series' => [
                    ['key' => 'a', 'label' => 'Organic Impressions', 'channel' => '__ORGANIC_SOCIAL_CHANNEL_1__', 'metric' => 'impressions', 'granularity' => 'daily'],
                    ['key' => 'b', 'label' => 'Paid Impressions', 'channel' => '__IMPRESSIONABLE_CHANNEL_1__', 'metric' => 'impressions', 'granularity' => 'daily'],
                ],
                'ast' => [
                    'type' => 'operator',
                    'operator' => '/',
                    'left' => ['type' => 'metric', 'metric' => 'a'],
                    'right' => ['type' => 'metric', 'metric' => 'b'],
                ],
            ],

            'organic_reach_vs_seo_ctr' => [
                'name' => 'Organic Reach vs SEO CTR',
                'description' => 'Ratio combining organic social reach and SEO click efficiency. A holistic content performance signal — rising values indicate your brand is gaining traction both socially and in search.',
                'format' => 'decimal',
                'output_granularity' => 'daily',
                'categories' => ['cross-channel', 'organic', 'seo', 'social', 'performance'],
                'required_tags' => ['organic_social', 'seo', 'clickable'],
                'source_series' => [
                    ['key' => 'a', 'label' => 'Organic Reach', 'channel' => '__ORGANIC_SOCIAL_CHANNEL_1__', 'metric' => 'reach', 'granularity' => 'daily'],
                    ['key' => 'b', 'label' => 'SEO Clicks', 'channel' => '__SEO_CHANNEL_1__', 'metric' => 'clicks', 'granularity' => 'daily'],
                ],
                'ast' => [
                    'type' => 'operator',
                    'operator' => '/',
                    'left' => ['type' => 'metric', 'metric' => 'a'],
                    'right' => ['type' => 'metric', 'metric' => 'b'],
                ],
            ],

            'revenue_per_click' => [
                'name' => 'Revenue per Click (Combined)',
                'description' => 'Average revenue generated per click across all channels (paid + organic). A top-level efficiency metric that cuts across channel silos.',
                'format' => 'currency',
                'output_granularity' => 'daily',
                'categories' => ['cross-channel', 'revenue', 'results', 'performance'],
                'required_tags' => ['revenue_tracked', 'clickable'],
                'source_series' => [
                    ['key' => 'a', 'label' => 'Revenue', 'channel' => '__REVENUE_TRACKED_CHANNEL_1__', 'metric' => 'revenue', 'granularity' => 'daily'],
                    ['key' => 'b', 'label' => 'Total Clicks', 'channel' => '__CLICKABLE_CHANNEL_1__', 'metric' => 'clicks', 'granularity' => 'daily'],
                ],
                'ast' => [
                    'type' => 'operator',
                    'operator' => '/',
                    'left' => ['type' => 'metric', 'metric' => 'a'],
                    'right' => ['type' => 'metric', 'metric' => 'b'],
                ],
            ],
        ];
    }

    /**
     * Get predefined derived metrics available for a set of active channels.
     * Filters by checking if the active channels collectively satisfy
     * all required_tags for each derived metric definition.
     *
     * @param array $activeChannels
     * @return array
     */
    public static function getAvailable(array $activeChannels): array
    {
        $availableTags = [];
        $registryTags = ChannelCapabilityRegistry::getTags();

        foreach ($activeChannels as $channel) {
            if (isset($registryTags[$channel])) {
                $availableTags = array_merge($availableTags, $registryTags[$channel]);
            }
        }
        $availableTags = array_unique($availableTags);

        return collect(self::getPredefined())->filter(function ($dm) use ($availableTags) {
            $requiredTags = $dm['required_tags'] ?? [];
            return count(array_intersect($requiredTags, $availableTags)) === count($requiredTags);
        })->toArray();
    }
}
