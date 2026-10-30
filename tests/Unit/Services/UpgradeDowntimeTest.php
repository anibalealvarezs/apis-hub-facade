<?php

namespace Tests\Unit\Services;

use App\Models\ApisHubRelease;
use App\Models\Project;
use App\Models\Server;
use App\Services\DeployerService;
use App\Services\RemoteEngineService;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class UpgradeDowntimeTest extends TestCase
{
    public function testMigrationFailuresNeverCheckOutOldCodeAndClassificationFollowsRestart(): void
    {
        $project = new Project(['name' => 'test', 'subdomain' => 'test', 'last_deployed_at' => '2026-10-29 00:00:00']);
        $old = new ApisHubRelease(['version_tag' => 'v1.18.2']);
        $target = new ApisHubRelease(['version_tag' => 'v1.18.3']);
        $server = new Server();
        $project->setRelation('apisHubRelease', $old);
        $project->setRelation('server', $server);
        $deployer = $this->getMockBuilder(DeployerService::class)
            ->setConstructorArgs([$this->createMock(RemoteEngineService::class)])
            ->onlyMethods(['assertComposerRepositoryReachable', 'generateEnvContent', 'runSshCommands', 'writeRemoteFile'])->getMock();
        $deployer->method('assertComposerRepositoryReachable')->willReturn(true);
        $deployer->method('generateEnvContent')->willReturn('TEST=value');
        $deployer->expects($this->once())->method('writeRemoteFile')->with($server, '/var/www/apis-hub/tenants/test/.env', 'TEST=value', 0600, 120)->willReturn(['status' => 'success']);
        $deployer->expects($this->once())->method('runSshCommands')->willReturnCallback(function ($server, $commands, $timeout) {
            $this->assertSame(3600, $timeout);
            $script = implode(' && ', $commands);
            $this->assertStringNotContainsString('TEST=value', $script);
            $this->assertLessThan(strpos($script, 'app:classify-queries'), strpos($script, 'up -d --force-recreate --remove-orphans master'));
            $migration = array_values(array_filter($commands, fn ($command) => str_contains($command, 'if ! docker compose run')))[0];
            $this->assertStringNotContainsString('git checkout', $migration);
            $this->assertContains("echo '__MIGRATIONS_STARTED__'", $commands);
            $this->assertStringStartsWith('(docker compose run', $commands[count($commands)-1]);
            return ['status' => 'error', 'output' => DeployerService::MIGRATIONS_STARTED_MARKER."\nMigration still running"];
        });
        $result = $deployer->upgradeRelease($project, $target);
        $this->assertSame('upgrade_failed_after_migration', $result['code']);
        $this->assertFalse($result['auto_recovered']);
    }

    public function testRemoteShellScriptIsPassedAsOneUnexpandedArgumentAndKeyIsRemoved(): void
    {
        Process::fake();
        $server = new Server(['ssh_user' => 'test', 'ip_address' => '127.0.0.1', 'ssh_private_key' => 'test-only']);
        $deployer = new DeployerService($this->createMock(RemoteEngineService::class));
        $commands = ['echo "$HOME"', 'docker compose run --entrypoint "php" master bin/cli.php'];
        $deployer->runSshCommands($server, $commands);
        Process::assertRan(function ($process) use ($commands) {
            $this->assertIsArray($process->command);
            $this->assertSame(implode(' && ', $commands), $process->command[count($process->command)-1]);
            $this->assertFileDoesNotExist($process->command[2]);
            return true;
        });
    }

    public function testTimeoutPreservesMigrationProgressWithoutExposingTheCommand(): void
    {
        $native = new \Symfony\Component\Process\Process(['echo', 'TEST_SECRET=do-not-log']);
        $native->setTimeout(1);
        $error = new \Illuminate\Process\Exceptions\ProcessTimedOutException(
            new \Symfony\Component\Process\Exception\ProcessTimedOutException($native, \Symfony\Component\Process\Exception\ProcessTimedOutException::TYPE_GENERAL),
            Process::result(output: DeployerService::MIGRATIONS_STARTED_MARKER."\nbackfill progress")
        );
        Process::fake(fn () => throw $error);
        $server = new Server(['ssh_user' => 'test', 'ip_address' => '127.0.0.1', 'ssh_private_key' => 'test-only']);
        $result = (new DeployerService($this->createMock(RemoteEngineService::class)))->runSshCommands($server, ['echo TEST_SECRET=do-not-log']);
        $this->assertSame('ssh_timeout', $result['code']);
        $this->assertStringContainsString(DeployerService::MIGRATIONS_STARTED_MARKER, $result['output']);
        $this->assertStringNotContainsString('do-not-log', $result['output']);
    }

    public function testUnexpectedTransportExceptionsDoNotExposeCommandsOrInput(): void
    {
        Process::fake(fn () => throw new \RuntimeException('TEST_SECRET=do-not-log'));
        $server = new Server(['ssh_user' => 'test', 'ip_address' => '127.0.0.1', 'ssh_private_key' => 'test-only']);
        $deployer = new DeployerService($this->createMock(RemoteEngineService::class));
        $result = $deployer->runSshCommands($server, ['echo TEST_SECRET=do-not-log']);
        $this->assertSame('ssh_execution_failed', $result['code']);
        $this->assertStringNotContainsString('do-not-log', $result['output']);
        $result = $deployer->writeRemoteFile($server, '/tmp/test.env', 'TEST_SECRET=do-not-log');
        $this->assertSame('ssh_write_failed', $result['code']);
        $this->assertStringNotContainsString('do-not-log', $result['output']);
    }

    public function testQueueVisibilityExceedsTheWholeUpgradeJobBudget(): void
    {
        $job = new \App\Jobs\UpgradeProjectReleaseJob(new Project(), new ApisHubRelease());
        foreach (['database', 'redis', 'beanstalkd'] as $connection) {
            $this->assertGreaterThan($job->timeout, config("queue.connections.{$connection}.retry_after"));
        }
    }
}
