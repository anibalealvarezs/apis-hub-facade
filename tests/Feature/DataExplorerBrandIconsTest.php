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
            'facebook marketing' => [FacebookMarketingDashboard::class, BrandIcon::meta()->toHtml()],
            'facebook organic' => [FacebookOrganicDashboard::class, BrandIcon::meta()->toHtml()],
            'google ads' => [\App\Filament\App\Pages\GoogleAdsDashboard::class, BrandIcon::googleAds()->toHtml()],
            'google analytics' => [GoogleAnalyticsDashboard::class, BrandIcon::googleAnalytics()->toHtml()],
            'google search console' => [GoogleSearchConsoleDashboard::class, BrandIcon::googleSearchConsole()->toHtml()],
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
            'facebook' => ['facebook', 'meta'],
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

    public function test_for_channel_resolves_specific_sub_channel_icons(): void
    {
        $ga4 = BrandIcon::forChannel('google_analytics_4');
        $this->assertNotNull($ga4);
        $this->assertSame(BrandIcon::googleAnalytics()->toHtml(), $ga4->toHtml());

        $gsc = BrandIcon::forChannel('google_search_console');
        $this->assertNotNull($gsc);
        $this->assertSame(BrandIcon::googleSearchConsole()->toHtml(), $gsc->toHtml());

        $gads = BrandIcon::forChannel('google_ads');
        $this->assertNotNull($gads);
        $this->assertSame(BrandIcon::googleAds()->toHtml(), $gads->toHtml());
    }

    public function test_for_channel_handles_facebook_organic_edge_cases(): void
    {
        // When asset dependency is an Instagram account
        $igFromDep = BrandIcon::forChannel('facebook_organic', 'instagram_account');
        $this->assertNotNull($igFromDep);
        $this->assertSame(BrandIcon::instagram()->toHtml(), $igFromDep->toHtml());

        // When subType or granularity is ig_post / instagram
        $igFromSubType = BrandIcon::forChannel('facebook_organic', null, 'ig_post');
        $this->assertNotNull($igFromSubType);
        $this->assertSame(BrandIcon::instagram()->toHtml(), $igFromSubType->toHtml());

        // When dependency is a Facebook page, resolve to Meta or Facebook mark
        $metaOrganic = BrandIcon::forChannel('facebook_organic');
        $this->assertNotNull($metaOrganic);
        $this->assertSame(BrandIcon::meta()->toHtml(), $metaOrganic->toHtml());

        // Direct facebook_marketing / meta_ads channel resolves to Meta mark
        $fbMarketing = BrandIcon::forChannel('facebook_marketing');
        $this->assertNotNull($fbMarketing);
        $this->assertSame(BrandIcon::meta()->toHtml(), $fbMarketing->toHtml());
    }

    public function test_for_channel_falls_back_to_provider(): void
    {
        $shopify = BrandIcon::forChannel('shopify');
        $this->assertNotNull($shopify);
        $this->assertSame(BrandIcon::shopify()->toHtml(), $shopify->toHtml());

        $mailchimp = BrandIcon::forChannel('mailchimp');
        $this->assertNotNull($mailchimp);
        $this->assertSame(BrandIcon::mailchimp()->toHtml(), $mailchimp->toHtml());
    }
}
