<?php

namespace App\Services\Analytics;

/** Audited cache contracts. Metadata only: this does not enable new ingestion or UI choices. */
class ChannelAnalyticsCatalog
{
    public static function tree(string $channel): ?array
    {
        return match ($channel) {
            'facebook_organic', 'meta_organic' => self::organic(),
            'google_ads' => self::ads(),
            'google_analytics', 'google_analytics_4' => self::analytics(),
            'mailchimp' => self::mailchimp(),
            default => null,
        };
    }

    private static function metric(string $label, array $sources, string $unit, string $reducer, array $scopes, string $status = 'cached', array $caveats = []): array
    {
        return compact('label', 'sources', 'unit', 'reducer', 'scopes', 'status', 'caveats');
    }

    private static function scope(string $label, array $metrics, array $grain, array $filter, string $period = 'daily'): array
    {
        return compact('label', 'metrics', 'grain', 'filter', 'period') + ['merge_with_other_scopes' => false];
    }

    private static function base(string $channel, array $metrics, array $scopes, array $entities, array $dimensions, array $ingestion, array $caveats): array
    {
        return [
            'channel' => $channel, 'audited_at' => '2026-10-30', 'contract_version' => 1,
            'verification' => 'source-code audit; no authenticated provider coverage test',
            'metrics' => $metrics, 'scopes' => $scopes, 'entities' => $entities,
            'dimensions' => $dimensions, 'breakdowns' => array_keys($dimensions),
            'ui_dependencies' => ChannelGranularityRegistry::getDependenciesForChannel($channel),
            'granularities' => array_intersect_key(ChannelGranularityRegistry::getGranularitiesForChannel($channel), array_flip(['daily', 'weekly', 'monthly', 'quarterly', 'semiannual', 'annually', 'trailing_weekly', 'trailing_monthly', 'trailing_quarterly', 'trailing_semiannual', 'trailing_annually', 'lifetime'])),
            'filters' => ['account' => 'channeledAccount', 'scope' => 'dimensions.scope',
                'entity_attributes' => 'attributes.<entity>.<field>',
                'entity_attributes_note' => 'Stored entity attributes only; availability comes from tenant attribute metadata.'],
            'ingestion' => $ingestion, 'caveats' => $caveats,
            'aggregation_policy' => 'Choose one scope and one compatible grain. Reducers describe required semantics, not a guarantee of current engine behavior.',
        ];
    }

    private static function ads(): array
    {
        $levels = ['account', 'campaign', 'ad_group', 'ad', 'query'];
        $metrics = [];
        foreach (['spend' => ['Spend', 'metrics.cost_micros', 'account_currency'], 'impressions' => ['Impressions', 'metrics.impressions', 'count'], 'clicks' => ['Clicks', 'metrics.clicks', 'count'], 'conversions' => ['Conversions', 'metrics.conversions', 'count']] as $key => [$label, $source, $unit]) {
            $metrics[$key] = self::metric($label, [$source], $unit, 'sum', $levels);
        }
        $metrics['conversion_value'] = self::metric('Conversion value', ['metrics.conversions_value'], 'account_currency', 'sum', $levels, 'known_issue', ['ADS-01']);
        $metrics['roas_purchase'] = self::metric('Return on ad spend', ['metrics.conversions_value', 'metrics.cost_micros'], 'ratio', 'sum(conversion_value) / sum(spend)', $levels, 'known_issue', ['ADS-01']);
        foreach (['ctr' => ['CTR', 'clicks / impressions', 'ratio'], 'cpc' => ['Cost per click', 'spend / clicks', 'account_currency'], 'cpm' => ['Cost per thousand impressions', '1000 * spend / impressions', 'account_currency'], 'cost_per_conversion' => ['Cost per conversion', 'spend / conversions', 'account_currency'], 'conversion_rate' => ['Conversion rate (click based)', 'conversions / clicks', 'ratio']] as $key => [$label, $formula, $unit]) {
            $metrics[$key] = self::metric($label, [], $unit, $formula, $levels, 'derived', ['ADS-02']);
        }
        $metrics['video_views'] = self::metric('Video views', ['metrics.video_views'], 'count', 'sum', [], 'source_only', ['ADS-03']);
        $scopes = [];
        foreach ($levels as $level) {
            $scopes[$level] = self::scope(ucwords(str_replace('_', ' ', $level)), array_keys(array_filter($metrics, fn ($metric) => in_array($level, $metric['scopes'], true))), ['daily', $level, 'device'], []);
            $scopes[$level]['selection'] = 'Hierarchy aggregation profile and corresponding entity dependency; level is converter context, not a declared dimension filter.';
        }
        $tree = self::base('google_ads', $metrics, $scopes,
            ['channeledAccount' => 'customer', 'channeledCampaign' => 'campaign', 'adGroup' => 'ad group', 'ad' => 'ad', 'query' => 'search term'],
            ['device' => ['source' => 'segments.device', 'scopes' => $levels], 'campaignType' => ['source' => 'campaign.advertising_channel_type', 'scopes' => ['campaign', 'ad']], 'query' => ['source' => 'search_term_view.search_term', 'scopes' => ['query']]],
            ['defaults' => ['metrics.cost_micros', 'metrics.impressions', 'metrics.clicks', 'metrics.conversions', 'metrics.conversions_value', 'metrics.cost_per_conversion', 'metrics.average_cpc'], 'search_terms' => 'Opt-in; privacy-limited coverage, not an account-total substitute.', 'currency' => 'Micros normalized to account currency; no cross-account FX conversion.'],
            ['ADS-01' => 'Conversion value is assigned to ROAS; the default conversion_value key does not match the normalized conversions_value key.', 'ADS-02' => 'Ratios must be recomputed from totals. Click-based conversion rate is not necessarily Google interaction-based conversion rate.', 'ADS-03' => 'Requested metrics are not forwarded to the converter whitelist. Dictionary membership does not prove ingestion.']);
        unset($tree['filters']['scope']);
        $tree['filters']['semantic'] = ['dimensions.intent', 'dimensions.brand_relation', 'dimensions.business_relevance'];
        $tree['canonical_aliases'] = ['revenue' => 'conversion_value', 'total_revenue' => 'conversion_value', 'search_term' => 'query (dimension, not a numeric metric)'];
        return $tree;
    }

