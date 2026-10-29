<?php

namespace Tests\Unit\Services;

use Anibalealvarezs\ApisHubApi\ApisHubApi;
use App\Http\Controllers\MonitoringController;
use App\Models\Project;
use App\Services\RemoteEngineService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UpgradeRequestSuppressionTest extends TestCase
{
    private string $previousConnection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousConnection = DB::getDefaultConnection();
        config(['database.connections.upgrade_requests_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::setDefaultConnection('upgrade_requests_test');
        DB::connection()->getSchemaBuilder()->create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subdomain');
            $table->string('health_status')->nullable();
            $table->string('monitoring_token');
            $table->text('health_metrics')->nullable();
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->timestamp('last_deployed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        DB::table('projects')->insert(['id' => 1, 'name' => 'test', 'subdomain' => 'test',
            'health_status' => 'upgrading', 'monitoring_token' => 'test-token',
            'last_deployed_at' => '2026-10-29 00:00:00']);
    }

    protected function tearDown(): void
    {
        DB::purge('upgrade_requests_test');
        DB::setDefaultConnection($this->previousConnection);
        parent::tearDown();
    }

    public function testStaleProjectCannotSendTelemetryWhileDatabaseSaysUpgrading(): void
    {
        $project = Project::findOrFail(1);
        $project->health_status = 'online'; // Already-loaded Livewire/queue model.
        $service = $this->getMockBuilder(RemoteEngineService::class)->onlyMethods(['getClient'])->getMock();
        $service->expects($this->never())->method('getClient');
        $result = $service->getSyncTelemetry($project);
        self::assertSame('tenant_upgrading', $result['error_code']);
        self::assertFalse($result['retryable']);
        self::assertSame(503, $result['http_status']);
    }

    public function testRequestsResumeWithoutReloadingTheProjectWhenUpgradeEnds(): void
    {
        $project = Project::findOrFail(1);
        DB::table('projects')->where('id', 1)->update(['health_status' => 'online']);
        $client = $this->createMock(ApisHubApi::class);
        $client->expects($this->once())->method('getSyncTelemetry')->willReturn(['status' => 'success']);
        $service = $this->getMockBuilder(RemoteEngineService::class)->onlyMethods(['getClient'])->getMock();
        $service->expects($this->once())->method('getClient')->willReturn($client);
        self::assertSame(['status' => 'success'], $service->getSyncTelemetry($project));
    }

    public function testHeartbeatPreservesUpgradeAndNormalHeartbeatStillMarksOnline(): void
    {
        $controller = new MonitoringController();
        $request = Request::create('/api/heartbeat', 'POST', ['system' => ['ok' => true]], server: ['HTTP_X_MONITORING_TOKEN' => 'test-token']);
        self::assertSame(200, $controller->heartbeat($request)->getStatusCode());
        self::assertSame('upgrading', Project::findOrFail(1)->health_status);
        self::assertNotNull(Project::findOrFail(1)->last_heartbeat_at);
        DB::table('projects')->where('id', 1)->update(['health_status' => 'offline']);
        $controller->heartbeat($request);
        self::assertSame('online', Project::findOrFail(1)->health_status);
    }
}
