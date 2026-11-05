<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Filament\App\Pages\CatalogEntity;
use App\Filament\App\Pages\MarketingOverview;
use App\Models\ApisHubRelease;
use App\Models\AssetGroup;
use App\Models\AssetGroupItem;
use App\Models\Project;
use App\Models\ProjectUserAssetGroup;
use App\Models\User;
use App\Services\Catalog\CatalogLinkRenderer;
use App\Services\Catalog\CatalogScopeResolver;
use App\Services\Catalog\EntityReference;
use App\Services\RemoteEngineService;
use Database\Seeders\ApisHubReleaseV1184Seeder;
use Database\Seeders\ApisHubReleaseV1190Seeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Livewire\Livewire;
use Mockery;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CatalogEntityPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        $_ENV['APP_ENV'] = 'testing';
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = ':memory:';
        $_ENV['CACHE_STORE'] = 'array';
        $_ENV['SESSION_DRIVER'] = 'array';
        $_ENV['QUEUE_CONNECTION'] = 'sync';

        parent::setUp();

        Permission::firstOrCreate(['name' => 'view_data', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view_customer_pii', 'guard_name' => 'web']);
    }

    private function createAdsProject(User $owner, array $syncConfig = []): Project
    {
        $defaultConfig = [
            'facebook_marketing' => [
                'ad_accounts' => [
                    ['id' => 'act_101', 'enabled' => true, 'lost_access' => false],
                    ['id' => 'act_102', 'enabled' => true, 'lost_access' => false],
                    ['id' => 'act_999', 'enabled' => false, 'lost_access' => false],
                ],
            ],
            'google_ads' => [
                'ad_accounts' => [
                    ['id' => 'gads_201', 'enabled' => true, 'lost_access' => false],
                    ['id' => 'gads_202', 'enabled' => true, 'lost_access' => false],
                ],
            ],
        ];

        $project = Project::factory()->create([
            'user_id'              => $owner->id,
            'subdomain'            => 'cat-' . uniqid(),
            'remote_admin_api_key' => 'test-key',
            'last_deployed_at'     => now(),
            'sync_config'          => $syncConfig !== [] ? $syncConfig : $defaultConfig,
        ]);

        $this->attachMember($owner, $project, 'project_owner', true);

        return $project;
    }

    private function attachMember(
        User $user,
        Project $project,
        string $roleName = 'project_user',
        bool $unrestricted = false,
        array $groupIds = []
    ): void {
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

        DB::table('project_user')->updateOrInsert(
            [
                'project_id' => $project->id,
                'user_id'    => $user->id,
            ],
            [
                'asset_access_unrestricted' => $unrestricted,
                'created_at'                => now(),
                'updated_at'                => now(),
            ]
        );

        DB::table('model_has_roles')->insertOrIgnore([
            'role_id'    => $role->id,
            'model_type' => User::class,
            'model_id'   => $user->id,
            'project_id' => $project->id,
        ]);

        foreach ($groupIds as $groupId) {
            ProjectUserAssetGroup::firstOrCreate([
                'project_id'     => $project->id,
                'user_id'        => $user->id,
                'asset_group_id' => $groupId,
            ]);
        }
    }

    private function createAssetGroup(Project $project, string $name, array $channelAssets): AssetGroup
    {
        $group = AssetGroup::create([
            'project_id' => $project->id,
            'name'       => $name,
        ]);

        foreach ($channelAssets as $channel => $assetIds) {
            foreach ($assetIds as $assetId) {
                AssetGroupItem::create([
                    'asset_group_id' => $group->id,
                    'channel'        => $channel,
                    'asset_id'       => $assetId,
                ]);
            }
        }

        return $group;
    }

    public function test_entity_reference_formatting_parsing_and_validation(): void
    {
        $formatted = EntityReference::format('channeled', 'facebook_marketing', 'campaigns', 42);
        $this->assertSame('channeled:facebook_marketing:campaigns:42', $formatted);

        $parsed = EntityReference::parse($formatted);
        $this->assertSame('channeled', $parsed->getLayer());
        $this->assertSame('facebook_marketing', $parsed->getChannel());
        $this->assertSame('campaigns', $parsed->getEntityType());
        $this->assertSame(42, $parsed->getId());
        $this->assertTrue(EntityReference::isPhase1Supported('campaigns'));
        $this->assertFalse(EntityReference::isPhase1Supported('orders'));

        $this->assertNull(EntityReference::tryParse('invalid-ref'));
        $this->assertNull(EntityReference::tryParse(''));

        $this->expectException(InvalidArgumentException::class);
        EntityReference::parse('unknown:facebook_marketing:campaigns:1');
    }

    public function test_scope_resolver_for_unrestricted_owner_and_restricted_collaborators(): void
    {
        $owner = User::factory()->create();
        $owner->givePermissionTo('view_data');
        $project = $this->createAdsProject($owner);

        $groupA = $this->createAssetGroup($project, 'Group Alpha', [
            'facebook_marketing' => ['act_101'],
        ]);
        $groupB = $this->createAssetGroup($project, 'Group Beta', [
            'facebook_marketing' => ['act_102'],
            'google_ads'         => ['gads_201'],
        ]);

        $collaboratorA = User::factory()->create();
        $collaboratorA->givePermissionTo('view_data');
        $this->attachMember($collaboratorA, $project, 'project_user', false, [$groupA->id]);

        $collaboratorOverlap = User::factory()->create();
        $collaboratorOverlap->givePermissionTo('view_data');
        $this->attachMember($collaboratorOverlap, $project, 'project_user', false, [$groupA->id, $groupB->id]);

        /** @var CatalogScopeResolver $resolver */
        $resolver = app(CatalogScopeResolver::class);

        // 1. Unrestricted owner sees all groups and all enabled assets across both Ads channels
        $ownerOptions = $resolver->getScopeOptions($project, $owner);
        $this->assertArrayHasKey('all', $ownerOptions);
        $this->assertArrayHasKey((string) $groupA->id, $ownerOptions);
        $this->assertArrayHasKey((string) $groupB->id, $ownerOptions);

        $ownerScope = $resolver->resolveScope($project, $owner, 'all');
        $this->assertTrue($ownerScope['is_unrestricted']);
        $this->assertTrue($ownerScope['can_view_pii']);
        $this->assertFalse($ownerScope['unauthorized_group']);
        $this->assertSame(['act_101', 'act_102'], $ownerScope['channels']['facebook_marketing']['platform_ids']);
        $this->assertSame(['gads_201', 'gads_202'], $ownerScope['channels']['google_ads']['platform_ids']);

        // 2. Collaborator assigned only to Group A sees only Group A in options and resolves only act_101
        $collabAOptions = $resolver->getScopeOptions($project, $collaboratorA);
        $this->assertArrayHasKey('all', $collabAOptions);
        $this->assertArrayHasKey((string) $groupA->id, $collabAOptions);
        $this->assertArrayNotHasKey((string) $groupB->id, $collabAOptions);

        $collabAScope = $resolver->resolveScope($project, $collaboratorA, 'all');
        $this->assertFalse($collabAScope['is_unrestricted']);
        $this->assertFalse($collabAScope['can_view_pii']);
        $this->assertSame(['act_101'], $collabAScope['channels']['facebook_marketing']['platform_ids']);
        $this->assertArrayNotHasKey('google_ads', $collabAScope['channels']);

        // 3. Collaborator A requesting Group B gets unauthorized_group = true and empty channels
        $unauthorizedScope = $resolver->resolveScope($project, $collaboratorA, (string) $groupB->id);
        $this->assertTrue($unauthorizedScope['unauthorized_group']);
        $this->assertFalse($unauthorizedScope['has_any_assets']);
        $this->assertSame([], $unauthorizedScope['channels']);

        // 4. Collaborator with overlapping groups resolves the union in 'all' and narrows when selecting Group A
        $overlapAll = $resolver->resolveScope($project, $collaboratorOverlap, 'all');
        $this->assertSame(['act_101', 'act_102'], $overlapAll['channels']['facebook_marketing']['platform_ids']);
        $this->assertSame(['gads_201'], $overlapAll['channels']['google_ads']['platform_ids']);

        $overlapGroupA = $resolver->resolveScope($project, $collaboratorOverlap, (string) $groupA->id);
        $this->assertSame('asset_group', $overlapGroupA['scope_type']);
        $this->assertSame($groupA->id, $overlapGroupA['scope_id']);
        $this->assertSame(['act_101'], $overlapGroupA['channels']['facebook_marketing']['platform_ids']);
        $this->assertArrayNotHasKey('google_ads', $overlapGroupA['channels']);

        // 5. Revoking Group B from collaboratorOverlap immediately removes Group B's assets
        ProjectUserAssetGroup::where('project_id', $project->id)
            ->where('user_id', $collaboratorOverlap->id)
            ->where('asset_group_id', $groupB->id)
            ->delete();

        $afterRevoke = $resolver->resolveScope($project, $collaboratorOverlap, 'all');
        $this->assertSame(['act_101'], $afterRevoke['channels']['facebook_marketing']['platform_ids']);
        $this->assertArrayNotHasKey('google_ads', $afterRevoke['channels']);

        // 6. Granting explicit view_customer_pii allows restricted collaborator to pass PII guard
        $this->assertFalse($resolver->canViewCustomerPii($project, $collaboratorA));
        $collaboratorA->givePermissionTo('view_customer_pii');
        $this->assertTrue($resolver->canViewCustomerPii($project, $collaboratorA));
    }

    public function test_catalog_entity_list_mode_renders_ads_entities_and_restores_deep_link_filters(): void
    {
        $owner = User::factory()->create();
        $owner->givePermissionTo('view_data');
        $project = $this->createAdsProject($owner);

        $this->actingAs($owner);
        Filament::setTenant($project);

        $capturedPayloads = [];
        $engineMock = Mockery::mock(RemoteEngineService::class);
        $engineMock->shouldReceive('listCatalogEntities')
            ->once()
            ->withArgs(function (Project $proj, string $entity, array $payload) use ($project, &$capturedPayloads) {
                $capturedPayloads[] = [$proj->id, $entity, $payload];

                return $proj->id === $project->id && $entity === 'campaigns';
            })
            ->andReturn([
                'status' => 'success',
                'data'   => [
                    [
                        'reference'     => 'channeled:facebook_marketing:campaigns:10',
                        'layer'         => 'channeled',
                        'channel'       => 'facebook_marketing',
                        'channel_label' => 'Meta Ads',
                        'entity_type'   => 'campaigns',
                        'id'            => 10,
                        'platform_id'   => 'fb_camp_10',
                        'name'          => 'Summer Prospecting Campaign',
                        'status'        => 'ACTIVE',
                        'attributes'    => [
                            'objective'   => 'OUTCOME_SALES',
                            'buying_type' => 'AUCTION',
                            'budget'      => 250.0,
                            'currency'    => 'USD',
                        ],
                        'relationships' => [
                            'account' => [
                                'reference'   => 'channeled:facebook_marketing:accounts:1',
                                'entity_type' => 'accounts',
                                'name'        => 'Primary Meta Account',
                                'platform_id' => 'act_101',
                            ],
                        ],
                        'provenance'    => [
                            'freshness_timestamp' => '2026-10-01 12:00:00',
                        ],
                    ],
                ],
                'meta'   => [
                    'total'     => 1,
                    'page'      => 1,
                    'per_page'  => 25,
                    'last_page' => 1,
                ],
            ]);

        $this->app->instance(RemoteEngineService::class, $engineMock);

        Livewire::withQueryParams([
            'channel' => 'facebook_marketing',
            'search'  => 'Summer',
            'status'  => 'ACTIVE',
            'sort'    => 'budget',
            'dir'     => 'DESC',
        ])
            ->test(CatalogEntity::class, ['entity' => 'campaigns'])
            ->assertSuccessful()
            ->assertSet('channel', 'facebook_marketing')
            ->assertSet('search', 'Summer')
            ->assertSet('status', 'ACTIVE')
            ->assertSet('sort', 'budget')
            ->assertSet('dir', 'DESC')
            ->assertSee('Summer Prospecting Campaign')
            ->assertSee('Primary Meta Account')
            ->assertSee('OUTCOME_SALES');

        $this->assertCount(1, $capturedPayloads);
        [, , $sentPayload] = $capturedPayloads[0];
        $this->assertSame('Summer', $sentPayload['search']);
        $this->assertSame('ACTIVE', $sentPayload['filters']['status']);
        $this->assertSame('facebook_marketing', $sentPayload['filters']['channel']);
        $this->assertSame('budget', $sentPayload['sort']['field']);
        $this->assertSame('DESC', $sentPayload['sort']['dir']);
        $this->assertSame(['act_101', 'act_102'], $sentPayload['authorized_scope']['channels']['facebook_marketing']['platform_ids']);
    }

    public function test_catalog_entity_detail_mode_renders_profile_hierarchy_metrics_and_milestone_activity(): void
    {
        $owner = User::factory()->create();
        $owner->givePermissionTo('view_data');
        $project = $this->createAdsProject($owner);

        $this->actingAs($owner);
        Filament::setTenant($project);

        $recordRef = 'channeled:google_ads:ad-groups:55';

        $engineMock = Mockery::mock(RemoteEngineService::class);
        $engineMock->shouldReceive('getCatalogEntityProfile')
            ->once()
            ->withArgs(fn (Project $p, string $entity, string $ref) => $p->id === $project->id && $entity === 'ad-groups' && $ref === $recordRef)
            ->andReturn([
                'status' => 'success',
                'data'   => [
                    'record' => [
                        'reference'     => $recordRef,
                        'layer'         => 'channeled',
                        'channel'       => 'google_ads',
                        'channel_label' => 'Google Ads',
                        'entity_type'   => 'ad-groups',
                        'id'            => 55,
                        'platform_id'   => 'gads_ag_55',
                        'name'          => 'Brand Exact Match Ad Group',
                        'status'        => 'ENABLED',
                        'attributes'    => [
                            'optimization_goal' => 'SEARCH',
                            'billing_event'     => 'CPC',
                            'start_date'        => '2026-01-10',
                        ],
                        'provenance'    => [
                            'channel'             => 'google_ads',
                            'channel_label'       => 'Google Ads',
                            'platform_id'         => 'gads_ag_55',
                            'freshness_timestamp' => '2026-10-01 12:00:00',
                        ],
                    ],
                    'source_records' => [
                        [
                            'layer'       => 'channeled',
                            'table'       => 'channeled_ad_groups',
                            'id'          => 55,
                            'platform_id' => 'gads_ag_55',
                        ],
                    ],
                    'relationships' => [
                        'parents' => [
                            'account'  => [
                                'reference'   => 'channeled:google_ads:accounts:2',
                                'entity_type' => 'accounts',
                                'name'        => 'Google Search US',
                                'platform_id' => 'gads_201',
                            ],
                            'campaign' => [
                                'reference'   => 'channeled:google_ads:campaigns:20',
                                'entity_type' => 'campaigns',
                                'name'        => 'US Brand Search Campaign',
                                'platform_id' => 'gads_camp_20',
                            ],
                        ],
                        'children' => [
                            'ads' => [
                                'entity_type' => 'ads',
                                'label'       => 'Ads',
                                'total_count' => 4,
                                'items'       => [
                                    [
                                        'reference'   => 'channeled:google_ads:ads:99',
                                        'entity_type' => 'ads',
                                        'name'        => 'Responsive Search Ad #1',
                                        'platform_id' => 'gads_ad_99',
                                        'status'      => 'ENABLED',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'metrics' => [
                        'grain'      => 'ad_group',
                        'currency'   => 'USD',
                        'date_range' => [
                            'start_date' => '2026-09-01',
                            'end_date'   => '2026-09-30',
                        ],
                        'coverage'   => [
                            'state'          => 'complete',
                            'has_data'       => true,
                            'observed_days'  => 30,
                            'requested_days' => 30,
                        ],
                        'summary'    => [
                            'spend'         => 400.0,
                            'impressions'   => 10000,
                            'clicks'        => 800,
                            'conversions'   => 40.0,
                            'revenue'       => 2400.0,
                            'ctr'           => 0.08,
                            'cpc'           => 0.5,
                            'cpm'           => 40.0,
                            'roas_purchase' => 6.0,
                        ],
                        'series'     => [
                            '2026-09-15' => [
                                'spend'         => 400.0,
                                'impressions'   => 10000,
                                'clicks'        => 800,
                                'conversions'   => 40.0,
                                'ctr'           => 0.08,
                                'cpc'           => 0.5,
                                'roas_purchase' => 6.0,
                            ],
                        ],
                    ],
                ],
            ]);

        $engineMock->shouldReceive('getCatalogEntityActivity')
            ->once()
            ->andReturn([
                'status' => 'success',
                'data'   => [
                    'coverage' => [
                        'notice' => 'Current-state and sync milestone activity (field-level mutation audit history is not stored in Phase 1).',
                    ],
                    'data'     => [
                        [
                            'id'            => 'sync_milestone_1',
                            'kind'          => 'synced',
                            'label'         => 'Latest Catalog Metadata Sync',
                            'description'   => 'Entity metadata refreshed from Google Ads.',
                            'occurred_at'   => '2026-10-01 12:00:00',
                            'quality'       => 'sync_snapshot',
                            'quality_label' => 'Sync Snapshot',
                        ],
                    ],
                    'meta'     => [
                        'next_cursor' => null,
                        'has_more'    => false,
                        'total'       => 1,
                    ],
                ],
            ]);

        $this->app->instance(RemoteEngineService::class, $engineMock);

        Livewire::test(CatalogEntity::class, [
            'entity' => 'ad-groups',
            'record' => $recordRef,
        ])
            ->assertSuccessful()
            ->assertSee('Brand Exact Match Ad Group')
            ->assertSee('US Brand Search Campaign')
            ->assertSee('Google Search US')
            ->assertSee('Responsive Search Ad #1')
            ->assertSee('Latest Catalog Metadata Sync')
            ->assertSee('USD 400.00');
    }

    public function test_restricted_collaborator_blocks_unauthorized_scope_and_customer_pii(): void
    {
        $owner = User::factory()->create();
        $project = $this->createAdsProject($owner);

        $groupA = $this->createAssetGroup($project, 'Allowed Group', [
            'facebook_marketing' => ['act_101'],
        ]);
        $groupB = $this->createAssetGroup($project, 'Forbidden Group', [
            'facebook_marketing' => ['act_102'],
        ]);

        $collaborator = User::factory()->create();
        $collaborator->givePermissionTo('view_data');
        $this->attachMember($collaborator, $project, 'project_user', false, [$groupA->id]);

        $this->actingAs($collaborator);
        Filament::setTenant($project);

        $engineMock = Mockery::mock(RemoteEngineService::class);
        $engineMock->shouldNotReceive('listCatalogEntities');
        $engineMock->shouldNotReceive('getCatalogEntityProfile');
        $this->app->instance(RemoteEngineService::class, $engineMock);

        // 1. Restricted collaborator visiting /catalog/customers is blocked by PII guard without remote call
        Livewire::test(CatalogEntity::class, ['entity' => 'customers'])
            ->assertSuccessful()
            ->assertSet('stateCode', 'pii_restricted')
            ->assertSee('Customer PII Access Restricted');

        // 2. Restricted collaborator selecting unauthorized Asset Group B in list mode gets unauthorized_scope state
        Livewire::withQueryParams(['scope' => (string) $groupB->id])
            ->test(CatalogEntity::class, ['entity' => 'campaigns'])
            ->assertSuccessful()
            ->assertSet('stateCode', 'unauthorized_scope')
            ->assertSee('Unauthorized Asset Group Scope');

        // 3. Restricted collaborator requesting detail page with unauthorized Asset Group B gets 404
        Livewire::withQueryParams(['scope' => (string) $groupB->id])
            ->test(CatalogEntity::class, [
                'entity' => 'campaigns',
                'record' => 'channeled:facebook_marketing:campaigns:10',
            ])
            ->assertNotFound();
    }

    public function test_deferred_phase_entities_and_remote_errors_render_clean_banners(): void
    {
        $owner = User::factory()->create();
        $owner->givePermissionTo('view_data');
        $project = $this->createAdsProject($owner);

        $this->actingAs($owner);
        Filament::setTenant($project);

        // Deferred Phase 4 entity (orders)
        Livewire::test(CatalogEntity::class, ['entity' => 'orders'])
            ->assertSuccessful()
            ->assertSet('stateCode', 'phase_deferred')
            ->assertSee('Scheduled for Upcoming Rollout Phase');

        // Remote tenant_upgrading state on Phase 1 entity
        $engineMock = Mockery::mock(RemoteEngineService::class);
        $engineMock->shouldReceive('listCatalogEntities')
            ->once()
            ->andReturn([
                'status'     => 'error',
                'error_code' => 'tenant_upgrading',
                'message'    => 'Your dedicated engine is applying an update.',
            ]);
        $this->app->instance(RemoteEngineService::class, $engineMock);

        Livewire::test(CatalogEntity::class, ['entity' => 'accounts'])
            ->assertSuccessful()
            ->assertSet('stateCode', 'tenant_upgrading')
            ->assertSee('Tenant Upgrade in Progress')
            ->assertSee('Your dedicated engine is applying an update.');
    }

    public function test_phase_2_entities_queries_search_type_customers_and_link_renderer(): void
    {
        $owner = User::factory()->create();
        $owner->givePermissionTo('view_data');

        $project = $this->createAdsProject($owner, [
            'facebook_marketing' => [
                'ad_accounts' => [
                    ['id' => 'act_101', 'enabled' => true, 'lost_access' => false],
                ],
            ],
            'facebook_organic' => [
                'pages' => [
                    ['id' => 'fb_page_1', 'ig_account' => 'ig_900', 'enabled' => true, 'lost_access' => false],
                ],
            ],
            'google_search_console' => [
                'sites' => [
                    ['id' => 'https://example.com/', 'enabled' => true, 'lost_access' => false],
                ],
            ],
            'google_analytics' => [
                'properties' => [
                    ['id' => 'properties/444', 'enabled' => true, 'lost_access' => false],
                ],
            ],
            'mailchimp' => [
                'audiences' => [
                    ['id' => 'mc_list_1', 'enabled' => true, 'lost_access' => false],
                ],
            ],
        ]);

        $this->actingAs($owner);
        Filament::setTenant($project);

        // 1. Verify CatalogScopeResolver expands linked IG account and derives facebook_leads
        /** @var CatalogScopeResolver $resolver */
        $resolver = app(CatalogScopeResolver::class);
        $scope = $resolver->resolveScope($project, $owner, 'all');
        $this->assertContains('fb_page_1', $scope['channels']['facebook_organic']['platform_ids']);
        $this->assertContains('ig_900', $scope['channels']['facebook_organic']['platform_ids']);
        $this->assertArrayHasKey('facebook_leads', $scope['channels']);
        $this->assertSame(['mc_list_1'], $scope['channels']['mailchimp']['platform_ids']);

        // 2. Verify CatalogEntity Queries list passes search_type filter
        $capturedQueryPayload = null;
        $engineMock = Mockery::mock(RemoteEngineService::class);
        $engineMock->shouldReceive('listCatalogEntities')
            ->once()
            ->withArgs(function (Project $p, string $entity, array $payload) use (&$capturedQueryPayload) {
                $capturedQueryPayload = $payload;

                return $entity === 'queries';
            })
            ->andReturn([
                'status' => 'success',
                'data'   => [
                    [
                        'reference'     => 'base:google_search_console:queries:77',
                        'layer'         => 'base',
                        'channel'       => 'google_search_console',
                        'channel_label' => 'Google Search Console',
                        'entity_type'   => 'queries',
                        'id'            => 77,
                        'platform_id'   => 'brand shoes',
                        'name'          => 'brand shoes',
                        'status'        => 'ACTIVE',
                        'attributes'    => [
                            'query'               => 'brand shoes',
                            'search_types'        => ['image'],
                            'metric_config_count' => 14,
                        ],
                        'relationships' => [
                            'account' => [
                                'reference'   => 'channeled:google_search_console:accounts:5',
                                'entity_type' => 'accounts',
                                'name'        => 'example.com',
                                'platform_id' => 'https://example.com/',
                            ],
                        ],
                        'provenance'    => [
                            'freshness_timestamp' => '2026-10-05 10:00:00',
                        ],
                    ],
                ],
                'meta' => ['total' => 1, 'page' => 1, 'per_page' => 25, 'last_page' => 1],
            ]);

        // 3. Verify Customers list works for authorized owner with PII access
        $engineMock->shouldReceive('listCatalogEntities')
            ->once()
            ->withArgs(fn (Project $p, string $entity) => $entity === 'customers')
            ->andReturn([
                'status' => 'success',
                'data'   => [
                    [
                        'reference'     => 'base:mailchimp:customers:12',
                        'layer'         => 'base',
                        'channel'       => 'mailchimp',
                        'channel_label' => 'Mailchimp',
                        'entity_type'   => 'customers',
                        'id'            => 12,
                        'platform_id'   => 'alice@example.com',
                        'name'          => 'Alice Rivera',
                        'status'        => 'SUBSCRIBED',
                        'attributes'    => [
                            'email'               => 'alice@example.com',
                            'country_code'        => 'US',
                            'vip'                 => true,
                            'source_record_count' => 2,
                        ],
                        'relationships' => [],
                        'provenance'    => [
                            'freshness_timestamp' => '2026-10-05 11:00:00',
                        ],
                    ],
                ],
                'meta' => ['total' => 1, 'page' => 1, 'per_page' => 25, 'last_page' => 1],
            ]);

        $this->app->instance(RemoteEngineService::class, $engineMock);

        Livewire::withQueryParams(['search_type' => 'image'])
            ->test(CatalogEntity::class, ['entity' => 'queries'])
            ->assertSuccessful()
            ->assertSet('searchType', 'image')
            ->assertSee('brand shoes')
            ->assertSee('example.com');

        $this->assertSame('image', $capturedQueryPayload['filters']['search_type'] ?? null);

        Livewire::test(CatalogEntity::class, ['entity' => 'customers'])
            ->assertSuccessful()
            ->assertSee('Alice Rivera')
            ->assertSee('alice@example.com')
            ->assertSee('VIP');

        // 4. Verify CatalogLinkRenderer behavior across verified rows, search fallbacks, and unverified GA4 labels
        /** @var CatalogLinkRenderer $linkRenderer */
        $linkRenderer = app(CatalogLinkRenderer::class);

        $fbCampUrl = $linkRenderer->forExplorerRow($project, 'facebook_marketing', 'campaigns', [
            'channeledcampaign_id' => 10,
            'name'                 => 'Summer Sale',
        ]);
        $this->assertNotNull($fbCampUrl);
        $this->assertStringContainsString('/catalog/campaigns/channeled:facebook_marketing:campaigns:10', $fbCampUrl);

        // Totals / Unknown rows never link
        $this->assertNull($linkRenderer->forExplorerRow($project, 'facebook_marketing', 'campaigns', [
            'name'     => 'Total',
            'is_total' => true,
        ]));
        $this->assertNull($linkRenderer->forExplorerRow($project, 'google_search_console', 'queries', [
            'name' => '(not set)',
        ]));

        // GA4 sessionCampaignName without numeric channeledcampaign_id never links
        $this->assertNull($linkRenderer->forExplorerRow($project, 'google_analytics', 'campaigns', [
            'id'   => 'summer_promo_utm',
            'name' => 'summer_promo_utm',
        ]));

        // GA4 events and GSC queries link to scoped Catalog search when grouped by natural key
        $ga4EventUrl = $linkRenderer->forExplorerRow($project, 'google_analytics', 'events', [
            'name' => 'purchase',
        ]);
        $this->assertNotNull($ga4EventUrl);
        $this->assertStringContainsString('/catalog/events', $ga4EventUrl);
        $this->assertStringContainsString('channel=google_analytics', $ga4EventUrl);
        $this->assertStringContainsString('search=purchase', $ga4EventUrl);
    }

    public function test_marketing_overview_renders_domains_deduplicates_assets_and_respects_pii_guard(): void
    {
        $owner = User::factory()->create();
        $owner->givePermissionTo('view_data');

        $project = $this->createAdsProject($owner, [
            'facebook_marketing' => [
                'ad_accounts' => [
                    ['id' => 'act_101', 'enabled' => true, 'lost_access' => false],
                ],
            ],
            'google_search_console' => [
                'sites' => [
                    ['id' => 'https://example.com/', 'enabled' => true, 'lost_access' => false],
                ],
            ],
            'mailchimp' => [
                'audiences' => [
                    ['id' => 'mc_list_1', 'enabled' => true, 'lost_access' => false],
                ],
            ],
        ]);

        $this->actingAs($owner);
        Filament::setTenant($project);

        $engineMock = Mockery::mock(RemoteEngineService::class);
        $engineMock->shouldReceive('getCatalogCapabilities')
            ->andReturn([
                'status' => 'success',
                'data'   => [
                    'contract_version' => '1.0',
                    'entity_measures'  => ['supported' => false],
                ],
            ]);

        $engineMock->shouldReceive('listCatalogEntities')
            ->andReturnUsing(function (Project $p, string $entity) {
                return [
                    'status' => 'success',
                    'data'   => [
                        [
                            'reference'     => "channeled:facebook_marketing:{$entity}:1",
                            'channel'       => 'facebook_marketing',
                            'channel_label' => 'Meta Ads',
                            'entity_type'   => $entity,
                            'id'            => 1,
                            'platform_id'   => 'pid_1',
                            'name'          => 'Sample ' . ucfirst($entity),
                            'status'        => 'ACTIVE',
                        ],
                    ],
                    'meta' => ['total' => 3, 'page' => 1, 'per_page' => 3, 'last_page' => 1],
                ];
            });

        $this->app->instance(RemoteEngineService::class, $engineMock);

        Livewire::test(MarketingOverview::class)
            ->assertSuccessful()
            ->assertSee('Marketing Overview')
            ->assertSee('Paid Advertising')
            ->assertSee('Organic & Social Content')
            ->assertSee('Search Visibility')
            ->assertSee('Email & Lifecycle Events')
            ->assertSee('Customers & Leads')
            ->assertSee('Sample Campaigns');
    }

    public function test_apis_hub_release_v1190_seeder_is_idempotent_and_sets_default(): void
    {
        $this->seed(ApisHubReleaseV1184Seeder::class);
        $v1184 = ApisHubRelease::where('version_tag', 'v1.18.4')->firstOrFail();
        $this->assertTrue((bool) $v1184->is_default);

        // Run v1.19.0 seeder twice to verify idempotency
        $this->seed(ApisHubReleaseV1190Seeder::class);
        $this->seed(ApisHubReleaseV1190Seeder::class);

        $v1190 = ApisHubRelease::where('version_tag', 'v1.19.0')->firstOrFail();
        $this->assertTrue((bool) $v1190->is_active);
        $this->assertTrue((bool) $v1190->is_default);
        $this->assertSame([], $v1190->upgrade_commands);
        $this->assertSame($v1184->supported_channels, $v1190->supported_channels);
        $this->assertFalse((bool) $v1184->fresh()->is_default);
        $this->assertSame(1, ApisHubRelease::where('is_default', true)->count());
    }
}