    private static function analytics(): array
    {
        $scopeMetrics = [
            'account' => ['screenPageViews', 'sessions', 'bounceRate', 'totalRevenue'],
            'traffic_matrix' => ['screenPageViews', 'sessions', 'bounceRate', 'averageSessionDuration', 'totalRevenue', 'conversions'],
            'event_matrix' => ['eventCount', 'conversions'],
            'acquisition_matrix' => ['newUsers', 'activeUsers', 'totalUsers'],
            'ad_touchpoint_matrix' => ['sessions', 'conversions', 'totalRevenue'],
        ];
        $definitions = [
            'screenPageViews' => ['Page and screen views', 'count', 'sum'], 'sessions' => ['Sessions', 'count', 'sum_within_one_scope'],
            'bounceRate' => ['Bounce rate', 'ratio', 'session_weighted_average'], 'totalRevenue' => ['Total revenue', 'property_currency', 'sum'],
            'averageSessionDuration' => ['Average session duration', 'seconds', 'session_weighted_average'],
            'conversions' => ['Conversions (legacy source name)', 'count', 'sum'], 'eventCount' => ['Event count', 'count', 'sum'],
            'newUsers' => ['New users', 'users', 'source_distinct_for_requested_window'],
            'activeUsers' => ['Active users', 'users', 'source_distinct_for_requested_window'], 'totalUsers' => ['Total users', 'users', 'source_distinct_for_requested_window'],
        ];
        $metrics = [];
        foreach ($definitions as $key => [$label, $unit, $reducer]) {
            $scopes = array_keys(array_filter($scopeMetrics, fn ($list) => in_array($key, $list, true)));
            $issues = in_array($key, ['newUsers', 'activeUsers', 'totalUsers', 'bounceRate', 'averageSessionDuration'], true) ? ['GA-02'] : [];
            if ($key === 'conversions') $issues[] = 'GA-04';
            $metrics[$key] = self::metric($label, [$key], $unit, $reducer, $scopes, 'cached', $issues);
        }
        $metrics['spend'] = self::metric('Advertising spend', [], 'property_currency', 'sum', [], 'known_issue', ['GA-01']);
        $grains = [
            'account' => ['daily'],
            'traffic_matrix' => ['daily', 'dimensions.sessionDefaultChannelGroup', 'dimensions.source', 'dimensions.medium', 'dimensions.landing_page', 'channeledCampaign', 'adGroup', 'country', 'device'],
            'event_matrix' => ['daily', 'event', 'dimensions.page', 'dimensions.sessionDefaultChannelGroup', 'dimensions.source', 'dimensions.medium', 'channeledCampaign', 'adGroup', 'ad'],
            'acquisition_matrix' => ['daily', 'dimensions.firstUserDefaultChannelGroup', 'dimensions.firstUserSourceMedium', 'channeledCampaign', 'adGroup', 'ad'],
            'ad_touchpoint_matrix' => ['daily', 'channeledCampaign', 'adGroup', 'ad'],
        ];
        $scopes = []; $dimensions = [];
        foreach ($scopeMetrics as $key => $list) {
            $scopes[$key] = self::scope(ucwords(str_replace('_', ' ', $key)), $list, $grains[$key], $key === 'account' ? ['dimensions.scope' => ['operator' => 'is_null']] : ['dimensions.scope' => $key]);
            foreach (array_slice($grains[$key], 1) as $dimension) $dimensions[$dimension]['scopes'][] = $key;
        }
        $tree = self::base('google_analytics', $metrics, $scopes,
            ['channeledAccount' => 'property', 'channeledCampaign' => 'campaign name', 'adGroup' => 'ad group / manual term mapping', 'ad' => 'manual ad content', 'event' => 'event name', 'country' => 'country', 'device' => 'device category'], $dimensions,
            ['defaults_by_scope' => $scopeMetrics, 'all' => 'Runs account plus four overlapping matrices; never add their totals.', 'currency' => 'Property currency; do not combine currencies without conversion.', 'compatibility' => 'Validate property metadata and metric/dimension compatibility before expanding requests.'],
            ['GA-01' => 'Canonical spend currently aliases totalRevenue. Revenue is not advertising cost.', 'GA-02' => 'Daily distinct user counts cannot reconstruct window-distinct users; generic SUM/AVG is unsafe for users/rates.', 'GA-03' => 'Traffic/event cache source and medium separately; the sessionSourceMedium UI path does not match those stored dimensions.', 'GA-04' => 'Legacy conversions requests need property metadata/compatibility validation against current keyEvents terminology.', 'GA-05' => 'Response thresholding, sampling and currency/timezone metadata are not represented in the metric contract.']);
        $tree['canonical_aliases'] = ['sessions' => 'sessions', 'conversions' => 'conversions', 'reach' => 'activeUsers', 'impressions' => 'screenPageViews', 'new_users' => 'newUsers', 'revenue' => 'totalRevenue', 'bounce_rate' => 'bounceRate', 'average_session_duration' => 'averageSessionDuration', 'total_users' => 'totalUsers', 'event_count' => 'eventCount'];
        $tree['source_dimensions_by_scope'] = [
            'account' => ['date'],
            'traffic_matrix' => ['date', 'sessionDefaultChannelGroup', 'sessionSourceMedium', 'sessionCampaignName', 'sessionGoogleAdsAdGroupName', 'deviceCategory', 'countryId', 'landingPagePlusQueryString'],
            'event_matrix' => ['date', 'eventName', 'pagePath', 'sessionDefaultChannelGroup', 'sessionSourceMedium', 'sessionCampaignName', 'sessionGoogleAdsAdGroupName', 'sessionManualTerm', 'sessionManualAdContent'],
            'acquisition_matrix' => ['date', 'firstUserDefaultChannelGroup', 'firstUserSourceMedium', 'firstUserCampaignName', 'firstUserGoogleAdsAdGroupName', 'firstUserManualTerm', 'firstUserManualAdContent'],
            'ad_touchpoint_matrix' => ['date', 'sessionCampaignName', 'sessionGoogleAdsAdGroupName', 'sessionManualTerm', 'sessionManualAdContent'],
        ];
        $tree['alias_warning'] = 'Reach means active users and impressions means page/screen views here; they are not advertising measurements. spend is deliberately excluded from valid aliases (GA-01).';
        return $tree;
    }

