<?php

namespace App\Filament\App\Pages;

use App\Services\Analytics\PredefinedKpiRegistry;
use Filament\Pages\Page;

class KpiReference extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static string $view = 'filament.app.pages.kpi-reference';

    public static function getNavigationLabel(): string
    {
        return __('KPI Reference');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Knowledge Base');
    }

    public function getTitle(): string
    {
        return __('Predefined KPI Templates');
    }

    public static function canAccess(): bool
    {
        return true;
    }

    public function getKpis(): array
    {
        return PredefinedKpiRegistry::getPredefinedKpis();
    }

    public function getKpisWithGuidance(): array
    {
        $kpis = $this->getKpis();
        $result = [];
        foreach ($kpis as $key => $kpi) {
            $guidance = $this->getGuidance($key);
            $result[] = [
                'key' => $key,
                'name' => $kpi['name'],
                'description' => $kpi['description'],
                'type_label' => $guidance['type_label'],
                'explanation' => $guidance['explanation'],
                'use_case' => $guidance['use_case'],
                'interpretation' => $guidance['interpretation'],
                'categories' => $kpi['categories'] ?? [],
            ];
        }
        return $result;
    }

    public function getCategoryOptions(): array
    {
        return [
            'performance' => __('Performance'),
            'cost' => __('Cost'),
            'results' => __('Results'),
            'clicks' => __('Clicks'),
            'impressions' => __('Impressions'),
            'seasonality' => __('Seasonality'),
            'trends' => __('Trends'),
            'scalability' => __('Scalability'),
            'cross-channel' => __('Cross-Channel'),
            'alerts' => __('Alerts'),
            'seo' => __('SEO'),
            'organic' => __('Organic'),
        ];
    }

    public function getGuidance(string $key): array
    {
        $guidance = [
            'true_blended_marginal_cost' => [
                'type_label' => __('Cost Efficiency Analyzer'),
                'explanation' => __('Calculates the real average cost you pay per click when combining multiple paid channels. Instead of looking at each channel\'s cost in isolation, this KPI blends them together to show you the consolidated efficiency of your entire paid strategy.'),
                'use_case' => __('Imagine you\'re running Facebook Ads (costing $500 for 1,000 clicks) and Google Ads (costing $300 for 500 clicks). Instead of tracking each separately, this KPI tells you your true blended cost across both — helping you decide if your overall paid mix is getting more or less efficient over time.'),
                'interpretation' => __('A lower number means you\'re paying less per click overall — good. A higher number means your blended cost is rising, which could indicate one of your channels is becoming less efficient. Watch for sudden jumps: they often mean a channel\'s performance has dipped and needs attention.'),
            ],
            'spend_elasticity' => [
                'type_label' => __('Diminishing Returns Finder'),
                'explanation' => __('Measures how much extra output (like clicks or conversions) you actually get when you increase your spending. If you double your budget, do you really get double the results? This KPI answers that.'),
                'use_case' => __('You\'re considering increasing your monthly Facebook budget from $1,000 to $2,000. Will you actually get twice the clicks? This KPI tells you if your channel still has room to grow efficiently, or if you\'re just burning money on diminishing returns.'),
                'interpretation' => __('A result above 1 means the channel is scalable — increasing spend still brings proportional or better results. A result below 1 means you\'re hitting diminishing returns (you spent more, but got less per dollar). A result near 0 or negative is a red flag: more spending is barely moving the needle.'),
            ],
            'weekly_organic_seasonality' => [
                'type_label' => __('Organic Pattern Detector'),
                'explanation' => __('Checks whether your organic metrics (like reach or impressions on unpaid posts) follow a predictable weekly rhythm. Helps you understand if certain days of the week consistently outperform others.'),
                'use_case' => __('You notice your organic Facebook reach seems higher on Wednesdays, but you\'re not sure if it\'s a real pattern or just random. This KPI confirms whether a real weekly seasonality exists so you can schedule your most important organic posts on the strongest days.'),
                'interpretation' => __('A clear positive result confirms a weekly pattern exists — your content performs predictably better on certain days. A weak or negative result means there\'s no consistent weekly rhythm; your organic performance fluctuates randomly, and you should focus on content quality rather than day-of-week timing.'),
            ],
            'paid_to_organic_halo_effect' => [
                'type_label' => __('Cross-Channel Influence Detector'),
                'explanation' => __('Tests whether spending money on paid ads causes a delayed boost in your organic performance. In other words: does your paid activity "spill over" and increase your unpaid reach or engagement on later days?'),
                'use_case' => __('You run a Facebook ad campaign for a week and notice your organic page reach went up the following week. Was that a coincidence, or did the paid ads actually drive people to engage with your organic content? This KPI tells you whether your paid spend is creating a halo effect.'),
                'interpretation' => __('A positive result means your paid ads are helping your organic performance — the halo effect is real. You should factor this into your ROI calculations (the paid ads are worth more than just their direct clicks). A weak or negative result means there\'s no detectable spillover; your paid and organic channels are working independently.'),
            ],
            'cpc_momentum' => [
                'type_label' => __('Trend Direction Tracker'),
                'explanation' => __('Monitors whether your Cost Per Click (CPC) is getting cheaper or more expensive over a rolling window. Instead of looking at a single day\'s number, it detects the underlying direction of your costs.'),
                'use_case' => __('Your CPC was $0.50 last week, $0.55 this week, and $0.58 today. Are these random fluctuations or is there a real upward trend? This KPI cuts through the noise and tells you if your costs are genuinely trending in a direction that needs action.'),
                'interpretation' => __('An upward signal means your CPC is getting more expensive — your ad costs are creeping up. Consider refreshing creatives, adjusting targeting, or reviewing auction competitiveness. A downward signal means your CPC is improving — your campaigns are becoming more efficient. No clear signal means costs are stable; no immediate action needed.'),
            ],
            'impression_anomaly_alert' => [
                'type_label' => __('Outlier / Spike Detector'),
                'explanation' => __('Flags unusually large spikes or drops in your impressions that fall outside normal patterns. Designed to catch unexpected events before they become problems — or opportunities.'),
                'use_case' => __('Your impressions suddenly jump from 10,000 to 100,000 in one day. Did a post go viral? Or is there a reporting glitch? Or did a competitor stop bidding? This KPI alerts you when something out of the ordinary happens so you can investigate immediately.'),
                'interpretation' => __('When an anomaly is detected, check what happened on that date: Was there a campaign change? A viral post? A bot attack? A tracking error? Spikes aren\'t always good (bot traffic) and drops aren\'t always bad (better targeting). The KPI says "look here" — your judgement determines what it means.'),
            ],
            'seo_click_momentum' => [
                'type_label' => __('SEO Traffic Trend'),
                'explanation' => __('Monitors whether your organic search clicks are trending up or down over a rolling window. It cuts through daily noise to reveal the real direction of your SEO performance — are you gaining ground or losing it?'),
                'use_case' => __('You\'ve been publishing blog posts and optimizing your site for months, but daily traffic bounces up and down. Is all that effort actually paying off? This KPI tells you if the underlying trend is positive (your SEO is working) or negative (something needs attention).'),
                'interpretation' => __('An upward signal means your organic traffic is growing — keep doing what you\'re doing. A downward signal means you\'re losing traction: check for algorithm updates, new competitors, or technical SEO issues. No clear signal means traffic is stable.'),
            ],
            'reach_elasticity' => [
                'type_label' => __('Audience Saturation Finder'),
                'explanation' => __('Measures how much additional audience reach you actually get for every extra dollar spent on paid media. Unlike clicks (which can scale almost linearly), reach has a natural ceiling — you can only show ads to so many people before you start saturating.'),
                'use_case' => __('You\'ve been increasing your Facebook ad budget from $1,000 to $3,000, but your dashboard shows your frequency is going up (people seeing the same ad 5+ times). Are you actually reaching NEW people, or just annoying the same audience? This KPI tells you if your reach is still growing efficiently per dollar.'),
                'interpretation' => __('A value above 1 means you still have room to grow — your ads are reaching new audiences efficiently. A value below 1 means you\'re hitting saturation: each dollar buys less new reach. Time to refresh audiences, expand targeting, or rotate creatives to re-engage different segments.'),
            ],
            'content_half_life' => [
                'type_label' => __('Engagement Decay Meter'),
                'explanation' => __('Measures how quickly the engagement on your organic posts drops off after publishing. Some content stays relevant for days (long half-life), while other content peaks and dies within hours (short half-life). This KPI quantifies that decay rate.'),
                'use_case' => __('You post daily on Facebook — some posts get 80% of their reach in the first 3 hours, while others trickle in over 3 days. Which type of content has longer-lasting value? This KPI helps you identify what kind of posts keep delivering results long after publishing, so you can invest more effort there.'),
                'interpretation' => __('A longer half-life means your content has staying power — evergreen topics, tutorials, or discussions that keep generating engagement. A shorter half-life means time-sensitive content (news, promotions, trends) that burns bright and fast. Neither is bad, but knowing your mix helps you balance your content calendar.'),
            ],
            'paid_organic_cannibalization' => [
                'type_label' => __('Channel Conflict Detector'),
                'explanation' => __('Tests whether your paid campaigns are reducing your organic reach. This is the flip side of the Halo Effect: instead of paid helping organic, this detects if your own ads are competing with and diminishing your unpaid content visibility.'),
                'use_case' => __('You launch a big Facebook ad campaign and notice your organic page reach drops during the same period. Is this a coincidence or are your ads actually stealing reach that your organic posts would have gotten? This KPI tells you if your paid and organic channels are eating each other\'s lunch.'),
                'interpretation' => __('A positive result means cannibalization is happening — your ads are reducing your organic reach. Consider separating paid and organic audiences, or pausing paid campaigns during important organic pushes. A negative result means no conflict; your channels coexist independently.'),
            ],
            'ctr_efficiency' => [
                'type_label' => __('Search Presence Quality'),
                'explanation' => __('Analyzes how effectively your search impressions convert into clicks. As you rank for more keywords (especially lower-volume, long-tail ones), your average CTR naturally changes. This KPI helps you understand if your search snippets and rankings are becoming more or less compelling.'),
                'use_case' => __('Your Google Search Console shows you\'re getting more impressions than ever, but clicks aren\'t keeping pace. Are you ranking for irrelevant keywords? Did your snippet titles lose their appeal? This KPI isolates the click-through efficiency so you can diagnose if it\'s a ranking issue or a messaging issue.'),
                'interpretation' => __('A rising value means your search snippets are becoming more effective — better titles, descriptions, or rich results are convincing users to click. A falling value means something is off: maybe you\'re ranking for impressions without clicks (position too low), or your snippets have become less compelling. Investigate which queries are dragging CTR down.'),
            ],
            'revenue_elasticity' => [
                'type_label' => __('Revenue Growth Predictor'),
                'explanation' => __('Measures how your actual revenue responds to changes in ad spend. If you double your ad budget, does revenue double too — or do you hit a point where extra spend stops being profitable? This KPI quantifies the real efficiency of your ad-to-revenue pipeline.'),
                'use_case' => __('Your e-commerce store spends $10,000 on ads and generates $30,000 in revenue — a 3x ROAS. But when you increase to $15,000, revenue only goes to $35,000. ROAS dropped to 2.3x. Are you past the sweet spot? This KPI tells you exactly how scalable your revenue is relative to spend.'),
                'interpretation' => __('A result above 1 means your revenue scales well with spend — each extra dollar still brings strong returns. A result below 1 means you\'re past peak efficiency: you\'re spending more but getting proportionally less revenue. Time to optimize targeting, creative, or consider diminishing returns in your budget allocation.'),
            ],
            'cpa_trend' => [
                'type_label' => __('Acquisition Cost Monitor'),
                'explanation' => __('Tracks whether your cost per acquisition is getting cheaper or more expensive on a rolling basis. Instead of reacting to daily CPA fluctuations, this KPI detects the real underlying direction of your acquisition costs.'),
                'use_case' => __('Your Facebook ads are bringing in 50 conversions per week, but your CPA has gone from $12 to $14 to $16 over the last month. Is this just random weekly variation or a genuine upward trend? This KPI tells you if you need to take action before your margins get squeezed.'),
                'interpretation' => __('An upward signal means your CPA is rising — your acquisition costs are creeping up. Consider refreshing audiences, creatives, or optimizing your funnel. A downward signal means you\'re getting more efficient — your targeting or funnel improvements are working. No clear signal means CPA is stable.'),
            ],
            'seo_to_revenue_influence' => [
                'type_label' => __('Organic Revenue Detector'),
                'explanation' => __('Tests whether your organic search traffic has a measurable impact on future revenue. Moves beyond "traffic is up" to answer the real question: is SEO actually driving the bottom line?'),
                'use_case' => __('Your SEO team reports that organic sessions increased 30% this quarter, but revenue is flat. Is SEO just bringing in tire-kickers, or does it take time for organic visitors to convert? This KPI separates correlation from causation, telling you if organic search is truly a revenue driver.'),
                'interpretation' => __('A positive result means your SEO traffic is genuinely driving revenue — there\'s a predictive relationship. You should invest more in organic. A weak or negative result means organic visits aren\'t translating to revenue in a predictable way. Consider whether you\'re attracting the right kind of traffic (commercial intent vs. informational queries).'),
            ],
        ];

        return $guidance[$key] ?? [
            'type_label' => '',
            'explanation' => '',
            'use_case' => '',
            'interpretation' => '',
        ];
    }
}
