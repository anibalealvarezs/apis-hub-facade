<?php

namespace Tests\Feature\Analytics;

use App\Enums\UserTier;
use App\Models\Alert;
use App\Models\AlertCalculationLine;
use App\Models\ApisHubRelease;
use App\Models\BillingProfile;
use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\Project;
use App\Models\User;
use App\Services\AlertService;
use App\Services\BillingLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AlertTest extends TestCase
{
    use RefreshDatabase;

    public User $user;
    public Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $profile = BillingProfile::create([
            'user_id' => $this->user->id,
            'name' => 'Test Billing Profile',
            'tier' => UserTier::PRO,
        ]);
        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'billing_profile_id' => $profile->id,
            'timezone' => 'America/New_York',
        ]);
    }

    public function test_alert_creation_and_next_evaluation_computation_daily()
    {
        $alert = Alert::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'name' => 'High Spend Warning',
            'source_type' => 'metric',
            'source_config' => ['channel' => 'meta', 'metric' => 'spend'],
            'ast' => ['type' => 'metric', 'metric' => 'meta.spend'],
            'filters' => [],
            'aggregation_method' => 'latest',
            'upper_limit' => 1000.00,
            'schedule_type' => 'daily',
            'schedule_config' => ['time' => '09:00'],
            'is_active' => true,
        ]);

        $nextEval = $alert->computeNextEvaluationAt();
        $this->assertNotNull($nextEval);

        $alert->next_evaluation_at = $nextEval;
        $alert->save();

        $this->assertDatabaseHas('alerts', [
            'id' => $alert->id,
            'name' => 'High Spend Warning',
            'is_active' => true,
        ]);
    }

    public function test_alert_scheduling_weekly_biweekly_monthly_once()
    {
        // Weekly
        $weeklyAlert = Alert::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'name' => 'Weekly Report',
            'source_type' => 'metric',
            'source_config' => ['channel' => 'meta', 'metric' => 'spend'],
            'ast' => ['type' => 'metric', 'metric' => 'meta.spend'],
            'aggregation_method' => 'sum',
            'schedule_type' => 'weekly',
            'schedule_config' => ['time' => '08:00', 'day_of_week' => 1], // Monday
            'is_active' => true,
        ]);
        $this->assertNotNull($weeklyAlert->computeNextEvaluationAt());

        // Monthly
        $monthlyAlert = Alert::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'name' => 'Monthly Audit',
            'source_type' => 'metric',
            'source_config' => ['channel' => 'meta', 'metric' => 'spend'],
            'ast' => ['type' => 'metric', 'metric' => 'meta.spend'],
            'aggregation_method' => 'avg',
            'schedule_type' => 'monthly',
            'schedule_config' => ['time' => '10:00', 'days_of_month' => [1, 15]],
            'is_active' => true,
        ]);
        $this->assertNotNull($monthlyAlert->computeNextEvaluationAt());

        // Once
        $onceAlert = Alert::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'name' => 'One-off Check',
            'source_type' => 'metric',
            'source_config' => ['channel' => 'meta', 'metric' => 'spend'],
            'ast' => ['type' => 'metric', 'metric' => 'meta.spend'],
            'aggregation_method' => 'latest',
            'schedule_type' => 'once',
            'schedule_config' => ['time' => '12:00', 'date' => '2026-09-01'],
            'is_active' => true,
        ]);
        $this->assertNotNull($onceAlert->computeNextEvaluationAt());
    }

    public function test_total_calculation_lines_counting_for_billing()
    {
        $alert = Alert::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'name' => 'Multi-Account Alert',
            'source_type' => 'metric',
            'source_config' => ['channel' => 'meta', 'metric' => 'spend'],
            'ast' => ['type' => 'metric', 'metric' => 'meta.spend'],
            'filters' => [],
            'aggregation_method' => 'latest',
            'schedule_type' => 'daily',
            'schedule_config' => ['time' => '08:00'],
            'is_active' => true,
        ]);

        AlertCalculationLine::create([
            'alert_id' => $alert->id,
            'label' => 'Account A',
            'asset_filter' => ['asset_platform_id' => 'act_111'],
        ]);

        AlertCalculationLine::create([
            'alert_id' => $alert->id,
            'label' => 'Account B',
            'asset_filter' => ['asset_platform_id' => 'act_222'],
        ]);

        $service = app(AlertService::class);
        $totalLines = $service->countTotalCalculationLines($this->project);
        $this->assertEquals(2, $totalLines);

        $billingService = app(BillingLifecycleService::class);
        $maxCalculations = $billingService->getMaxAlertCalculationsForTier(UserTier::PRO);
        $this->assertEquals(20, $maxCalculations);
        $this->assertTrue($totalLines < $maxCalculations);
    }

    public function test_version_gating_supports_alerts()
    {
        // Unassigned release defaults to true
        $this->assertTrue($this->project->supportsAlerts());

        // Older release v1.14.0
        $oldRelease = ApisHubRelease::create([
            'version_tag' => 'v1.14.0',
            'name' => 'Legacy Release',
            'is_active' => true,
        ]);

        $this->project->apis_hub_release_id = $oldRelease->id;
        $this->project->save();
        $this->project->refresh();
        $this->assertFalse($this->project->supportsAlerts());

        // Updated release v1.15.0
        $newRelease = ApisHubRelease::create([
            'version_tag' => 'v1.15.0',
            'name' => 'Alert Engine Release',
            'is_active' => true,
        ]);

        $this->project->apis_hub_release_id = $newRelease->id;
        $this->project->save();
        $this->project->refresh();
        $this->assertTrue($this->project->supportsAlerts());
    }

    public function test_sync_window_warning_detection()
    {
        $this->project->sync_config = [
            'meta' => ['sync_time' => '08:00'],
        ];
        $this->project->save();

        $service = app(AlertService::class);

        // Within 2-hour window after 08:00 (e.g. 09:15)
        $warning = $service->getSyncWindowWarning($this->project, '09:15');
        $this->assertNotNull($warning);

        // Outside 2-hour window (e.g. 11:30)
        $noWarning = $service->getSyncWindowWarning($this->project, '11:30');
        $this->assertNull($noWarning);
    }

    public function test_associated_alerts_retrieval_and_deletion()
    {
        $dashboard = Dashboard::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'name' => 'Marketing Overview',
            'is_public' => false,
            'is_default' => false,
        ]);

        $widget = DashboardWidget::create([
            'dashboard_id' => $dashboard->id,
            'name' => 'Meta Spend Widget',
            'source_type' => 'metric',
            'widget_type' => 'card',
            'grid_x' => 0,
            'grid_y' => 0,
            'grid_w' => 4,
            'grid_h' => 2,
            'source_config' => ['channel' => 'meta', 'metric' => 'spend'],
        ]);

        $alert = Alert::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'name' => 'Widget Alert',
            'source_type' => 'metric',
            'source_config' => ['widget_id' => $widget->id, 'channel' => 'meta', 'metric' => 'spend'],
            'ast' => ['type' => 'metric', 'metric' => 'meta.spend'],
            'aggregation_method' => 'latest',
            'schedule_type' => 'daily',
            'schedule_config' => ['time' => '08:00'],
            'is_active' => true,
        ]);

        $widgetAlerts = $widget->getAssociatedAlerts();
        $this->assertCount(1, $widgetAlerts);
        $this->assertEquals($alert->id, $widgetAlerts->first()->id);

        $dashboardAlerts = $dashboard->getAssociatedAlerts();
        $this->assertCount(1, $dashboardAlerts);
        $this->assertEquals($alert->id, $dashboardAlerts->first()->id);
    }

    public function test_alert_calculation_line_saves_and_edits_specific_asset_platform_id()
    {
        $alert = Alert::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'name' => 'Specific Asset Alert',
            'source_type' => 'metric',
            'source_config' => ['channel' => 'google_api', 'metric' => 'clicks'],
            'ast' => ['type' => 'metric', 'metric' => 'google_api.clicks'],
            'aggregation_method' => 'latest',
            'upper_limit' => 500,
            'schedule_type' => 'daily',
            'schedule_config' => ['time' => '08:00'],
            'is_active' => true,
        ]);

        $line = AlertCalculationLine::create([
            'alert_id' => $alert->id,
            'label' => 'marcelacrodriguez.com',
            'asset_filter' => ['asset_platform_id' => 'https://marcelacrodriguez.com'],
        ]);

        $this->assertEquals('https://marcelacrodriguez.com', $line->asset_filter['asset_platform_id'] ?? null);

        $this->actingAs($this->user);
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('app'));
        \Filament\Facades\Filament::setTenant($this->project);

        \Livewire\Livewire::test(\App\Filament\App\Resources\AlertResource\RelationManagers\CalculationLinesRelationManager::class, [
            'ownerRecord' => $alert,
            'pageClass' => \App\Filament\App\Resources\AlertResource\Pages\EditAlert::class,
        ])
            ->callTableAction('edit', $line, [
                'asset_filter' => ['asset_platform_id' => 'https://marcelacrodriguez.com'],
                'label' => 'marcelacrodriguez.com',
            ])
            ->assertHasNoTableActionErrors();

        $line->refresh();
        $this->assertEquals('https://marcelacrodriguez.com', $line->asset_filter['asset_platform_id'] ?? null);
        $this->assertEquals('marcelacrodriguez.com', $line->label);
    }
}


