<?php

use App\Models\Alert;
use App\Models\AlertCalculationLine;
use App\Models\BillingProfile;
use App\Models\CustomKpi;
use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use App\Services\DeployerService;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->user = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john.doe@example.com',
        'password' => bcrypt('super-secret-password-123'),
    ]);

    $this->billingProfile = BillingProfile::create([
        'user_id' => $this->user->id,
        'name' => 'Pro Profile',
        'type' => 'company',
        'tier' => \App\Enums\UserTier::ULTRA,
        'status' => 'active',
        'is_default' => true,
    ]);

    $this->server = Server::factory()->create([
        'name' => 'Prod Server',
        'ip_address' => '192.168.1.100',
        'ssh_user' => 'root',
        'is_ready' => true,
    ]);

    $this->project = Project::factory()->create([
        'user_id' => $this->user->id,
        'server_id' => $this->server->id,
        'billing_profile_id' => $this->billingProfile->id,
        'name' => 'Acme Analytics',
        'subdomain' => 'acme-prod',
        'timezone' => 'America/New_York',
        'public_api_key' => 'tenant_master_super_secret_token_abc_123',
    ]);
});

test('buildSanitizedProjectContextPayload exports custom KPIs, dashboards, and alerts without leaking sensitive information', function () {
    // 1. Create a Custom KPI with internal UI state in filters
    $kpi = CustomKpi::create([
        'project_id' => $this->project->id,
        'name' => 'High Intent ROAS',
        'description' => 'Blended ROAS filtered by conversion intent',
        'calculation_type' => 'formula',
        'ast' => [
            'type' => 'binary_expression',
            'operator' => '/',
            'left' => ['type' => 'metric', 'name' => 'total_conversion_value'],
            'right' => ['type' => 'metric', 'name' => 'spend'],
        ],
        'filters' => [
            'campaign_type' => 'search',
            '_ui_state' => [
                'template_key' => 'roas_formula',
                'internal_dom_id' => 'kpi_form_row_12',
                'secret_debug_comment' => 'Do not expose this internal state',
            ],
        ],
        'is_active' => true,
    ]);

    // Inactive KPI should be ignored
    CustomKpi::create([
        'project_id' => $this->project->id,
        'name' => 'Draft Inactive Metric',
        'description' => 'Should not be in catalog',
        'calculation_type' => 'formula',
        'is_active' => false,
    ]);

    // 2. Create a Dashboard with Widgets
    $dashboard = Dashboard::create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'name' => ['en' => 'Executive Performance', 'es' => 'Rendimiento Ejecutivo'],
        'description' => ['en' => 'High level C-suite KPIs', 'es' => 'KPIs de alto nivel directivo'],
        'is_default' => true,
        'is_public' => false,
    ]);

    $widget = DashboardWidget::create([
        'dashboard_id' => $dashboard->id,
        'custom_kpi_id' => $kpi->id,
        'name' => ['en' => 'ROAS Overview', 'es' => 'Resumen de ROAS'],
        'title' => ['en' => 'Blended ROAS', 'es' => 'ROAS Combinado'],
        'description' => ['en' => 'Monthly aggregate', 'es' => 'Agregado mensual'],
        'widget_type' => 'metric_card',
        'source_type' => 'custom_kpi',
        'source_config' => ['kpi_id' => $kpi->id],
        'controls' => ['timeframe' => 'last_30_days'],
        'grid_x' => 0,
        'grid_y' => 0,
        'grid_w' => 4,
        'grid_h' => 2,
    ]);

    // 3. Create an Alert with Calculation Lines
    $alert = Alert::create([
        'project_id' => $this->project->id,
        'user_id' => $this->user->id,
        'name' => 'Critical Spend Threshold Spike',
        'description' => 'Triggers when daily spend exceeds 1500 USD',
        'source_type' => 'metric',
        'source_config' => ['metric' => 'spend'],
        'ast' => ['type' => 'metric', 'name' => 'spend'],
        'filters' => ['channel' => 'facebook_marketing'],
        'aggregation_method' => 'sum',
        'upper_limit' => 1500.0,
        'lower_limit' => null,
        'schedule_type' => 'hourly',
        'schedule_config' => ['minute' => 0],
        'is_active' => true,
    ]);

    AlertCalculationLine::create([
        'alert_id' => $alert->id,
        'label' => 'Main Facebook Ad Account',
        'asset_filter' => ['account_id' => 'act_999000111'],
        'sort_order' => 1,
    ]);

    // Act: build payload
    $deployer = app(DeployerService::class);
    $payload = $deployer->buildSanitizedProjectContextPayload($this->project);

    // Assert: Project metadata only has safe fields
    expect($payload)->toHaveKeys(['project', 'custom_kpis', 'dashboards', 'alerts', 'synced_at']);
    expect($payload['project'])->toBe([
        'id' => $this->project->id,
        'name' => 'Acme Analytics',
        'subdomain' => 'acme-prod',
        'timezone' => 'America/New_York',
    ]);

    // Assert: Custom KPIs are clean and stripped of _ui_state
    expect($payload['custom_kpis'])->toHaveCount(1);
    $exportedKpi = $payload['custom_kpis'][0];
    expect($exportedKpi['name'])->toBe('High Intent ROAS');
    expect($exportedKpi['filters'])->toBe(['campaign_type' => 'search']);
    expect($exportedKpi['filters'])->not->toHaveKey('_ui_state');

    // Assert: Dashboards and Widgets structure
    expect($payload['dashboards'])->toHaveCount(1);
    $exportedDashboard = $payload['dashboards'][0];
    expect($exportedDashboard['id'])->toBe($dashboard->id);
    expect($exportedDashboard['name'])->toBe(['en' => 'Executive Performance', 'es' => 'Rendimiento Ejecutivo']);
    expect($exportedDashboard['widgets_count'])->toBe(1);
    expect($exportedDashboard['widgets'][0]['title'])->toBe(['en' => 'Blended ROAS', 'es' => 'ROAS Combinado']);

    // Assert: Alerts structure
    expect($payload['alerts'])->toHaveCount(1);
    $exportedAlert = $payload['alerts'][0];
    expect($exportedAlert['name'])->toBe('Critical Spend Threshold Spike');
    expect($exportedAlert['upper_limit'])->toBe(1500.0);
    expect($exportedAlert['calculation_lines'])->toHaveCount(1);
    expect($exportedAlert['calculation_lines'][0]['label'])->toBe('Main Facebook Ad Account');

    // CRITICAL SECURITY ASSERTION: No sensitive data anywhere in JSON string
    $json = json_encode($payload);
    expect($json)->not->toContain('super-secret-password-123');
    expect($json)->not->toContain('tenant_master_super_secret_token_abc_123');
    expect($json)->not->toContain('SECRETKEY');
    expect($json)->not->toContain('192.168.1.100');
    expect($json)->not->toContain('john.doe@example.com');
    expect($json)->not->toContain('Do not expose this internal state');
});
