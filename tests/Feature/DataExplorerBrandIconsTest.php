<?php

namespace Tests\Feature;

use App\Filament\App\Pages\FacebookMarketingDashboard;
use App\Filament\App\Pages\FacebookOrganicDashboard;
use App\Filament\App\Pages\GoogleAnalyticsDashboard;
use App\Filament\App\Pages\GoogleSearchConsoleDashboard;
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
        ];
    }
}
