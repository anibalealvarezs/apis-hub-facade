<?php

namespace Tests\Unit\Services;

use App\Services\Analytics\ChannelCapabilityRegistry;
use App\Services\Analytics\GscAnalyticsCatalog;
use Tests\TestCase;

class ChannelAnalyticsCatalogTest extends TestCase
{
    public function testEveryCatalogHasConsistentScopeMetricAndCaveatReferences(): void
    {
        foreach (['facebook_organic', 'google_ads', 'google_analytics', 'mailchimp'] as $channel) {
            $tree = ChannelCapabilityRegistry::getAnalyticsTree($channel);
            $this->assertSame($channel, $tree['channel']);
            $this->assertNotEmpty($tree['entities']);
            $this->assertArrayHasKey('annually', $tree['granularities']);
            foreach ($tree['metrics'] as $name => $metric) {
                $this->assertNotEmpty($metric['label'], "$channel.$name");
                $this->assertNotEmpty($metric['unit']);
                $this->assertNotEmpty($metric['reducer']);
                $this->assertContains($metric['status'], ['cached', 'conditional', 'known_issue', 'derived', 'source_only', 'preaggregated', 'unsupported']);
                foreach ($metric['caveats'] as $id) $this->assertArrayHasKey($id, $tree['caveats']);
                foreach ($metric['scopes'] as $scope) {
                    $this->assertArrayHasKey($scope, $tree['scopes']);
                    $this->assertContains($name, $tree['scopes'][$scope]['metrics']);
                }
            }
            foreach ($tree['scopes'] as $scope => $definition) {
                $this->assertFalse($definition['merge_with_other_scopes']);
                foreach ($definition['metrics'] as $metric) {
                    $this->assertArrayHasKey($metric, $tree['metrics']);
                    $this->assertContains($scope, $tree['metrics'][$metric]['scopes']);
                }
            }
            foreach ($tree['dimensions'] as $dimension) {
                foreach ($dimension['scopes'] as $scope) $this->assertArrayHasKey($scope, $tree['scopes']);
            }
        }
    }

    public function testAliasesResolveAndExistingGscContractIsPreserved(): void
    {
        $this->assertSame(ChannelCapabilityRegistry::getAnalyticsTree('facebook_organic'), ChannelCapabilityRegistry::getAnalyticsTree('meta_organic'));
        $this->assertSame(ChannelCapabilityRegistry::getAnalyticsTree('google_analytics'), ChannelCapabilityRegistry::getAnalyticsTree('google_analytics_4'));
        $this->assertSame(GscAnalyticsCatalog::tree(), ChannelCapabilityRegistry::getAnalyticsTree('google_search_console'));
        $this->assertNull(ChannelCapabilityRegistry::getAnalyticsTree('unknown'));
    }

    public function testAdsDictionaryPresenceDoesNotClaimSuccessfulIngestion(): void
    {
        $tree = ChannelCapabilityRegistry::getAnalyticsTree('google_ads');
        $this->assertSame('known_issue', $tree['metrics']['conversion_value']['status']);
        $this->assertSame('known_issue', $tree['metrics']['roas_purchase']['status']);
        $this->assertSame('source_only', $tree['metrics']['video_views']['status']);
        $this->assertSame('sum(conversion_value) / sum(spend)', $tree['metrics']['roas_purchase']['reducer']);
        $this->assertArrayNotHasKey('scope', $tree['filters']);
    }

    public function testGa4AvailabilityIsScopedAndRevenueIsNotCost(): void
    {
        $tree = ChannelCapabilityRegistry::getAnalyticsTree('google_analytics');
        $this->assertFalse($tree['event_discovery']['key_event_required']);
        $this->assertSame('observed_events_in_synced_window', $tree['event_discovery']['coverage']);
        $this->assertSame('not_automatically_cached', $tree['event_discovery']['custom_parameters']);
        $this->assertSame(9, $tree['report_constraints']['max_requested_dimensions']);
        $this->assertNotContains('totalRevenue', $tree['scopes']['acquisition_matrix']['metrics']);
        $this->assertSame([], $tree['metrics']['spend']['scopes']);
        $this->assertArrayNotHasKey('spend', $tree['canonical_aliases']);
        $this->assertSame(['operator' => 'is_null'], $tree['scopes']['account']['filter']['dimensions.scope']);
        $this->assertSame('traffic_matrix', $tree['scopes']['traffic_matrix']['filter']['dimensions.scope']);
        $this->assertArrayHasKey('dimensions.source', $tree['dimensions']);
        $this->assertArrayHasKey('dimensions.landing_page', $tree['dimensions']);
        $this->assertArrayNotHasKey('dimensions.sessionSourceMedium', $tree['dimensions']);
        $this->assertSame('source_distinct_for_requested_window', $tree['metrics']['totalUsers']['reducer']);
    }

    public function testOrganicSnapshotsAndSourceUncertaintyAreExplicit(): void
    {
        $tree = ChannelCapabilityRegistry::getAnalyticsTree('facebook_organic');
        $this->assertCount(24, $tree['metrics']);
        $this->assertSame('latest_snapshot_per_post_for_lifetime_metrics', $tree['scopes']['ig_post']['reducer_override']);
        $this->assertSame('conditional', $tree['metrics']['likes']['status']);
        $this->assertContains('page_actions_post_reactions_total_daily', $tree['metrics']['likes']['sources']);
        $this->assertSame('source_time_unit', $tree['metrics']['ig_reels_avg_watch_time']['unit']);
    }

    public function testMailchimpDistinctsAndOrderScopeCannotMasqueradeAsCampaignTotals(): void
    {
        $tree = ChannelCapabilityRegistry::getAnalyticsTree('mailchimp');
        $this->assertSame('distinct_identity_for_requested_window', $tree['metrics']['clicks_unique']['reducer']);
        $this->assertSame(['store_conversions'], $tree['metrics']['revenue']['scopes']);
        $this->assertSame(['clicks', 'clicks_unique'], $tree['dimensions']['dimensions.page']['metrics']);
        $this->assertContains('MC-03', $tree['metrics']['opens_standard']['caveats']);
        $this->assertContains('MC-06', $tree['metrics']['open_rate']['caveats']);
    }
}