    private static function organic(): array
    {
        $all = ['facebook_page', 'instagram_account', 'fb_post', 'ig_post'];
        // Conditional means supported by conversion/dictionary, not returned for every media type or API version.
        $definitions = [
            'likes' => ['Likes / mapped reactions', 'count', 'sum', $all, ['ORG-02']],
            'comments' => ['Comments', 'count', 'sum', $all, []], 'reach' => ['Reach', 'users', 'source_distinct_for_requested_window', $all, ['ORG-03']],
            'views' => ['Views / plays', 'count', 'sum', $all, []], 'content_views' => ['Content views', 'count', 'sum', ['instagram_account'], []],
            'page_views_total' => ['Page views', 'count', 'sum', ['facebook_page'], []], 'video_views' => ['Video views', 'count', 'sum', ['facebook_page', 'fb_post'], []],
            'profile_views' => ['Profile views', 'count', 'sum', ['instagram_account'], []], 'website_clicks' => ['Website clicks', 'count', 'sum', ['instagram_account'], []],
            'profile_links_taps' => ['Profile link taps', 'count', 'sum', ['instagram_account'], []],
            'follows_and_unfollows' => ['Follows and unfollows', 'count', 'preserve_follow_type', ['instagram_account', 'facebook_page'], ['ORG-02']],
            'saves' => ['Saves', 'count', 'sum', ['instagram_account', 'ig_post'], []], 'shares' => ['Shares', 'count', 'sum', $all, []],
            'total_interactions' => ['Interactions', 'count', 'sum', $all, ['ORG-02']], 'replies' => ['Replies', 'count', 'sum', ['instagram_account', 'ig_post'], []],
            'accounts_engaged' => ['Accounts engaged', 'users', 'source_distinct_for_requested_window', ['instagram_account'], ['ORG-03']],
            'post_clicks' => ['Post clicks', 'count', 'sum', ['fb_post'], []],
            'ig_reels_avg_watch_time' => ['Reels average watch time', 'source_time_unit', 'view_weighted_average', ['ig_post'], ['ORG-04']],
            'post_video_avg_time_watched' => ['Average video watch time', 'source_time_unit', 'view_weighted_average', ['fb_post'], ['ORG-04']],
            'ig_reels_video_view_total_time' => ['Reels total watch time', 'source_time_unit', 'sum', ['ig_post'], ['ORG-04']],
            'profile_activity' => ['Profile activity', 'count', 'sum', ['ig_post'], []], 'profile_visits' => ['Profile visits', 'count', 'sum', ['ig_post'], []],
            'reposts' => ['Reposts', 'count', 'sum', ['ig_post'], []], 'follows' => ['Follows', 'count', 'sum', ['ig_post'], []],
        ];
        $aliases = [
            'likes' => ['post_reactions_by_type_total', 'page_actions_post_reactions_total'], 'comments' => ['post_comments'],
            'reach' => ['post_reach', 'page_total_media_view_unique', 'post_total_media_view_unique'], 'views' => ['plays', 'page_media_view', 'post_media_view'],
            'video_views' => ['page_video_views', 'post_video_views'], 'follows_and_unfollows' => ['page_follows'], 'saves' => ['saved'],
            'shares' => ['post_shares'], 'total_interactions' => ['post_engagement', 'post_engagements', 'page_post_engagements'],
        ];
        $metrics = []; $scopes = [];
        foreach ($definitions as $key => [$label, $unit, $reducer, $supported, $issues]) {
            $sources = [$key, ...($aliases[$key] ?? [])];
            if (!in_array($key, ['ig_reels_avg_watch_time', 'ig_reels_video_view_total_time'], true)) $sources = array_merge($sources, array_map(fn ($name) => $name.'_daily', $sources));
            $metrics[$key] = self::metric($label, $sources, $unit, $reducer, $supported, 'conditional', array_values(array_unique([...$issues, 'ORG-01', 'ORG-04'])));
        }
        foreach ($all as $scope) {
            $post = in_array($scope, ['fb_post', 'ig_post'], true);
            $scopes[$scope] = self::scope(ucwords(str_replace('_', ' ', $scope)), array_keys(array_filter($metrics, fn ($m) => in_array($scope, $m['scopes'], true))), $post ? ['post', 'snapshot date'] : ['daily', 'page/account'], [], $post ? 'lifetime_or_daily_suffix' : 'daily');
            $scopes[$scope]['selection'] = 'Select the corresponding dependency and aggregation profile; no universal scope dimension is asserted.';
            $scopes[$scope]['reducer_override'] = $post ? 'latest_snapshot_per_post_for_lifetime_metrics' : null;
        }
        $tree = self::base('facebook_organic', $metrics, $scopes,
            ['channeledAccount' => 'Facebook page / Instagram account', 'page' => 'page', 'post' => 'post / media'],
            ['dimensions.media_product_type' => ['scopes' => ['instagram_account']], 'dimensions.follow_type' => ['scopes' => ['instagram_account']], 'dimensions.contact_button_type' => ['scopes' => ['instagram_account']], 'dimensions.reaction_type' => ['scopes' => ['facebook_page', 'fb_post']]],
            ['instagram_account_passes' => 5, 'coverage' => 'Account type, media type, permissions and API version dependent; failed optional passes can leave partial data.', 'history' => 'Driver clamps Instagram account requests to approximately two years; not a source retention guarantee.', 'snapshots' => 'Repeated lifetime snapshots must not be summed as daily flows.'],
            ['ORG-01' => 'SDK fills missing requested Instagram account metrics with zero; optional pass failures can still produce partial success.', 'ORG-02' => 'Likes aliases include all reactions; follows/unfollows and engagement aliases require source-specific interpretation.', 'ORG-03' => 'Unique reach/engagement is non-additive; baseline totals and breakdown slices overlap.', 'ORG-04' => 'Exact live API/media availability and time units require versioned source verification.']);
        unset($tree['filters']['scope']);
        return $tree;
    }

