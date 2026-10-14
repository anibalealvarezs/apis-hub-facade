<?php

use App\Filament\App\Pages\DataSources;
use App\Models\ApisHubRelease;
use App\Models\Project;
use App\Models\User;
use App\Support\BrandIcon;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->user = User::factory()->create();
    Permission::firstOrCreate(['name' => 'view_settings', 'guard_name' => 'web']);
    $this->user->givePermissionTo('view_settings');
    $this->actingAs($this->user);
});

/**
 * Bind a tenant whose release exposes exactly the given supported channels, then return a
 * page instance seeded with the given sync_config.
 */
function pageWithChannels(array $supportedChannels, array $syncConfig = []): DataSources
{
    $release = ApisHubRelease::create([
        'version_tag' => 'v-test-'.collect($supportedChannels)->map(fn ($c) => substr($c, 0, 6))->implode('-'),
        'is_active' => true,
        'is_default' => false,
        'supported_channels' => $supportedChannels,
    ]);

    $project = Project::factory()->create([
        'user_id' => auth()->id(),
        'apis_hub_release_id' => $release->id,
    ]);

    Filament::setTenant($project);

    $page = new DataSources;
    $page->data = $syncConfig;

    return $page;
}

function providerKeys(DataSources $page): array
{
    return array_keys($page->getProviders());
}

function channelKeys(DataSources $page, string $providerKey): array
{
    return array_column($page->getProviders()[$providerKey]['channels'], 'key');
}

test('providers exposing a compatible channel sort before those that do not', function () {
    $page = pageWithChannels(['google_search_console', 'facebook_marketing', 'mailchimp']);

    // Compatible providers (Facebook, Google, Mailchimp) lead; the fully incompatible ones
    // (Klaviyo, Shopify, TikTok) follow. Nothing is enabled, so both groups fall back to
    // alphabetical order.
    expect(providerKeys($page))->toBe(['facebook', 'google', 'mailchimp', 'klaviyo', 'shopify', 'tiktok']);
});

test('compatible providers with an enabled channel sort first', function () {
    $page = pageWithChannels(
        ['google_search_console', 'facebook_marketing', 'mailchimp'],
        ['mailchimp' => ['enabled' => true]],
    );

    // Mailchimp has an enabled compatible channel; Google and Facebook do not.
    expect(providerKeys($page))->toBe(['mailchimp', 'facebook', 'google', 'klaviyo', 'shopify', 'tiktok']);
});

test('providers with an enabled channel sort alphabetically among themselves', function () {
    $page = pageWithChannels(
        ['google_search_console', 'facebook_marketing', 'mailchimp'],
        [
            'mailchimp' => ['enabled' => true],
            'facebook_marketing' => ['enabled' => true],
        ],
    );

    // Facebook and Mailchimp both have an enabled compatible channel → alphabetical.
    expect(providerKeys($page))->toBe(['facebook', 'mailchimp', 'google', 'klaviyo', 'shopify', 'tiktok']);
});

test('an enabled but incompatible channel does not promote a provider', function () {
    $page = pageWithChannels(
        ['google_search_console', 'mailchimp'],
        ['tiktok_marketing' => ['enabled' => true]],
    );

    // TikTok is entirely incompatible, so its enabled channel does not count.
    expect(providerKeys($page))->toBe(['google', 'mailchimp', 'facebook', 'klaviyo', 'shopify', 'tiktok']);
});

test('compatible channels sort before incompatible channels within a provider', function () {
    $page = pageWithChannels(['google_search_console', 'google_ads']);

    // Google Analytics is unsupported by this release, so it is demoted last.
    expect(channelKeys($page, 'google'))->toBe(['google_ads', 'google_search_console', 'google_analytics']);
});

test('enabled channels sort before disabled channels within a compatible provider', function () {
    $page = pageWithChannels(
        ['shopify_metrics', 'shopify_orders', 'shopify_products', 'shopify_customers'],
        ['shopify_products' => ['enabled' => true]],
    );

    // Shopify Products is enabled, so it leads even though "Shopify Customers" sorts
    // ahead of it alphabetically.
    expect(channelKeys($page, 'shopify'))->toBe([
        'shopify_products',
        'shopify_customers',
        'shopify_metrics',
        'shopify_orders',
    ]);
});

test('enabled channels sort alphabetically among themselves', function () {
    $page = pageWithChannels(
        ['shopify_metrics', 'shopify_orders', 'shopify_products', 'shopify_customers'],
        [
            'shopify_products' => ['enabled' => true],
            'shopify_orders' => ['enabled' => true],
        ],
    );

    expect(channelKeys($page, 'shopify'))->toBe([
        'shopify_orders',
        'shopify_products',
        'shopify_customers',
        'shopify_metrics',
    ]);
});

test('the flat enabled mirror is honoured when the nested flag is absent', function () {
    $page = pageWithChannels(
        ['google_search_console', 'mailchimp'],
        ['google_search_console_enabled' => true],
    );

    expect($page->isChannelEnabled('google_search_console'))->toBeTrue();
    expect(providerKeys($page))->toBe(['google', 'mailchimp', 'facebook', 'klaviyo', 'shopify', 'tiktok']);
});

test('the nested enabled flag wins over the flat mirror', function () {
    $page = pageWithChannels(
        ['google_search_console', 'mailchimp'],
        [
            'google_search_console' => ['enabled' => false],
            'google_search_console_enabled' => true,
        ],
    );

    expect($page->isChannelEnabled('google_search_console'))->toBeFalse();
});

test('an absent channel reports as disabled', function () {
    $page = pageWithChannels(['mailchimp']);

    expect($page->isChannelEnabled('google_ads'))->toBeFalse();
});

test('each channel carries its compatibility and enabled flags', function () {
    $page = pageWithChannels(
        ['google_search_console', 'mailchimp'],
        ['google_search_console' => ['enabled' => true]],
    );

    $google = $page->getProviders()['google']['channels'];

    $this->assertTrue($google[0]['compatible']);
    $this->assertTrue($google[0]['enabled']);
    $this->assertSame('Active', $google[0]['status']);
    $this->assertFalse($google[1]['compatible']);
    $this->assertSame('Coming Soon', $google[1]['status']);
});

test('every provider in the catalog has a resolvable branded icon', function () {
    $page = pageWithChannels(['mailchimp']);

    foreach (array_keys($page->getProviders()) as $providerKey) {
        $this->assertNotNull(
            BrandIcon::forProvider($providerKey, 'h-5 w-5'),
            "Provider [{$providerKey}] has no branded icon."
        );
    }
});

test('the sidebar renders branded provider marks rather than generic heroicons', function () {
    $release = ApisHubRelease::create([
        'version_tag' => 'v-render-test',
        'is_active' => true,
        'is_default' => false,
        'supported_channels' => ['google_search_console', 'facebook_marketing', 'mailchimp'],
    ]);

    $project = Project::factory()->create([
        'user_id' => auth()->id(),
        'apis_hub_release_id' => $release->id,
    ]);

    Filament::setTenant($project);

    $component = Livewire::test(DataSources::class)->assertSuccessful();

    foreach (['google', 'facebook', 'mailchimp', 'klaviyo', 'shopify', 'tiktok'] as $providerKey) {
        $component->assertSee(
            BrandIcon::forProvider($providerKey, 'h-5 w-5')->toHtml(),
            escape: false
        );
    }
});
