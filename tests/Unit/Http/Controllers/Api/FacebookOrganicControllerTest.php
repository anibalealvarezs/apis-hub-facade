<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Controllers\Api;

use App\Http\Controllers\Api\FacebookOrganicController;
use PHPUnit\Framework\TestCase;

final class FacebookOrganicControllerTest extends TestCase
{
    public function test_fb_pages_filters_prefer_platform_id_over_channeled_account(): void
    {
        $controller = new FacebookOrganicController();
        $filters = [
            'account_type' => 'facebook_page',
            'channel' => 'facebook_organic',
            'period' => 'daily',
        ];

        $accounts = $this->invokePrivate(
            $controller,
            'parseSelectedAccounts',
            [['123|124|147613761768682|NONE']]
        );

        $this->invokePrivate(
            $controller,
            'applySelectedAccountFilters',
            [&$filters, 'facebook', 'fb_pages', $accounts]
        );

        $this->assertSame('147613761768682', $filters['page_platform_id']);
        $this->assertArrayNotHasKey('channeledAccount', $filters);
    }

    public function test_facebook_filters_fallback_to_channeled_account_when_platform_id_missing(): void
    {
        $controller = new FacebookOrganicController();
        $filters = [
            'account_type' => 'facebook_page',
            'channel' => 'facebook_organic',
            'period' => 'daily',
        ];

        $accounts = $this->invokePrivate(
            $controller,
            'parseSelectedAccounts',
            [['123|NONE|NONE|NONE']]
        );

        $this->invokePrivate(
            $controller,
            'applySelectedAccountFilters',
            [&$filters, 'facebook', 'fb_pages', $accounts]
        );

        $this->assertSame('123', $filters['channeledAccount']);
    }

    public function test_fb_posts_filters_prefer_platform_id_when_both_ids_exist(): void
    {
        $controller = new FacebookOrganicController();
        $filters = [
            'account_type' => 'facebook_page',
            'post' => 'NOT_NULL',
        ];

        $accounts = $this->invokePrivate(
            $controller,
            'parseSelectedAccounts',
            [['123|124|147613761768682|NONE']]
        );

        $this->invokePrivate(
            $controller,
            'applySelectedAccountFilters',
            [&$filters, 'facebook', 'fb_posts', $accounts]
        );

        $this->assertSame('147613761768682', $filters['page_platform_id']);
        $this->assertArrayNotHasKey('channeledAccount', $filters);
    }

    public function test_extract_facebook_page_ids_from_aggregate_response_returns_unique_ids(): void
    {
        $controller = new FacebookOrganicController();

        $pageIds = $this->invokePrivate(
            $controller,
            'extractFacebookPageIdsFromAggregateResponse',
            [[
                'data' => [
                    ['page_id' => 119],
                    ['PAGE_ID' => '119'],
                    ['page_id' => 120],
                    ['page_id' => null],
                ],
            ]]
        );

        $this->assertSame(['119', '120'], $pageIds);
    }

    public function test_filter_facebook_post_rows_keeps_only_selected_page_platform_posts(): void
    {
        $controller = new FacebookOrganicController();

        $rows = $this->invokePrivate(
            $controller,
            'filterFacebookPostRows',
            [[
                ['post_id' => '112975583443266_122267048786074498', 'reach' => '330'],
                ['post_id' => '18043593167523327', 'reach' => '876'],
                ['post_id' => '112975583443266_122140387034074498', 'reach' => '36'],
            ], ['112975583443266']]
        );

        $this->assertCount(2, $rows);
        $this->assertSame('112975583443266_122267048786074498', $rows[0]['post_id']);
        $this->assertSame('112975583443266_122140387034074498', $rows[1]['post_id']);
    }

    public function test_prefer_resolved_facebook_page_filter_replaces_platform_filters_for_non_summary_queries(): void
    {
        $controller = new FacebookOrganicController();
        $filters = [
            'account_type' => 'facebook_page',
            'page' => '119',
            'page_platform_id' => '112975583443266',
            'channeledAccount' => '177',
            'post' => 'NOT_NULL',
        ];

        $accounts = [
            'fbAccountIds' => ['177'],
            'igAccountIds' => ['178'],
            'fbPlatformIds' => ['112975583443266'],
            'fbPageIds' => ['119'],
        ];

        $this->invokePrivate(
            $controller,
            'preferResolvedFacebookPageFilter',
            [&$filters, 'facebook', 'fb_posts', $accounts]
        );

        $this->assertSame('119', $filters['page']);
        $this->assertArrayNotHasKey('page_platform_id', $filters);
        $this->assertArrayNotHasKey('channeledAccount', $filters);
    }

    public function test_build_chart_aggregations_keeps_plain_aliases_for_account_charts(): void
    {
        $controller = new FacebookOrganicController();

        $result = $this->invokePrivate(
            $controller,
            'buildChartAggregations',
            [[
                'reach' => 'reach',
                'likes' => 'likes',
            ], false]
        );

        $this->assertSame([
            'reach' => 'reach',
            'likes' => 'likes',
        ], $result);
    }

    public function test_build_chart_aggregations_adds_trend_aliases_for_post_charts(): void
    {
        $controller = new FacebookOrganicController();

        $result = $this->invokePrivate(
            $controller,
            'buildChartAggregations',
            [[
                'reach' => 'reach',
                'likes' => 'likes',
            ], true]
        );

        $this->assertSame([
            'trend_total_reach' => 'reach',
            'trend_total_likes' => 'likes',
        ], $result);
    }

    public function test_ig_accounts_tab_config_is_channel_scoped_and_content_views_uses_supported_expression(): void
    {
        $controller = new FacebookOrganicController();

        $result = $this->invokePrivate($controller, 'getTabConfig', ['ig_accounts']);

        $this->assertSame('facebook_organic', $result['filters']['channel']);
        $this->assertSame('views', $result['aggregations']['content_views']);
    }

    public function test_post_chart_filters_use_daily_period_for_fb_posts(): void
    {
        $controller = new FacebookOrganicController();
        $filters = $this->invokePrivate($controller, 'getTabConfig', ['fb_posts'])['filters'];

        $this->invokePrivate($controller, 'applyPostChartDailyFilters', [&$filters]);

        $this->assertSame('daily', $filters['period']);
        $this->assertArrayNotHasKey('snapshot_fallback_mode', $filters);
        $this->assertArrayNotHasKey('latest_snapshot', $filters);
    }

    public function test_post_chart_filters_use_daily_period_for_ig_posts(): void
    {
        $controller = new FacebookOrganicController();
        $filters = $this->invokePrivate($controller, 'getTabConfig', ['ig_posts'])['filters'];

        $this->invokePrivate($controller, 'applyPostChartDailyFilters', [&$filters]);

        $this->assertSame('daily', $filters['period']);
        $this->assertArrayNotHasKey('snapshot_fallback_mode', $filters);
        $this->assertArrayNotHasKey('latest_snapshot', $filters);
    }

    /**
     * @param array<int, mixed> $arguments
     */
    private function invokePrivate(object $object, string $method, array $arguments): mixed
    {
        $reflection = new \ReflectionMethod($object, $method);

        return $reflection->invokeArgs($object, $arguments);
    }
}
