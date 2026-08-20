<?php

namespace Tests\Feature\Analytics;

use App\Models\AlertLog;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AlertPruningTest extends TestCase
{
    use RefreshDatabase;

    public function test_alert_logs_older_than_30_days_are_pruned()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        // Recent log (10 days old)
        $recentLog = AlertLog::create([
            'project_id' => $project->id,
            'alert_name' => 'Recent Alert',
            'source_type' => 'metric',
            'source_summary' => 'meta.spend',
            'asset_summary' => 'Main Account',
            'ast_snapshot' => [],
            'asset_filter_snapshot' => [],
            'evaluation_window' => ['start' => '2026-08-01', 'end' => '2026-08-01'],
            'evaluated_value' => 50.00,
            'aggregation_method' => 'latest',
            'status' => 'ok',
            'triggered_at' => now()->subDays(10),
            'created_at' => now()->subDays(10),
        ]);

        // Old log (35 days old) via DB insert so created_at is preserved
        $oldLogId = \Illuminate\Support\Facades\DB::table('alert_logs')->insertGetId([
            'project_id' => $project->id,
            'alert_name' => 'Old Alert',
            'source_type' => 'metric',
            'source_summary' => 'meta.spend',
            'asset_summary' => 'Main Account',
            'ast_snapshot' => json_encode([]),
            'asset_filter_snapshot' => json_encode([]),
            'evaluation_window' => json_encode(['start' => '2026-07-01', 'end' => '2026-07-01']),
            'evaluated_value' => 200.00,
            'aggregation_method' => 'latest',
            'status' => 'triggered',
            'triggered_at' => now()->subDays(35),
            'created_at' => now()->subDays(35),
        ]);

        $this->artisan('model:prune', ['--model' => [AlertLog::class]])
            ->assertExitCode(0);

        $this->assertDatabaseHas('alert_logs', ['id' => $recentLog->id]);
        $this->assertDatabaseMissing('alert_logs', ['id' => $oldLogId]);
    }
}
