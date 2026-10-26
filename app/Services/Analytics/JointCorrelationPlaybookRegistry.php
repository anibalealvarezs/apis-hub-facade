<?php

namespace App\Services\Analytics;

class JointCorrelationPlaybookRegistry
{
    /**
     * Get all predefined joint correlation playbook scenarios.
     *
     * @return array
     */
    public static function getPlays(): array
    {
        return [
            [
                'id' => 'custom_analysis',
                'name' => __('Custom Analysis'),
                'short_desc' => __('Free Exploration'),
                'theory' => __('Start with a blank canvas to explore your own hypotheses across any channels and metrics.'),
                'expected' => __('No predefined expectations. Select your channels, assets, metrics, and lags manually to discover new correlations.'),
                'requires' => [],
                'config' => [
                    'curveA' => ['channel' => '', 'metric' => '', 'level' => 'zscore', 'lag' => '0'],
                    'curveB' => ['channel' => '', 'metric' => '', 'level' => 'zscore', 'lag' => '0'],
                ],
            ],
            [
                'id' => 'brand_search_synergy',
                'name' => __('Brand Search Synergy'),
                'short_desc' => __('FB Ads vs GSC'),
                'theory' => __("Paid social campaigns drive top-of-funnel awareness. People see an ad, don't click, but later search for the brand on Google."),
                'expected' => __('Positive correlation with a 2-4 day lag. If correlation is 0, your ads are not generating residual search intent.'),
                'requires' => ['facebook_marketing', 'google_search_console'],
                'config' => [
                    'curveA' => ['channel' => 'facebook_marketing', 'metric' => 'spend', 'level' => 'zscore', 'lag' => '0'],
                    'curveB' => ['channel' => 'google_search_console', 'metric' => 'clicks', 'level' => 'zscore', 'lag' => '3'],
                ],
            ],
            [
                'id' => 'organic_lift_paid',
                'name' => __('Organic Lift via Paid'),
                'short_desc' => __('FB Ads vs FB Organic'),
                'theory' => __('Aggressive paid spending can create a halo effect on your organic profile visits and reach.'),
                'expected' => __('Positive correlation. When spend spikes, organic reach should spike proportionally.'),
                'requires' => ['facebook_marketing', 'facebook_organic'],
                'config' => [
                    'curveA' => ['channel' => 'facebook_marketing', 'metric' => 'spend', 'level' => 'zscore', 'lag' => '0'],
                    'curveB' => ['channel' => 'facebook_organic', 'metric' => 'reach', 'level' => 'zscore', 'lag' => '0'],
                ],
            ],
            [
                'id' => 'ad_fatigue',
                'name' => __('Ad Fatigue & Efficiency'),
                'short_desc' => __('FB CTR vs FB Cost'),
                'theory' => __('As audience saturates, click-through rates drop while cost per acquisition (CPA) spikes.'),
                'expected' => __('Strong negative correlation. The Rolling Correlation chart is critical here to spot the exact day fatigue started.'),
                'requires' => ['facebook_marketing'],
                'config' => [
                    'curveA' => ['channel' => 'facebook_marketing', 'metric' => 'ctr', 'level' => 'zscore', 'lag' => '0'],
                    'curveB' => ['channel' => 'facebook_marketing', 'metric' => 'cost_per_result', 'level' => 'zscore', 'lag' => '0'],
                ],
            ],
            [
                'id' => 'google_evaluation_cycle',
                'name' => __('SEO Evaluation Cycle'),
                'short_desc' => __('GSC Impressions vs Position'),
                'theory' => __('When Google gives you an impression spike, it takes the algorithm a few days to process the user-behavior signals (CTR, Bounce Rate) from that traffic before adjusting your ranking.'),
                'expected' => __('Positive correlation with Lag +4. A spike in impressions 4 days ago often correlates with a temporary drop in ranking (higher position number) today as Google processes the broad, low-engagement traffic test.'),
                'requires' => ['google_search_console'],
                'config' => [
                    'curveA' => ['channel' => 'google_search_console', 'metric' => 'impressions', 'level' => 'zscore', 'lag' => '4'],
                    'curveB' => ['channel' => 'google_search_console', 'metric' => 'position', 'level' => 'zscore', 'lag' => '0'],
                ],
            ],
            [
                'id' => 'search_to_organic_lag',
                'name' => __('Search Demand to Organic Engagement'),
                'short_desc' => __('GSC Clicks vs FB Organic'),
                'theory' => __('Surges in search demand often precede user engagement on social channels as consumers research before interacting.'),
                'expected' => __('Positive correlation with 1-3 day lag.'),
                'requires' => ['google_search_console', 'facebook_organic'],
                'config' => [
                    'curveA' => ['channel' => 'google_search_console', 'metric' => 'clicks', 'level' => 'zscore', 'lag' => '0'],
                    'curveB' => ['channel' => 'facebook_organic', 'metric' => 'page_engaged_users', 'level' => 'zscore', 'lag' => '2'],
                ],
            ],
            [
                'id' => 'empty_traffic',
                'name' => __('Empty Traffic Check'),
                'short_desc' => __('FB Clicks vs Conversions'),
                'theory' => __("More clicks should theoretically mean more conversions. If they don't, you might have a \"clickbait\" ad or a broken landing page."),
                'expected' => __("Should be a strong positive correlation. If the correlation drops to zero or goes negative, your ads are driving low-intent traffic that doesn't convert."),
                'requires' => ['facebook_marketing'],
                'config' => [
                    'curveA' => ['channel' => 'facebook_marketing', 'metric' => 'clicks', 'level' => 'zscore', 'lag' => '0'],
                    'curveB' => ['channel' => 'facebook_marketing', 'metric' => 'results', 'level' => 'zscore', 'lag' => '0'],
                ],
            ],
            [
                'id' => 'cpm_vs_roas',
                'name' => __('Auction Competition vs ROAS'),
                'short_desc' => __('FB CPM vs ROAS'),
                'theory' => __('When market competition drives up the cost of impressions (CPM), does your return on ad spend (ROAS) immediately drop, or does the higher quality of expensive traffic maintain the ROAS?'),
                'expected' => __('Typically a negative correlation. As CPMs rise, ROAS drops unless the more expensive audience is converting at a proportionally higher rate.'),
                'requires' => ['facebook_marketing'],
                'config' => [
                    'curveA' => ['channel' => 'facebook_marketing', 'metric' => 'cpm', 'level' => 'zscore', 'lag' => '0'],
                    'curveB' => ['channel' => 'facebook_marketing', 'metric' => 'purchase_roas', 'level' => 'zscore', 'lag' => '0'],
                ],
            ],
            [
                'id' => 'organic_algorithm_reward',
                'name' => __('Algorithm Reward'),
                'short_desc' => __('FB Engagements vs Reach'),
                'theory' => __('Social algorithms reward high engagement (likes/comments) today with broader reach tomorrow.'),
                'expected' => __('Positive correlation with a 1 to 2 day lag. A spike in interactions today should cause a delayed spike in reach as the algorithm pushes the content.'),
                'requires' => ['facebook_organic'],
                'config' => [
                    'curveA' => ['channel' => 'facebook_organic', 'metric' => 'total_interactions', 'level' => 'zscore', 'lag' => '0'],
                    'curveB' => ['channel' => 'facebook_organic', 'metric' => 'reach', 'level' => 'zscore', 'lag' => '2'],
                ],
            ],
            [
                'id' => 'gsc_to_ga_sessions',
                'name' => __('SEO Clicks to GA Web Sessions'),
                'short_desc' => __('GSC Clicks vs GA Sessions'),
                'theory' => __('Organic search clicks reported by Search Console directly translate to real-time sessions in Google Analytics.'),
                'expected' => __('Very strong positive correlation with 0 lag. Discrepancies indicate tracking or redirect issues.'),
                'requires' => ['google_search_console', 'google_analytics'],
                'config' => [
                    'curveA' => ['channel' => 'google_search_console', 'metric' => 'clicks', 'level' => 'zscore', 'lag' => '0'],
                    'curveB' => ['channel' => 'google_analytics', 'metric' => 'sessions', 'level' => 'zscore', 'lag' => '0'],
                ],
            ],
            [
                'id' => 'paid_search_cannibalization',
                'name' => __('Paid Search vs Organic Cannibalization'),
                'short_desc' => __('GAds Spend vs GSC Clicks'),
                'theory' => __('Bidding heavily on brand search terms can cannibalize clicks that you would have captured organically for free, raising CAC without adding net-new traffic.'),
                'expected' => __('Negative correlation with 0-1 day lag signals cannibalization (paid spend goes up, organic clicks go down). Positive correlation signals true incrementality and category demand expansion.'),
                'requires' => ['google_ads', 'google_search_console'],
                'config' => [
                    'curveA' => ['channel' => 'google_ads', 'metric' => 'spend', 'level' => 'zscore', 'lag' => '0'],
                    'curveB' => ['channel' => 'google_search_console', 'metric' => 'clicks', 'level' => 'zscore', 'lag' => '0'],
                ],
            ],
            [
                'id' => 'paid_search_to_ga_sessions',
                'name' => __('Paid Search Click-to-Session Reality Check'),
                'short_desc' => __('GAds Clicks vs GA Sessions'),
                'theory' => __('Every billed Google Ads click should immediately yield a session in Google Analytics. Large divergence reveals bot traffic, slow landing page load, or tag misconfigurations.'),
                'expected' => __('Extremely high positive correlation (> 0.85) with 0 lag. A low correlation indicates severe tracking loss or invalid click drop-off before landing.'),
                'requires' => ['google_ads', 'google_analytics'],
                'config' => [
                    'curveA' => ['channel' => 'google_ads', 'metric' => 'clicks', 'level' => 'zscore', 'lag' => '0'],
                    'curveB' => ['channel' => 'google_analytics', 'metric' => 'sessions', 'level' => 'zscore', 'lag' => '0'],
                ],
            ],
            [
                'id' => 'cross_ppc_cpc_arbitrage',
                'name' => __('Cross-PPC Cost Arbitrage'),
                'short_desc' => __('Google CPC vs Meta CPC'),
                'theory' => __('Comparing cost-per-click fluctuations between Google Ads and Meta Ads allows dynamic budget shifting to the more cost-effective inventory.'),
                'expected' => __('Moderate correlation during holiday surges (both rise). Divergent trends indicate an opportunity to shift budget toward the cheaper channel.'),
                'requires' => ['google_ads', 'facebook_marketing'],
                'config' => [
                    'curveA' => ['channel' => 'google_ads', 'metric' => 'cpc', 'level' => 'zscore', 'lag' => '0'],
                    'curveB' => ['channel' => 'facebook_marketing', 'metric' => 'cpc', 'level' => 'zscore', 'lag' => '0'],
                ],
            ],
        ];
    }

    /**
     * Get a specific play by ID.
     *
     * @param string $id
     * @return array|null
     */
    public static function getPlay(string $id): ?array
    {
        foreach (static::getPlays() as $play) {
            if ($play['id'] === $id) {
                return $play;
            }
        }

        return null;
    }
}
