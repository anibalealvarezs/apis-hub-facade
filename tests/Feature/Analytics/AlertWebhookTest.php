<?php

namespace Tests\Feature\Analytics;

use App\Enums\UserTier;
use App\Models\Alert;
use App\Models\AlertLog;
use App\Models\BillingProfile;
use App\Models\Project;
use App\Models\User;
use App\Notifications\AlertTriggeredNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AlertWebhookTest extends TestCase
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
            'name' => 'Test Profile',
            'tier' => UserTier::PRO,
        ]);
        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'billing_profile_id' => $profile->id,
            'monitoring_token' => 'test-monitoring-token-123',
        ]);
    }

    public function test_alert_triggered_webhook_creates_log_and_dispatches_notification()
    {
        Notification::fake();

        $alert = Alert::create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'name' => 'CPK Spike',
            'source_type' => 'metric',
            'source_config' => ['channel' => 'meta', 'metric' => 'cpc'],
            'ast' => ['type' => 'metric', 'metric' => 'meta.cpc'],
            'filters' => [],
            'aggregation_method' => 'latest',
            'upper_limit' => 5.00,
            'notify_ui' => true,
            'notify_email' => true,
            'schedule_type' => 'daily',
            'schedule_config' => ['time' => '10:00'],
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'X-Monitoring-Token' => $this->project->monitoring_token,
        ])->postJson('/api/alerts/triggered', [
            'alert_id' => $alert->id,
            'alert_name' => $alert->name,
            'source_type' => 'metric',
            'source_summary' => 'meta.cpc',
            'asset_summary' => 'Main Account',
            'evaluated_value' => 7.50,
            'threshold_type' => 'upper',
            'threshold_value' => 5.00,
            'status' => 'triggered',
            'triggered_at' => now()->toIso8601String(),
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('alert_logs', [
            'project_id' => $this->project->id,
            'alert_id' => $alert->id,
            'status' => 'triggered',
            'evaluated_value' => 7.50,
        ]);

        Notification::assertSentTo($this->user, AlertTriggeredNotification::class);
    }

    public function test_webhook_rejects_invalid_token()
    {
        $response = $this->withHeaders([
            'X-Monitoring-Token' => 'invalid-token',
        ])->postJson('/api/alerts/triggered', [
            'alert_name' => 'Unauthorized Alert',
        ]);

        $response->assertStatus(403);
    }
}
