<?php

use App\Filament\App\Pages\DataSources;
use App\Models\ApisHubRelease;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->user = User::factory()->create();
    Permission::firstOrCreate(['name' => 'view_settings', 'guard_name' => 'web']);
    $this->user->givePermissionTo('view_settings');
    $this->actingAs($this->user);
});

/**
 * Bind a tenant whose release exposes a Mailchimp schema whose only array field is
 * "audiences" — mirroring MailchimpProfile, which deliberately has no "accounts"
 * field because credentials are stored outside the form.
 */
function mailchimpTenant(array $syncConfig = []): Project
{
    $release = ApisHubRelease::create([
        'version_tag' => 'v-merge-test',
        'is_active' => true,
        'is_default' => false,
        'supported_channels' => ['mailchimp'],
        'config_schemas' => [
            'mailchimp' => [
                'fields' => [
                    'enabled' => ['type' => 'boolean', 'default' => true],
                    'cron_recent_hour' => ['type' => 'integer', 'default' => 4],
                    'cron_recent_minute' => ['type' => 'integer', 'default' => 0],
                    'audiences' => [
                        'type' => 'array',
                        'default' => [],
                        'item_schema' => [
                            'id' => ['type' => 'string'],
                            'name' => ['type' => 'string'],
                            'enabled' => ['type' => 'boolean', 'default' => false],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $project = Project::factory()->create([
        'user_id' => auth()->id(),
        'apis_hub_release_id' => $release->id,
        'sync_config' => $syncConfig,
    ]);

    Filament::setTenant($project);

    return $project;
}

function invokeMerge(DataSources $page, array $liveAssets): void
{
    $method = new ReflectionMethod($page, 'mergeDiscoveredAssets');
    $method->setAccessible(true);
    $method->invoke($page, $liveAssets);
}

test('merging discovered audiences preserves stored mailchimp credentials', function () {
    $tenant = mailchimpTenant([
        'mailchimp' => [
            'enabled' => true,
            'accounts' => [
                [
                    'account_id' => 'mc_abc1234567',
                    'account_name' => 'Mabe',
                    'api_key' => 'key-us21',
                    'server_prefix' => 'us21',
                ],
            ],
        ],
        'klaviyo' => ['enabled' => true],
    ]);

    $page = new DataSources;
    $page->activeChannel = 'mailchimp';
    $page->data = [
        'mailchimp' => [
            'enabled' => true,
            'cron_time' => '04:00',
            'audiences' => [],
        ],
    ];
    $page->form = $page->getForm('form');

    invokeMerge($page, [
        'audiences' => [['id' => 'aud-1', 'name' => 'Newsletter', 'enabled' => false]],
    ]);

    $sync = $tenant->fresh()->sync_config;

    expect($sync['mailchimp']['accounts'][0]['api_key'])->toBe('key-us21')
        ->and($sync['mailchimp']['accounts'][0]['account_id'])->toBe('mc_abc1234567')
        ->and($sync['mailchimp']['audiences'][0]['id'])->toBe('aud-1')
        ->and($sync['klaviyo']['enabled'])->toBeTrue();
});

test('a channel that was never connected stays unconnected after a merge', function () {
    $tenant = mailchimpTenant();

    $page = new DataSources;
    $page->activeChannel = 'mailchimp';
    $page->data = [
        'mailchimp' => [
            'enabled' => true,
            'cron_time' => '04:00',
            'audiences' => [],
        ],
    ];
    $page->form = $page->getForm('form');

    invokeMerge($page, [
        'audiences' => [['id' => 'aud-1', 'name' => 'Newsletter', 'enabled' => false]],
    ]);

    expect($tenant->fresh()->sync_config['mailchimp']['accounts'] ?? null)->toBeNull();
});
