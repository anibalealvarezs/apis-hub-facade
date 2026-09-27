<?php

namespace Tests\Feature;

use App\Filament\App\Pages\FacebookMarketingDashboard;
use App\Filament\App\Pages\FacebookOrganicDashboard;
use App\Filament\App\Pages\GoogleAnalyticsDashboard;
use App\Filament\App\Pages\GoogleSearchConsoleDashboard;
use App\Filament\App\Pages\MailchimpDashboard;
use App\Support\BrandIcon;
use Illuminate\Contracts\Support\Htmlable;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DataExplorerBrandIconsTest extends TestCase
{
    #[DataProvider('channelPagesProvider')]
    public function test_channel_page_uses_brand_icon(string $pageClass, string $brand): void
    {
        $icon = $pageClass::getNavigationIcon();

        $this->assertInstanceOf(Htmlable::class, $icon);
        $this->assertStringContainsString($brand, $icon->toHtml());
    }

    public static function channelPagesProvider(): array
    {
        return [
            'facebook marketing' => [FacebookMarketingDashboard::class, BrandIcon::facebook()->toHtml()],
            'facebook organic' => [FacebookOrganicDashboard::class, BrandIcon::facebook()->toHtml()],
            'google analytics' => [GoogleAnalyticsDashboard::class, BrandIcon::google()->toHtml()],
            'google search console' => [GoogleSearchConsoleDashboard::class, BrandIcon::google()->toHtml()],
            'mailchimp' => [MailchimpDashboard::class, BrandIcon::mailchimp()->toHtml()],
        ];
    }

    #[DataProvider('brandIconProvider')]
    public function test_brand_icons_render_monochrome_svg(string $method): void
    {
        $html = BrandIcon::$method()->toHtml();

        $this->assertStringContainsString('<svg', $html);
        $this->assertStringContainsString('fill="currentColor"', $html);
        $this->assertStringContainsString('<path', $html);
    }

    public static function brandIconProvider(): array
    {
        return [
            'facebook' => ['facebook'],
            'google' => ['google'],
            'meta' => ['meta'],
            'klaviyo' => ['klaviyo'],
            'shopify' => ['shopify'],
            'netsuite' => ['netsuite'],
            'amazon' => ['amazon'],
            'bigcommerce' => ['bigcommerce'],
            'pinterest' => ['pinterest'],
            'linkedin' => ['linkedin'],
            'tiktok' => ['tiktok'],
            'x' => ['x'],
            'triple whale' => ['tripleWhale'],
            'salesforce' => ['salesforce'],
            'hubspot' => ['hubspot'],
            'mailchimp' => ['mailchimp'],
        ];
    }

    #[DataProvider('providerKeyProvider')]
    public function test_for_provider_resolves_each_catalog_provider(string $providerKey, string $method): void
    {
        $icon = BrandIcon::forProvider($providerKey);

        $this->assertInstanceOf(Htmlable::class, $icon);
        $this->assertSame(BrandIcon::$method()->toHtml(), $icon->toHtml());
    }

    public static function providerKeyProvider(): array
    {
        // The six providers hard-coded in DataSources::getProviders().
        return [
            'google' => ['google', 'google'],
            'facebook' => ['facebook', 'facebook'],
            'tiktok' => ['tiktok', 'tiktok'],
            'klaviyo' => ['klaviyo', 'klaviyo'],
            'shopify' => ['shopify', 'shopify'],
            'mailchimp' => ['mailchimp', 'mailchimp'],
        ];
    }

    public function test_for_provider_is_case_insensitive_and_trims(): void
    {
        $this->assertSame(BrandIcon::tripleWhale()->toHtml(), BrandIcon::forProvider('  Triple_Whale ')->toHtml());
    }

    public function test_for_provider_returns_null_for_unknown_provider(): void
    {
        $this->assertNull(BrandIcon::forProvider('not_a_provider'));
        $this->assertNull(BrandIcon::forProvider(null));
        $this->assertNull(BrandIcon::forProvider(''));
    }

    public function test_for_provider_applies_requested_size_classes(): void
    {
        $icon = BrandIcon::forProvider('shopify', 'h-5 w-5');

        $this->assertStringContainsString('class="h-5 w-5"', $icon->toHtml());
        $this->assertStringNotContainsString('class="h-6 w-6"', $icon->toHtml());
    }

    public function test_for_provider_defaults_to_navigation_size(): void
    {
        $this->assertSame(BrandIcon::google()->toHtml(), BrandIcon::forProvider('google')->toHtml());
    }
}
