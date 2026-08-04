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
                'theory' => __('When Google gives you an impression spike, it takes the algorithm a few days to process the user-behavior signals from that traffic before adjusting your ranking.'),
                'expected' => __('Positive correlation with Lag +4. A spike in impressions 4 days ago often correlates with a temporary drop in ranking today.'),
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
                'theory' => __('More clicks should theoretically mean more conversions. If they don\'t, you might have a "clickbait" ad or a broken landing page.'),
                'expected' => __('Should be a strong positive correlation. If the correlation drops to zero or goes negative, your ads are driving low-intent traffic.'),
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
                'theory' => __('When market competition drives up the cost of impressions (CPM), does your return on ad spend (ROAS) immediately drop?'),
                'expected' => __('Typically a negative correlation. As CPMs rise, ROAS drops unless the more expensive audience converts at a higher rate.'),
                'requires' => ['facebook_marketing'],
                'config' => [
                    'curveA' => ['channel' => 'facebook_marketing', 'metric' => 'cpm', 'level' => 'zscore', 'lag' => '0'],
                    'curveB' => ['channel' => 'facebook_marketing', 'metric' => 'purchase_roas', 'level' => 'zscore', 'lag' => '0'],
                ],
            ],
            [
                'id' => 'paid_to_ga_conversions',
                'name' => __('Paid Spend to Web Conversions'),
                'short_desc' => __('FB Spend vs GA Conversions'),
                'theory' => __('Ad spend on paid social should drive conversion events tracked in web analytics within 24-48 hours.'),
                'expected' => __('Strong positive correlation with a 0 to 1 day lag.'),
                'requires' => ['facebook_marketing', 'google_analytics'],
                'config' => [
                    'curveA' => ['channel' => 'facebook_marketing', 'metric' => 'spend', 'level' => 'zscore', 'lag' => '0'],
                    'curveB' => ['channel' => 'google_analytics', 'metric' => 'conversions', 'level' => 'zscore', 'lag' => '1'],
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
