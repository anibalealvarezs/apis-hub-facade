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
        $channelTags = \App\Services\Analytics\ChannelCapabilityRegistry::getTags();
        $result = [];
        foreach ($kpis as $key => $kpi) {
            $guidance = self::getGuidance($key);
            $categories = $kpi['categories'] ?? [];
            
            // Dynamically inject specific channel categories based on required tags
            $requiredTags = $kpi['required_tags'] ?? [];
            foreach ($channelTags as $channel => $tags) {
                if (count(array_intersect($requiredTags, $tags)) > 0) {
                    $categories[] = 'ch_' . $channel;
                }
            }

            $result[] = [
                'key' => $key,
                'name' => $kpi['name'],
                'description' => $kpi['description'],
                'type_label' => $guidance['type_label'],
                'explanation' => $guidance['explanation'],
                'use_case' => $guidance['use_case'],
                'interpretation' => $guidance['interpretation'],
                'categories' => array_values(array_unique($categories)),
                'scope' => $kpi['scope'] ?? '',
                'status' => $kpi['status'] ?? 'active',
            ];
        }
        return $result;
    }

    public function getCategoryOptions(): array
    {
        return array_merge([
            'agency' => __('Agency Performance'),
            'alerts' => __('Alerts'),
            'clicks' => __('Clicks'),
            'cost' => __('Cost'),
            'cross-channel' => __('Cross-Channel'),
            'impressions' => __('Impressions'),
            'organic' => __('Organic'),
            'org_mkt_marketing' => __('Marketing Focus'),
            'org_mkt_organic' => __('Organic Focus'),
            'performance' => __('Performance'),
            'results' => __('Results'),
            'scalability' => __('Scalability'),
            'scope_asset' => __('Asset'),
            'scope_channel' => __('Channel'),
            'scope_global' => __('Global'),
            'seasonality' => __('Seasonality'),
            'seo' => __('SEO'),
            'source_src' => __('Source Data'),
            'source_tracking' => __('Tracking Data'),
            'trends' => __('Trends'),
        ], $this->getChannelCategories());
    }

    private function getChannelCategories(): array
    {
        $channels = \App\Services\Analytics\ChannelCapabilityRegistry::getTags();
        $cats = [];
        foreach (array_keys($channels) as $channel) {
            $name = ucwords(str_replace('_', ' ', $channel));
            if ($channel === 'facebook_marketing') $name = 'FB Marketing';
            if ($channel === 'facebook_organic') $name = 'FB Organic';
            if ($channel === 'google_search_console') $name = 'Google Search Console';
            if ($channel === 'google_analytics') $name = 'Google Analytics';
            $cats['ch_' . $channel] = $name;
        }
        return $cats;
    }

    public function getCategoryGroups(): array
    {
        return [
            __('Specific Channels') => $this->getChannelCategories(),
            __('Channel') => [
                'cross-channel' => __('Cross-Channel'),
                'organic' => __('Organic'),
                'seo' => __('SEO'),
            ],
            __('Data Origin') => [
                'source_src' => __('Source Data'),
                'source_tracking' => __('Tracking Data'),
            ],
            __('Data Perspective') => [
                'org_mkt_marketing' => __('Marketing Focus'),
                'org_mkt_organic' => __('Organic Focus'),
            ],
            __('Focus') => [
                'agency' => __('Agency Performance'),
                'alerts' => __('Alerts'),
                'cost' => __('Cost'),
                'performance' => __('Performance'),
                'scalability' => __('Scalability'),
                'seasonality' => __('Seasonality'),
                'trends' => __('Trends'),
            ],
            __('Metric') => [
                'clicks' => __('Clicks'),
                'impressions' => __('Impressions'),
                'results' => __('Results'),
            ],
            __('Scope') => [
                'scope_asset' => __('Asset'),
                'scope_channel' => __('Channel'),
                'scope_global' => __('Global'),
            ],
        ];
    }

    public static function getGuidance(string $key): array
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
            'anomaly_alert' => [
                'type_label' => __('Outlier / Spike Detector'),
                'explanation' => __('Flags unusually large spikes or drops in any time-series metric that fall outside normal statistical patterns. Unlike fixed thresholds, this KPI adapts to your normal range and only alerts when something truly unusual happens — whether it\'s impressions, clicks, spend, conversions, CPC, or any metric you care about.'),
                'use_case' => __('Your impressions normally fluctuate between 8,000 and 12,000 per day, but suddenly jump to 50,000. Or your CPC has been steady at $0.55 and spikes to $1.20. Or conversions drop from 30/day to 5/day overnight. Is this a real anomaly worth investigating or just normal variance? This KPI cuts through the noise and flags events that genuinely need your attention — on whatever metric matters most to you.'),
                'interpretation' => __('When an anomaly is detected, investigate immediately: Did a campaign change trigger this? Is there a tracking error? A viral post? Bot traffic? A competitor shift? Spikes aren\'t always good (bot attacks inflate impressions) and drops aren\'t always bad (better targeting reduces waste). The KPI says "look here" — your judgement determines what it means.'),
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
            'ctr_efficiency_page' => [
                'type_label' => __('Search Presence Quality (Page)'),
                'explanation' => __('Analyzes how effectively your search impressions convert into clicks by landing page. As you rank for more pages, your average CTR naturally changes. This KPI helps you understand if your search snippets and rankings are becoming more or less compelling per page.'),
                'use_case' => __('Your Google Search Console shows you\'re getting more impressions than ever, but clicks aren\'t keeping pace. Which landing pages get the most clicks for their impressions? This KPI isolates the click-through efficiency by page so you can diagnose if it\'s a ranking issue or a messaging issue on specific URLs.'),
                'interpretation' => __('A rising value means your search snippets are becoming more effective for that page — better titles, descriptions, or rich results are convincing users to click. A falling value means something is off: maybe you\'re ranking for impressions without clicks, or your snippets have become less compelling. Investigate which pages are dragging CTR down.'),
            ],
            'ctr_efficiency_query' => [
                'type_label' => __('Search Presence Quality (Keyword)'),
                'explanation' => __('Analyzes how effectively your search impressions convert into clicks by search term. As you rank for more keywords (especially lower-volume, long-tail ones), your average CTR naturally changes. This KPI helps you understand which specific keywords are driving clicks vs empty impressions.'),
                'use_case' => __('Your Google Search Console shows you\'re getting more impressions than ever, but clicks aren\'t keeping pace. Are you ranking for irrelevant keywords? This KPI isolates the click-through efficiency by keyword so you can double down on high-intent terms or ignore irrelevant ones.'),
                'interpretation' => __('A rising value means a specific keyword snippet is highly effective. A falling value means you rank for the keyword but no one clicks it (mismatched intent, or low position). Investigate which queries have high impressions but terrible click-through rates.'),
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
            'result_efficiency' => [
                'type_label' => __('Campaign Efficiency Analyzer'),
                'explanation' => __('Measures how efficiently your ad spend converts into results. Instead of looking at total results, this KPI regresses results against spend to reveal the marginal efficiency — are you getting more or less output per dollar over time?'),
                'use_case' => __('Your Facebook campaigns generated 500 results last month on $2,000 spend and 550 results this month on $2,500 spend. Raw numbers look like improvement, but this KPI tells you your marginal efficiency actually dropped — each dollar is buying fewer results now.'),
                'interpretation' => __('An upward-sloping regression line means your campaigns are becoming more efficient — each dollar buys more results than before. A downward slope means you\'re losing efficiency: either audience fatigue, creative burnout, or increased competition. Watch the R² value — the higher it is, the more predictable your efficiency trend.'),
            ],
            'result_rate_momentum' => [
                'type_label' => __('Conversion Momentum Tracker'),
                'explanation' => __('Tracks whether your conversion rate is gaining or losing momentum using MACD analysis. Unlike a simple up/down comparison, this KPI detects the underlying directional force in your result rate trend, filtering out day-to-day noise.'),
                'use_case' => __('Your result rate fluctuates between 2% and 4% on any given day — is that normal volatility or the start of a real decline? This KPI tells you when the momentum shifts before the aggregate numbers make it obvious.'),
                'interpretation' => __('When the MACD line crosses above the signal line, your conversion momentum is accelerating — your targeting or creative improvements are working. A cross below means momentum is stalling. Sustained divergence in either direction is a strong signal to investigate what changed in your funnel.'),
            ],
            'organic_engagement_efficiency' => [
                'type_label' => __('Content Quality Gauge'),
                'explanation' => __('Measures how engaging your organic content is relative to the number of people reached. This isolates content quality from content reach — a post that reaches 100,000 people but only engages 100 is a reach vs. quality mismatch.'),
                'use_case' => __('Your organic reach has been growing steadily, but likes and comments aren\'t keeping pace. Are you reaching the wrong people, or is your content becoming less compelling? This KPI separates the two so you know which problem to fix.'),
                'interpretation' => __('A rising trend means your content quality is improving — each person reached is more likely to engage. A declining trend is a red flag: your reach is growing faster than engagement, which often means you\'re hitting the wrong audience or your content is losing relevance. Investigate audience targeting and content strategy when this declines.'),
            ],
            'roas_momentum' => [
                'type_label' => __('Profitability Early Warning'),
                'explanation' => __('Detects shifts in your Return on Ad Spend momentum before they become obvious in aggregate numbers. Uses MACD analysis on the purchase_roas metric to give you early warning of profitability changes.'),
                'use_case' => __('Your monthly ROAS reports show a healthy 3.5x, but you feel like something has changed in the last week. Instead of waiting for the month-end report, this KPI tells you right now if your ROAS momentum is shifting — giving you days or weeks of extra reaction time.'),
                'interpretation' => __('A bullish crossover (MACD line crossing above signal) means your ROAS momentum is building — recent campaigns are becoming more profitable. A bearish crossover (crossing below) means profitability is eroding. Check for creative fatigue, audience saturation, or increased auction costs when you see a bearish signal.'),
            ],
            'search_position_efficiency_page' => [
                'type_label' => __('Snippet Appeal Score (Page)'),
                'explanation' => __('Measures how many clicks you generate per unit of search position by landing page. A page ranking #5 that gets as many clicks as a page ranking #2 has higher "snippet appeal" — its title, description, and rich results are more compelling.'),
                'use_case' => __('You\'ve been optimizing your meta titles and descriptions across various blog posts, but rankings haven\'t changed much. Are the changes working? This KPI tells you if a specific page\'s click-through efficiency is improving even when positions stay the same.'),
                'interpretation' => __('A rising trend means your page\'s search snippet is becoming more compelling. A falling trend means the page might be losing relevance. Compare this with CTR Efficiency to distinguish between ranking issues and snippet quality issues.'),
            ],
            'search_position_efficiency_query' => [
                'type_label' => __('Snippet Appeal Score (Keyword)'),
                'explanation' => __('Measures how many clicks you generate per unit of search position by keyword. A keyword ranking #5 that gets as many clicks as a keyword ranking #2 indicates much higher intent for that specific query.'),
                'use_case' => __('A single page ranks for 50 different keywords. Which of those keywords actually pull their weight? This KPI reveals that you might be getting a 15% CTR on a highly relevant term and 0.5% on an informational term, even if they have similar rankings.'),
                'interpretation' => __('High values mean a keyword overperforms its rank (focus on pushing it to #1). Low values mean the keyword underperforms its rank (potentially the wrong intent).'),
            ],
            'seo_structural_inertia' => [
                'type_label' => __('Growth Trend Baseline'),
                'explanation' => __('Calculates the underlying growth trend of your organic search presence by combining Linear Regression and a 28-day Simple Moving Average, filtering out minor algorithmic updates.'),
                'use_case' => __('Your daily search impressions bounce up and down wildly. You need to know if the overall trajectory is positive, ignoring weekend dips or minor Google updates.'),
                'interpretation' => __('A positive slope (m) confirms accelerating SEO inertia. If the trend is positive despite daily volatility, your strategy is working.'),
            ],
            'fb_algorithmic_inertia' => [
                'type_label' => __('True Reach Floor'),
                'explanation' => __('Isolates the true algorithmic distribution floor of your Facebook Page by mathematically removing weekly seasonality using Triple Exponential Smoothing (Holt-Winters).'),
                'use_case' => __('Facebook reach is always lower on weekends. This metric strips out the "weekend effect" to show the true health and distribution momentum of your page.'),
                'interpretation' => __('A stable or rising trend indicates healthy algorithmic baseline distribution. A falling trend means the platform is naturally choking your baseline reach regardless of the day of the week.'),
            ],
            'ig_viral_momentum' => [
                'type_label' => __('Decay Escape Velocity'),
                'explanation' => __('Determines if an Instagram post has broken the standard temporal decay curve by applying a Logarithmic Regression against interaction velocity.'),
                'use_case' => __('You want to know if a recent post is going viral or just experiencing the standard 48-hour spike and fade.'),
                'interpretation' => __('If the real interaction curve stays above the theoretical logarithmic decay line after 48 hours, the post has broken the decay cycle and retains viral inertia.'),
            ],
            'paid_learning_inertia' => [
                'type_label' => __('Optimization Confirmation'),
                'explanation' => __('Uses Exponential Moving Average crossovers (EMA 7 vs EMA 14) to mathematically confirm when an ad campaign\'s cost efficiency trend has genuinely shifted due to optimization.'),
                'use_case' => __('You made a change to an ad campaign and want to know if it actually improved the cost per acquisition, or if it\'s just daily noise.'),
                'interpretation' => __('When the fast EMA (7-day) crosses below the slow EMA (14-day) for CPA, it mathematically confirms a successful optimization phase. A cross above indicates eroding efficiency.'),
            ],
            'seo_intent_match' => [
                'type_label' => __('Content Relevance Checker'),
                'explanation' => __('Compares organic search clicks with bounce rate to identify if the content matches search intent.'),
                'use_case' => __('You achieved a top 3 ranking and clicks increased, but is it quality traffic? If the bounce rate for that segment rises proportionally, your content isn\'t answering the user\'s real search intent.'),
                'interpretation' => __('A rising bounce rate alongside rising clicks means low relevance (toxic keyword/intent mismatch). A stable or dropping bounce rate means you are successfully capturing high-intent traffic.'),
            ],
            'organic_conversion_elasticity' => [
                'type_label' => __('SEO ROI Scalability'),
                'explanation' => __('Measures how much real site conversions scale for every point improved in organic search position or clicks.'),
                'use_case' => __('Helps identify which content clusters are truly profitable for SEO. It answers: "Reaching position 1 doubles traffic, but does it double sales?"'),
                'interpretation' => __('A value above 1 means your SEO gains scale profitably into conversions. A value near 0 means you are ranking for keywords that drive traffic but no business value.'),
            ],
            'seo_engagement_quality' => [
                'type_label' => __('SEO Traffic Quality Indicator'),
                'explanation' => __('A quality indicator measuring average session duration driven by organic keywords relative to clicks.'),
                'use_case' => __('Are users actually reading your 2,000-word blog post? If clicks go up but session duration plummets, users are abandoning the page quickly.'),
                'interpretation' => __('A rising trend means your content is highly engaging and retains the user\'s attention. A falling trend means you attract clicks but fail to retain them.'),
            ],
            'toxic_keyword_detector' => [
                'type_label' => __('Toxic Keyword Identifier'),
                'explanation' => __('Identifies specific search terms with a high propensity for bouncing.'),
                'use_case' => __('Locates "low quality" search terms that generate empty traffic, negatively affecting global site metrics and wasting retention efforts.'),
                'interpretation' => __('High values flag toxic keywords. These should either be de-optimized, or the landing page content must be drastically changed to match the actual user expectation.'),
            ],
            'toxic_page_detector' => [
                'type_label' => __('Toxic Page Identifier'),
                'explanation' => __('Identifies pages with high bounce rate despite high search visibility (impressions). These "toxic pages" attract search visits but fail to engage users, signaling a mismatch between search intent and page content.'),
                'use_case' => __('Pages that rank well but drive high bounce rates waste your search potential. This KPI flags those specific pages so you can improve the content to match user intent or de-optimize them to preserve your site\'s overall engagement metrics.'),
                'interpretation' => __('Higher regression values indicate pages where the disconnect between visibility and engagement is strongest — these are your priority pages to fix. A rising trend means the toxicity is spreading across more pages. Investigate whether your content answers the queries driving those impressions.'),
            ],
            'paid_acquisition_saturation' => [
                'type_label' => __('Audience Saturation Tracker'),
                'explanation' => __('Crosses ad spend against New Users acquired to reveal if you are still reaching fresh audiences.'),
                'use_case' => __('You increased your Meta Ads budget by 50%. The ad platform says everything is fine, but this KPI reveals if you are actually bringing "new blood" to the site or just re-targeting the same audience.'),
                'interpretation' => __('A declining elasticity means saturation: you are spending more but acquiring fewer new users per dollar. Time to refresh creatives or expand targeting.'),
            ],
            'click_to_session_drop_off' => [
                'type_label' => __('Friction & Bot Detector'),
                'explanation' => __('Detects percentage loss between ad clicks charged by the platform and actual web sessions recorded by analytics.'),
                'use_case' => __('Meta charges you for 10,000 clicks, but analytics only registers 6,000 sessions. This KPI tracks that discrepancy.'),
                'interpretation' => __('A rising drop-off rate indicates technical friction (slow loading times), accidental mobile clicks, or low-quality bot traffic from ad networks.'),
            ],
            'social_viral_to_revenue_pipeline' => [
                'type_label' => __('Viral ROI Measurer'),
                'explanation' => __('Evaluates if organic social virality statistically translates into website revenue in subsequent days.'),
                'use_case' => __('You had a viral post reaching 100k people. This KPI cuts through the noise to tell you if those "likes" actually moved the financial needle days later.'),
                'interpretation' => __('A positive granger causality means your viral content drives actual delayed revenue. A negative result means your viral content is just vanity metrics with no commercial value.'),
            ],
            'social_traffic_stickiness' => [
                'type_label' => __('Social Audience Retention'),
                'explanation' => __('Measures how engaged the audience from social media remains after landing on the website.'),
                'use_case' => __('Helps identify what types of organic posts (e.g., a blog article vs a short video) attract users willing to consume deep content on your site.'),
                'interpretation' => __('A higher stickiness means you are driving highly qualified social traffic. A lower stickiness means users click out of curiosity but leave immediately.'),
            ],
            'brand_search_halo_effect' => [
                'type_label' => __('Brand Awareness Spillover'),
                'explanation' => __('Analyzes if social media efforts (paid and organic) generate a delayed increase in brand searches on Google.'),
                'use_case' => __('You launch a strong brand awareness campaign on Facebook. This KPI detects if people, instead of clicking the ad, go to Google days later to search for your company.'),
                'interpretation' => __('A positive regression coefficient confirms that your social efforts are successfully building long-term brand recall and driving indirect search traffic.'),
            ],
            'omnichannel_revenue_attribution' => [
                'type_label' => __('Global Revenue Driver'),
                'explanation' => __('A multiple regression determining which marketing effort (SEO, Paid, Organic) statistically pushes more global revenue.'),
                'use_case' => __('Answers the million-dollar question: "Of all my simultaneous marketing efforts, which one is statistically driving the most sales this month?"'),
                'interpretation' => __('Compares the coefficients of each channel to reveal the true heavyweight champion of your marketing mix, beyond basic last-click attribution.'),
            ],
            'traffic_to_conversion_inertia' => [
                'type_label' => __('Core Site Conversion Health'),
                'explanation' => __('Determines the natural conversion rhythm of the isolated site: if general traffic grows 20%, do conversions grow 20%?'),
                'use_case' => __('Ideal for measuring if UX/UI changes on the website are improving the retention and conversion rate, independent of the traffic source quality.'),
                'interpretation' => __('A value above 1 means your site converts traffic highly efficiently (compounding returns). A value below 1 means your site struggles to convert increased traffic volumes.'),
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
