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
        ];

        return $guidance[$key] ?? [
            'type_label' => '',
            'explanation' => '',
            'use_case' => '',
            'interpretation' => '',
        ];
    }
}