    private static function mailchimp(): array
    {
        $definitions = ['sends' => 'sends', 'opens' => 'opens_total', 'opens_standard' => 'opens_standard', 'opens_proxy' => 'opens_proxy', 'clicks' => 'clicks_total', 'clicks_unique' => 'clicks_unique', 'bounces' => 'bounces_total', 'bounces_hard' => 'bounces_hard', 'bounces_soft' => 'bounces_soft', 'unsubscribes' => 'unsubscribes', 'orders' => 'orders_count', 'revenue' => 'revenue'];
        $metrics = [];
        foreach ($definitions as $key => $cached) {
            $store = in_array($key, ['orders', 'revenue'], true);
            $issues = $store ? ['MC-05'] : ['MC-01', 'MC-04'];
            if (in_array($key, ['opens_standard', 'opens_proxy'], true)) $issues[] = 'MC-03';
            if ($key === 'clicks_unique') $issues[] = 'MC-02';
            $metrics[$key] = self::metric(ucwords(str_replace('_', ' ', $key)), [$cached], $key === 'revenue' ? 'store_currency' : 'count', $key === 'clicks_unique' ? 'distinct_identity_for_requested_window' : 'sum', [$store ? 'store_conversions' : 'campaign_engagement'], 'preaggregated', $issues);
        }
        foreach (['open_rate' => 'opens / sends', 'click_rate' => 'clicks / sends'] as $key => $formula) {
            $metrics[$key] = self::metric(ucwords(str_replace('_', ' ', $key)), [], 'ratio', $formula, ['campaign_engagement'], 'derived', ['MC-06']);
        }
        $scopes = [
            'campaign_engagement' => self::scope('Campaign engagement', array_keys(array_filter($metrics, fn ($m) => in_array('campaign_engagement', $m['scopes'], true))), ['daily', 'campaign', 'optional URL slice'], []),
            'store_conversions' => self::scope('Connected store orders', ['orders', 'revenue'], ['daily', 'store'], []),
        ];
        $tree = self::base('mailchimp', $metrics, $scopes,
            ['channeledAccount' => 'audience', 'channeledCampaign' => 'email campaign', 'page' => 'clicked URL', 'store' => 'connected store'],
            ['channeledCampaign' => ['scopes' => ['campaign_engagement']], 'campaignType' => ['scopes' => ['campaign_engagement']], 'dimensions.page' => ['scopes' => ['campaign_engagement'], 'metrics' => ['clicks', 'clicks_unique']]],
            ['pipeline' => 'Email activity events and connected-store orders, then daily preaggregation.', 'attribution_window_days' => 30, 'attribution_note' => 'Configured rule, not proof of provider-attributed campaign revenue.', 'currency' => 'Store currency; conversion must precede combined totals.'],
            ['MC-01' => 'Click event identity omits URL and can collapse distinct same-second clicks.', 'MC-02' => 'Daily or per-URL distinct recipients cannot be summed into window-unique recipients.', 'MC-03' => 'Missing proxy flags become false; standard opens do not establish human-only activity.', 'MC-04' => 'Send timestamps use member last_changed; campaign-window selection and event end bounds need reconciliation.', 'MC-05' => 'Store orders are a separate scope; do not imply campaign attribution without evidence.', 'MC-06' => 'UI rates use total events / sends, not provider unique-recipient rates; they can exceed 100%.']);
        unset($tree['filters']['scope']);
        return $tree;
    }
}
