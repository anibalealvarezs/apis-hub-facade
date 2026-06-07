<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Controllers\Api;

use App\Http\Controllers\Api\FacebookOrganicController;
use PHPUnit\Framework\TestCase;

final class FacebookOrganicControllerTest extends TestCase
{
    public function test_fb_pages_filters_include_platform_and_account_when_available(): void
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
        $this->assertSame('123', $filters['channeledAccount']);
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

    public function test_fb_posts_filters_prefer_channeled_account_when_both_ids_exist(): void
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

        $this->assertSame('123', $filters['channeledAccount']);
        $this->assertArrayNotHasKey('page_platform_id', $filters);
    }

    /**
     * @param array<int, mixed> $arguments
     */
    private function invokePrivate(object $object, string $method, array $arguments): mixed
    {
        $reflection = new \ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $arguments);
    }
}

