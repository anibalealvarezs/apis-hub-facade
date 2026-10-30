<?php

namespace Tests\Unit\Services;

use App\Jobs\UpgradeProjectReleaseJob;
use App\Models\ApisHubRelease;
use App\Models\Project;
use App\Models\ProjectDeploymentLog;
use App\Models\Server;
use App\Services\DeployerService;
use App\Services\DurableUpgradeRunner;
use App\Services\RemoteEngineService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DurableUpgradeTest extends TestCase
{
    private string $previousConnection;
    private const ID = '12345678-1234-1234-1234-123456789abc';

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousConnection = DB::getDefaultConnection();
        config(['database.connections.durable_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::setDefaultConnection('durable_test');
        foreach ([
            'CREATE TABLE projects (id INTEGER PRIMARY KEY, name TEXT, subdomain TEXT, server_id INTEGER, health_status TEXT, apis_hub_release_id INTEGER, sync_config TEXT, last_deployed_at TEXT, deploy_started_at TEXT, deleted_at TEXT, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE apis_hub_releases (id INTEGER PRIMARY KEY, version_tag TEXT, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE project_deployment_logs (id INTEGER PRIMARY KEY, project_id INTEGER, status TEXT, output TEXT, started_at TEXT, completed_at TEXT, progress_stage TEXT, progress_updated_at TEXT, upgrade_operation_id TEXT UNIQUE, target_release_id INTEGER, remote_prepared_at TEXT, remote_output_hash TEXT, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE project_status_logs (id INTEGER PRIMARY KEY, project_id INTEGER, is_active INTEGER, event_type TEXT, created_by_id INTEGER, notes TEXT, created_at TEXT, updated_at TEXT)',
        ] as $sql) DB::statement($sql);
        DB::table('apis_hub_releases')->insert([['id' => 1, 'version_tag' => 'v1.18.2'], ['id' => 2, 'version_tag' => 'v1.18.3']]);
        DB::table('projects')->insert(['id' => 1, 'name' => 'test', 'subdomain' => 'test', 'server_id' => 1, 'health_status' => 'upgrading', 'apis_hub_release_id' => 1, 'last_deployed_at' => '2026-10-29 00:00:00']);
    }

    protected function tearDown(): void
    {
        DB::purge('durable_test');
        DB::setDefaultConnection($this->previousConnection);
        parent::tearDown();
    }

    public function testFreshMonitorResumesSameOperationAndFinalizesExactlyOnce(): void
    {
        $deployer = $this->createMock(DeployerService::class);
        $deployer->expects($this->exactly(2))->method('upgradeRelease')
            ->willReturnOnConsecutiveCalls(['status' => 'running', 'output' => 'Disconnected'], ['status' => 'success', 'output' => 'Verified']);
        $this->job()->handle($deployer);
        $this->assertSame('running', ProjectDeploymentLog::first()->status);
        $this->assertSame(1, Project::find(1)->apis_hub_release_id);
        // A replacement queue worker reads the persisted operation, not the old PHP object.
        $this->job()->handle($deployer);
        $this->assertSame('success', ProjectDeploymentLog::first()->status);
        $this->assertSame(2, Project::find(1)->apis_hub_release_id);
        $this->job()->handle($deployer);
        $this->assertSame('online', Project::find(1)->health_status);
        $this->assertSame(1, DB::table('project_status_logs')->count());
        $this->assertSame(1, ProjectDeploymentLog::count());
    }

    public function testLostWorkerAndTransportExceptionDoNotDeclareRemoteFailure(): void
    {
        $deployer = $this->createMock(DeployerService::class);
        $deployer->method('upgradeRelease')->willThrowException(new \RuntimeException('secret must not escape'));
        $job = $this->job();
        $job->handle($deployer);
        $job->failed(new \RuntimeException('worker killed'));
        $this->assertSame('running', ProjectDeploymentLog::first()->status);
        $this->assertSame('upgrading', Project::find(1)->health_status);
        $this->assertSame(1, Project::find(1)->apis_hub_release_id);
        $this->assertStringNotContainsString('secret', ProjectDeploymentLog::first()->output);
    }

    public function testCompletedRemoteRunIsProbedWithoutLaunchingOrWritingAndRequiresReadiness(): void
    {
        $project = Project::find(1);
        $project->setRelation('server', new Server());
        $deployer = $this->getMockBuilder(DeployerService::class)->setConstructorArgs([$this->createMock(RemoteEngineService::class)])
            ->onlyMethods(['runSshCommands', 'writeRemoteFile'])->getMock();
        $deployer->expects($this->never())->method('writeRemoteFile');
        $deployer->expects($this->exactly(2))->method('runSshCommands')->willReturnOnConsecutiveCalls(
            ['status' => 'success', 'output' => "finished:0\nlegacy SECRET=value\n"], ['status' => 'error', 'output' => 'not ready']);
        $result = $deployer->upgradeRelease($project, ApisHubRelease::find(2), operationId: self::ID);
        $this->assertSame('running', $result['status']);
        $this->assertSame('verifying', $result['remote_state']);
        $this->assertStringNotContainsString('SECRET', $result['output']);
    }

    public function testRunnerPersistsResultAndRefusesReplayAfterStartedEvenWithoutResult(): void
    {
        $directory = DurableUpgradeRunner::directory(self::ID);
        $script = DurableUpgradeRunner::script($directory, 'false');
        $this->assertStringContainsString('test ! -e started || exit 0', $script);
        $this->assertStringContainsString('mv result.tmp result', $script);
        $this->assertStringContainsString('flock -n 8', $script);
        $this->assertStringContainsString('server.lock', $script);
        $this->assertStringContainsString('</dev/null >/dev/null 2>&1 &', DurableUpgradeRunner::launch($directory));
        $this->assertSame('error', DurableUpgradeRunner::decode("finished:1\nfailed")['status']);
        $this->assertSame('success', DurableUpgradeRunner::decode("finished:0\ncompleted")['status']);
        $this->assertSame('running', DurableUpgradeRunner::decode('interrupted')['status']);
    }

    public function testMissingPreparedOperationCannotReplayItsMigration(): void
    {
        $project = Project::find(1);
        $project->setRelation('server', new Server());
        $deployer = $this->getMockBuilder(DeployerService::class)->setConstructorArgs([$this->createMock(RemoteEngineService::class)])
            ->onlyMethods(['runSshCommands', 'writeRemoteFile'])->getMock();
        $deployer->expects($this->once())->method('runSshCommands')->willReturn(['status' => 'success', 'output' => "missing\n"]);
        $deployer->expects($this->never())->method('writeRemoteFile');
        $result = $deployer->upgradeRelease($project, ApisHubRelease::find(2), operationId: self::ID, remotePrepared: true);
        $this->assertSame('interrupted', $result['remote_state']);
    }

    public function testLostLaunchAcknowledgementReattachesWithoutUploadingOrRunningAgain(): void
    {
        $project = Project::find(1);
        $project->setRelation('server', new Server());
        $deployer = $this->getMockBuilder(DeployerService::class)->setConstructorArgs([$this->createMock(RemoteEngineService::class)])
            ->onlyMethods(['runSshCommands', 'writeRemoteFile', 'assertComposerRepositoryReachable', 'generateEnvContent'])->getMock();
        $deployer->method('assertComposerRepositoryReachable')->willReturn(true);
        $deployer->method('generateEnvContent')->willReturn('TEST_SECRET=private');
        $deployer->expects($this->exactly(2))->method('writeRemoteFile')->willReturn(['status' => 'success']);
        $deployer->expects($this->exactly(4))->method('runSshCommands')->willReturnOnConsecutiveCalls(
            ['status' => 'success', 'output' => "missing\n"], // Probe before launch.
            ['status' => 'success', 'output' => ''], // Host tools preflight.
            ['status' => 'error', 'output' => 'SSH disconnected after launch'],
            ['status' => 'success', 'output' => "running\nGSC Web backfill: merged 100 duplicate configurations.\n"]
        );
        $prepared = false;
        $result = $deployer->upgradeRelease($project, ApisHubRelease::find(2), function ($stage) use (&$prepared) {
            if ($stage === 'launching') $prepared = true;
        }, self::ID);
        $this->assertTrue($prepared);
        $this->assertSame('running', $result['status']);
        $result = $deployer->upgradeRelease($project, ApisHubRelease::find(2), operationId: self::ID, remotePrepared: $prepared);
        $this->assertSame('running', $result['status']);
        $this->assertSame('merging', $result['progress_stage']);
        $this->assertStringNotContainsString('TEST_SECRET', $result['output']);
    }

    public function testExplicitRemoteFailureKeepsPreviousReleaseAndDoesNotRetryMigration(): void
    {
        $deployer = $this->createMock(DeployerService::class);
        $deployer->expects($this->once())->method('upgradeRelease')->willReturn(['status' => 'error', 'output' => 'Remote migration failed']);
        $this->job()->handle($deployer);
        $this->job()->handle($deployer);
        $this->assertSame('failed', ProjectDeploymentLog::first()->status);
        $this->assertSame('error', Project::find(1)->health_status);
        $this->assertSame(1, Project::find(1)->apis_hub_release_id);
    }

    public function testFinalizationDatabaseFailureCannotLeaveSuccessfulLogWithOldRelease(): void
    {
        DB::statement("CREATE TRIGGER block_finalization BEFORE INSERT ON project_status_logs BEGIN SELECT RAISE(ABORT, 'injected'); END");
        $deployer = $this->createMock(DeployerService::class);
        $deployer->method('upgradeRelease')->willReturn(['status' => 'success', 'output' => 'Verified']);
        $this->job()->handle($deployer);
        $this->assertSame('running', ProjectDeploymentLog::first()->status);
        $this->assertSame(1, Project::find(1)->apis_hub_release_id);
        $this->assertSame('upgrading', Project::find(1)->health_status);
    }

    public function testDeploymentWaitsWhileDetachedUpgradeOwnsTheServer(): void
    {
        $deployer = $this->createMock(DeployerService::class);
        $deployer->method('upgradeRelease')->willReturn(['status' => 'running', 'output' => '']);
        $deployer->expects($this->never())->method('deploy');
        $this->job()->handle($deployer);
        DB::table('projects')->insert(['id' => 2, 'name' => 'another', 'subdomain' => 'another', 'server_id' => 1, 'health_status' => 'deploying']);
        $job = (new \App\Jobs\DeployProjectJob(Project::find(2)))->withFakeQueueInteractions();
        $job->handle($deployer);
        $job->assertReleased(60);
        $this->assertSame('deploying', Project::find(2)->health_status);
        $this->assertSame(1, ProjectDeploymentLog::count());
    }

    public function testUnchangedRemoteOutputDoesNotClaimRecentProgress(): void
    {
        $deployer = $this->createMock(DeployerService::class);
        $deployer->method('upgradeRelease')->willReturn(['status' => 'running', 'output' => '', 'progress_stage' => 'merging', 'progress_fingerprint' => str_repeat('a', 64)]);
        $this->job()->handle($deployer);
        ProjectDeploymentLog::first()->update(['progress_updated_at' => '2026-01-01 00:00:00']);
        $this->job()->handle($deployer);
        $this->assertSame('2026-01-01 00:00:00', ProjectDeploymentLog::first()->progress_updated_at->format('Y-m-d H:i:s'));
    }

    public function testDetachedLinuxRunnerSurvivesLauncherExitAndRejectsDuplicateLaunch(): void
    {
        if (PHP_OS_FAMILY !== 'Linux') $this->markTestSkipped('Requires Linux bash, nohup and flock (the production host runtime).');
        $root = sys_get_temp_dir().'/durable-upgrade-'.bin2hex(random_bytes(8));
        $dir = $root.'/operation';
        mkdir($dir, 0700, true);
        try {
            file_put_contents($dir.'/runner.sh', DurableUpgradeRunner::script($dir, 'echo observation >> '.DurableUpgradeRunner::quote($dir.'/observations').' && sleep 1'));
            foreach ([1, 2] as $_) {
                $launch = new \Symfony\Component\Process\Process(['bash', '-c', DurableUpgradeRunner::launch($dir)]);
                $launch->setTimeout(5)->mustRun();
            }
            $deadline = microtime(true) + 10;
            while (!file_exists($dir.'/result') && microtime(true) < $deadline) usleep(100000);
            $this->assertFileExists($dir.'/result');
            $this->assertSame('0', trim(file_get_contents($dir.'/result')));
            $this->assertSame("observation\n", file_get_contents($dir.'/observations'));
            $launch->mustRun(); // A completed operation is also never replayed.
            $this->assertSame("observation\n", file_get_contents($dir.'/observations'));
        } finally {
            foreach (glob($dir.'/*') as $file) unlink($file);
            rmdir($dir);
            if (file_exists($root.'/server.lock')) unlink($root.'/server.lock');
            rmdir($root);
        }
    }

    private function job(): UpgradeProjectReleaseJob
    {
        return new UpgradeProjectReleaseJob(Project::find(1), ApisHubRelease::find(2), self::ID);
    }
}
