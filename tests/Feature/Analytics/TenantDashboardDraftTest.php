<?php

namespace Tests\Feature\Analytics;

use App\Enums\UserTier;
use App\Models\BillingProfile;
use App\Models\Dashboard;
use App\Models\DashboardDraft;
use App\Models\DashboardWidget;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantDashboardDraftTest extends TestCase
{
    use RefreshDatabase;

    public Project $project;
    protected User $user;
    protected string $apiKey = 'test-tenant-key-12345';

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $profile = $this->user->billingProfiles()->first();

        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'billing_profile_id' => $profile?->id,
            'public_api_key' => $this->apiKey,
            'remote_admin_api_key' => $this->apiKey,
            'sync_config' => [
                'google_analytics' => ['enabled' => true],
                'facebook_marketing' => ['enabled' => true],
            ],
        ]);

        $this->project->users()->attach($this->user->id, ['role' => 'owner', 'api_key' => 'owner_key']);
    }

    public function test_it_returns_authoring_context_with_quotas(): void
    {
        $response = $this->withToken($this->apiKey)
            ->getJson('/api/internal/v1/tenant-dashboards/context');

        $response->assertOk()
            ->assertJsonStructure([
                'project' => ['id', 'name', 'subdomain', 'tier'],
                'channels',
                'asset_groups',
                'quotas' => [
                    'current_dashboards',
                    'max_dashboards',
                    'can_create_dashboard',
                    'max_widgets_per_dashboard',
                ],
            ]);

        $this->assertEquals(8, $response->json('quotas.max_widgets_per_dashboard'));
        $this->assertContains('google_analytics', $response->json('channels'));
        $this->assertContains('facebook_marketing', $response->json('channels'));
        $this->assertContains('entity_measure', $response->json('allowed_source_types'));
        $this->assertArrayHasKey('orders', $response->json('entity_measures.supported_entities'));
        $this->assertContains('aov', $response->json('entity_measures.supported_entities.orders.measures'));
        $this->assertEquals(1, $response->json('catalog_versions.google_analytics.contract_version'));
        $this->assertEquals(1, $response->json('catalog_versions.google_analytics.query_contract_version'));
        $this->assertEquals('1.18.3', $response->json('catalog_versions.google_search_console.minimum_release'));
        $this->assertArrayHasKey('google_analytics', $response->json('channel_metrics_catalog'));
        $this->assertArrayHasKey('facebook_marketing', $response->json('channel_metrics_catalog'));
    }

    public function test_it_enforces_widget_quota_on_draft_creation(): void
    {
        // Free tier allows up to 8 widgets. Let's send 9 widgets.
        $widgets = [];
        for ($i = 1; $i <= 9; $i++) {
            $widgets[] = [
                'name' => "Widget {$i}",
                'title' => "Widget Title {$i}",
                'widget_type' => 'tile',
                'source_type' => 'metric',
            ];
        }

        $payload = [
            'name' => 'SEO Quota Test Dashboard',
            'target_audience' => 'client',
            'account_mode' => 'asset_groups',
            'widgets' => $widgets,
        ];

        $response = $this->withToken($this->apiKey)
            ->postJson('/api/internal/v1/tenant-dashboards/drafts', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['widgets']);
    }

    public function test_it_creates_updates_and_commits_dashboard_draft_atomically(): void
    {
        $validWidgets = [
            [
                'name' => 'Organic Sessions',
                'title' => 'SEO Traffic',
                'widget_type' => 'tile',
                'source_type' => 'metric',
                'source_config' => ['metric' => 'sessions', 'channel' => 'google_analytics'],
                'grid_x' => 0,
                'grid_y' => 0,
                'grid_w' => 4,
                'grid_h' => 2,
            ],
            [
                'name' => 'Paid Cannibalization',
                'title' => 'Paid vs Organic Search',
                'widget_type' => 'line_chart',
                'source_type' => 'kpi',
                'source_config' => ['kpi_key' => 'paid_search_cannibalization'],
                'grid_x' => 4,
                'grid_y' => 0,
                'grid_w' => 8,
                'grid_h' => 4,
            ],
        ];

        // 1. Create draft
        $createRes = $this->withToken($this->apiKey)
            ->postJson('/api/internal/v1/tenant-dashboards/drafts', [
                'name' => 'Executive SEO & Ads Performance',
                'description' => 'Drafted via MCP Assistant',
                'target_audience' => 'executive',
                'account_mode' => 'asset_groups',
                'widgets' => $validWidgets,
            ]);

        $createRes->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'draft_token',
                'version',
                'preview_url',
                'expires_at',
            ]);

        $token = $createRes->json('draft_token');
        $this->assertNotEmpty($token);

        // Verify draft in database
        $this->assertDatabaseHas('dashboard_drafts', [
            'draft_token' => $token,
            'name' => 'Executive SEO & Ads Performance',
            'version' => 1,
        ]);

        // 2. Update draft
        $validWidgets[0]['title'] = 'Updated SEO Traffic';
        $updateRes = $this->withToken($this->apiKey)
            ->putJson("/api/internal/v1/tenant-dashboards/drafts/{$token}", [
                'name' => 'Executive SEO & Ads Performance (V2)',
                'widgets' => $validWidgets,
            ]);

        $updateRes->assertOk()
            ->assertJson([
                'status' => 'success',
                'version' => 2,
            ]);

        $this->assertDatabaseHas('dashboard_drafts', [
            'draft_token' => $token,
            'name' => 'Executive SEO & Ads Performance (V2)',
            'version' => 2,
        ]);

        // 3. Commit draft
        $commitRes = $this->withToken($this->apiKey)
            ->postJson("/api/internal/v1/tenant-dashboards/drafts/{$token}/commit");

        $commitRes->assertOk()
            ->assertJsonStructure([
                'status',
                'dashboard_id',
                'message',
            ]);

        $dashboardId = $commitRes->json('dashboard_id');

        // Draft record should be removed upon commit
        $this->assertDatabaseMissing('dashboard_drafts', [
            'draft_token' => $token,
        ]);

        // Production dashboard and widgets created
        $this->assertDatabaseHas('dashboards', [
            'id' => $dashboardId,
            'project_id' => $this->project->id,
        ]);

        $this->assertDatabaseCount('dashboard_widgets', 2);

        // 4. Read back dashboard spec
        $specRes = $this->withToken($this->apiKey)
            ->getJson("/api/internal/v1/tenant-dashboards/spec/{$dashboardId}");

        $specRes->assertOk()
            ->assertJsonPath('dashboard.id', $dashboardId);
        $this->assertCount(2, $specRes->json('dashboard.widgets'));
    }

    public function test_it_discards_a_draft(): void
    {
        $draft = DashboardDraft::create([
            'project_id' => $this->project->id,
            'draft_token' => 'drf_sample_discard_token',
            'name' => 'Discard Me',
            'widgets' => [],
            'expires_at' => now()->addHours(24),
        ]);

        $response = $this->withToken($this->apiKey)
            ->deleteJson("/api/internal/v1/tenant-dashboards/drafts/{$draft->draft_token}");

        $response->assertOk()
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseMissing('dashboard_drafts', [
            'draft_token' => 'drf_sample_discard_token',
        ]);
    }

    public function test_it_stages_and_commits_entity_measure_widgets_in_dashboard_draft(): void
    {
        $entityWidgets = [
            [
                'name' => 'Cross-Channel Canonical AOV',
                'title' => 'Average Order Value (AOV)',
                'widget_type' => 'tile',
                'source_type' => 'entity_measure',
                'source_config' => [
                    'entity_type' => 'orders',
                    'measures' => ['aov'],
                    'deduplicate_canonical' => true,
                    'filters' => ['currency' => 'USD'],
                ],
                'grid_x' => 0,
                'grid_y' => 0,
                'grid_w' => 4,
                'grid_h' => 2,
            ],
            [
                'name' => 'Orders Revenue vs Ad Spend',
                'title' => 'Canonical Orders Revenue vs Meta Spend',
                'widget_type' => 'line_chart',
                'source_type' => 'metric',
                'source_config' => [
                    'raw_series' => [
                        [
                            'type' => 'entity_measure',
                            'entity_type' => 'orders',
                            'measures' => ['revenue'],
                            'deduplicate_canonical' => true,
                            'label' => 'Canonical Orders Revenue',
                        ],
                        [
                            'type' => 'metric',
                            'channel' => 'facebook_marketing',
                            'metrics' => ['spend'],
                            'label' => 'Meta Ad Spend',
                        ],
                    ],
                ],
                'grid_x' => 4,
                'grid_y' => 0,
                'grid_w' => 8,
                'grid_h' => 4,
            ],
        ];

        $createRes = $this->withToken($this->apiKey)
            ->postJson('/api/internal/v1/tenant-dashboards/drafts', [
                'name' => 'Cross-Channel Commerce & Entity Measures Dashboard',
                'target_audience' => 'executive',
                'account_mode' => 'blended',
                'widgets' => $entityWidgets,
            ]);

        $createRes->assertStatus(201);
        $token = $createRes->json('draft_token');

        $commitRes = $this->withToken($this->apiKey)
            ->postJson("/api/internal/v1/tenant-dashboards/drafts/{$token}/commit");

        $commitRes->assertOk();
        $dashboardId = $commitRes->json('dashboard_id');

        $this->assertDatabaseHas('dashboard_widgets', [
            'dashboard_id' => $dashboardId,
            'source_type' => 'entity_measure',
            'widget_type' => 'tile',
        ]);

        $specRes = $this->withToken($this->apiKey)
            ->getJson("/api/internal/v1/tenant-dashboards/spec/{$dashboardId}");

        $specRes->assertOk()
            ->assertJsonPath('dashboard.widgets.0.source_type', 'entity_measure')
            ->assertJsonPath('dashboard.widgets.0.source_config.entity_type', 'orders')
            ->assertJsonPath('dashboard.widgets.0.source_config.measures.0', 'aov')
            ->assertJsonPath('dashboard.widgets.0.source_config.deduplicate_canonical', true)
            ->assertJsonPath('dashboard.widgets.1.source_config.raw_series.0.type', 'entity_measure');
    }
}
