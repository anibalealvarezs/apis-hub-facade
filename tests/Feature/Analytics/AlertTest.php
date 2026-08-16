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

    public function test_relation_manager_resolves_dot_notation_asset_platform_id_data()
    {
        $alert = Alert::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'name' => 'Dot Notation Test Alert',
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
            'label' => 'initial.com',
            'asset_filter' => ['asset_platform_id' => 'https://initial.com'],
        ]);

        $this->actingAs($this->user);
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('app'));
        \Filament\Facades\Filament::setTenant($this->project);

        \Livewire\Livewire::test(\App\Filament\App\Resources\AlertResource\RelationManagers\CalculationLinesRelationManager::class, [
            'ownerRecord' => $alert,
            'pageClass' => \App\Filament\App\Resources\AlertResource\Pages\EditAlert::class,
        ])
            ->callTableAction('edit', $line, [
                'target_asset_platform_id' => 'https://marcelacrodriguez.com',
                'label' => 'marcelacrodriguez.com',
            ])
            ->assertHasNoTableActionErrors();

        $line->refresh();
        $this->assertEquals('https://marcelacrodriguez.com', $line->asset_filter['asset_platform_id'] ?? null);
        $this->assertEquals('marcelacrodriguez.com', $line->label);
    }

    public function test_create_alert_page_saves_specific_target_asset_platform_id()
    {
        $data = [
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'name' => 'Main Form Asset Alert',
            'source_type' => 'metric',
            'source_config' => ['channel' => 'meta', 'metric' => 'spend'],
            'aggregation_method' => 'latest',
            'upper_limit' => 1000,
            'schedule_type' => 'daily',
            'schedule_config' => ['time' => '08:00'],
            'calculationLines' => [
                [
                    'target_asset_platform_id' => 'act_999999',
                    'label' => 'Account #999999',
                ],
            ],
        ];

        $createAlertPage = new \App\Filament\App\Resources\AlertResource\Pages\CreateAlert();
        $reflector = new \ReflectionClass($createAlertPage);
        $method = $reflector->getMethod('mutateFormDataBeforeCreate');
        $method->setAccessible(true);

        $this->actingAs($this->user);
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('app'));
        \Filament\Facades\Filament::setTenant($this->project);

        $mutatedData = $method->invoke($createAlertPage, $data);

        $calculationLinesData = $mutatedData['calculationLines'] ?? [];
        unset($mutatedData['calculationLines']);

        $alert = Alert::create($mutatedData);
        foreach ($calculationLinesData as $lineData) {
            $alert->calculationLines()->create($lineData);
        }

        $line = $alert->calculationLines->first();
        $this->assertNotNull($line);
        $this->assertEquals('act_999999', $line->asset_filter['asset_platform_id'] ?? null);
        $this->assertEquals('Account #999999', $line->label);
    }

    public function test_alert_can_be_duplicated_with_disabled_status()
    {
        $originalAlert = Alert::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'name' => 'Original Active Alert',
            'source_type' => 'metric',
            'source_config' => ['channel' => 'meta', 'metric' => 'spend'],
            'ast' => ['type' => 'metric', 'metric' => 'meta.spend'],
            'aggregation_method' => 'latest',
            'upper_limit' => 500,
            'schedule_type' => 'daily',
            'schedule_config' => ['time' => '08:00'],
            'is_active' => true,
        ]);

        AlertCalculationLine::create([
            'alert_id' => $originalAlert->id,
            'label' => 'Line 1',
            'asset_filter' => ['asset_platform_id' => 'act_111'],
        ]);

        $replica = $originalAlert->replicate();
        $replica->is_active = false;
        $replica->name = $originalAlert->name . ' (Copy)';
        $replica->save();

        foreach ($originalAlert->calculationLines as $line) {
            $replica->calculationLines()->create([
                'label' => $line->label,
                'asset_filter' => $line->asset_filter,
                'sort_order' => $line->sort_order,
            ]);
        }

        $this->assertFalse($replica->is_active);
        $this->assertEquals('Original Active Alert (Copy)', $replica->name);
        $this->assertCount(1, $replica->calculationLines);
        $this->assertEquals('act_111', $replica->calculationLines->first()->asset_filter['asset_platform_id'] ?? null);
    }

    public function test_alert_percentage_unit_converts_to_float_on_create_and_fill()
    {
        $rawFormData = [
            'name' => 'CTR Alert',
            'source_type' => 'metric',
            'source_config' => ['channel' => 'meta', 'metric' => 'ctr'],
            'unit' => 'percentage',
            'upper_limit' => 1.2, // 1.2%
            'lower_limit' => 0.5, // 0.5%
            'schedule_type' => 'daily',
            'schedule_config' => ['time' => '08:00'],
            'aggregation_method' => 'latest',
        ];

        // Simulate CreateAlert normalization
        if ($rawFormData['unit'] === 'percentage') {
            $rawFormData['upper_limit'] = (float) $rawFormData['upper_limit'] / 100;
            $rawFormData['lower_limit'] = (float) $rawFormData['lower_limit'] / 100;
        }

        $rawFormData['project_id'] = $this->project->id;
        $rawFormData['user_id'] = $this->user->id;
        $rawFormData['ast'] = ['type' => 'metric', 'metric' => 'meta.ctr'];

        $alert = Alert::create($rawFormData);

        $this->assertEquals('percentage', $alert->unit);
        $this->assertEquals(0.012, (float) $alert->upper_limit);
        $this->assertEquals(0.005, (float) $alert->lower_limit);

        // Simulate EditAlert fill conversion for form display
        $filledData = $alert->toArray();
        if ($filledData['unit'] === 'percentage') {
            $filledData['upper_limit'] = (float) $filledData['upper_limit'] * 100;
            $filledData['lower_limit'] = (float) $filledData['lower_limit'] * 100;
        }

        $this->assertEquals(1.2, (float) $filledData['upper_limit']);
        $this->assertEquals(0.5, (float) $filledData['lower_limit']);
    }

    public function test_alert_service_formatting_retains_min_significant_decimals_up_to_four()
    {
        $service = app(\App\Services\AlertService::class);

        // Standard numbers
        $this->assertEquals('1.1', $service->formatMetricValue(1.1000, 'number'));
        $this->assertEquals('1.1001', $service->formatMetricValue(1.1001, 'number'));
        $this->assertEquals('5', $service->formatMetricValue(5.0000, 'number'));
        $this->assertEquals('1,250.5', $service->formatMetricValue(1250.5000, 'number'));

        // Percentage
        $this->assertEquals('1.2%', $service->formatMetricValue(0.012, 'percentage'));
        $this->assertEquals('2.2222%', $service->formatMetricValue(0.022222222, 'percentage'));
        $this->assertEquals('0.5%', $service->formatMetricValue(0.005, 'percentage'));
        $this->assertEquals('100%', $service->formatMetricValue(1.0, 'percentage'));

        // Currency
        $this->assertEquals('$1,250', $service->formatMetricValue(1250.00, 'currency'));
        $this->assertEquals('$1,250.5', $service->formatMetricValue(1250.50, 'currency'));
        $this->assertEquals('$1,250.5555', $service->formatMetricValue(1250.5555, 'currency'));
    }

    public function test_alert_service_indicator_sentiment_and_arrow_direction()
    {
        $service = app(\App\Services\AlertService::class);

        // 1. CTR (Higher is better)
        $ctrAlert = Alert::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'name' => 'CTR Alert',
            'source_type' => 'metric',
            'source_config' => ['channel' => 'meta', 'metric' => 'ctr'],
            'ast' => ['type' => 'metric', 'metric' => 'meta.ctr'],
            'unit' => 'percentage',
            'upper_limit' => 0.012,
            'lower_limit' => 0.005,
            'aggregation_method' => 'latest',
            'schedule_type' => 'daily',
            'schedule_config' => ['time' => '08:00'],
        ]);

        // CTR exceeded upper limit -> ⬆️ Up, 🟢 Green (Good!)
        $ctrUpper = $service->getAlertVisualIndicators($ctrAlert, 'upper', 'percentage', 0.022222, 0.012);
        $this->assertTrue($ctrUpper['is_good']);
        $this->assertEquals('up', $ctrUpper['direction']);
        $this->assertEquals('⬆️', $ctrUpper['arrow']);
        $this->assertEquals('🟢', $ctrUpper['badge_emoji']);
        $this->assertEquals('#10b981', $ctrUpper['color']);
        $this->assertEquals('2.2222%', $ctrUpper['formatted_evaluated']);
        $this->assertEquals('1.2%', $ctrUpper['formatted_threshold']);

        // CTR breached lower limit -> ⬇️ Down, 🔴 Red (Bad!)
        $ctrLower = $service->getAlertVisualIndicators($ctrAlert, 'lower', 'percentage', 0.002, 0.005);
        $this->assertFalse($ctrLower['is_good']);
        $this->assertEquals('down', $ctrLower['direction']);
        $this->assertEquals('⬇️', $ctrLower['arrow']);
        $this->assertEquals('🔴', $ctrLower['badge_emoji']);
        $this->assertEquals('#ef4444', $ctrLower['color']);

        // 2. Spend / Cost (Lower is better)
        $spendAlert = Alert::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'name' => 'Spend Alert',
            'source_type' => 'metric',
            'source_config' => ['channel' => 'meta', 'metric' => 'spend'],
            'ast' => ['type' => 'metric', 'metric' => 'meta.spend'],
            'unit' => 'currency',
            'upper_limit' => 1000,
            'lower_limit' => 200,
            'aggregation_method' => 'latest',
            'schedule_type' => 'daily',
            'schedule_config' => ['time' => '08:00'],
        ]);

        // Spend exceeded upper limit -> ⬆️ Up, 🔴 Red (Bad / High Spend Warning!)
        $spendUpper = $service->getAlertVisualIndicators($spendAlert, 'upper', 'currency', 1250.5, 1000);
        $this->assertFalse($spendUpper['is_good']);
        $this->assertEquals('up', $spendUpper['direction']);
        $this->assertEquals('⬆️', $spendUpper['arrow']);
        $this->assertEquals('🔴', $spendUpper['badge_emoji']);
        $this->assertEquals('#ef4444', $spendUpper['color']);
        $this->assertEquals('$1,250.5', $spendUpper['formatted_evaluated']);
        $this->assertEquals('$1,000', $spendUpper['formatted_threshold']);

        // Spend dropped below lower limit -> ⬇️ Down, 🟢 Green (Good / Cost Savings!)
        $spendLower = $service->getAlertVisualIndicators($spendAlert, 'lower', 'currency', 150, 200);
        $this->assertTrue($spendLower['is_good']);
        $this->assertEquals('down', $spendLower['direction']);
        $this->assertEquals('⬇️', $spendLower['arrow']);
        $this->assertEquals('🟢', $spendLower['badge_emoji']);
        $this->assertEquals('#10b981', $spendLower['color']);
    }
}






