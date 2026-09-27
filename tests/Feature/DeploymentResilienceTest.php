<?php

use App\Models\ApisHubRelease;
use App\Models\Project;
use App\Models\ProjectDeploymentLog;
use App\Models\Server;
use App\Services\DeployerService;
use Illuminate\Support\Facades\Process;

/**
 * Excerpt of the real Composer failure captured from the facade log when a project was
 * upgraded to v1.17.0 while satis.anibalalvarez.com was returning HTTP 522.
 */
function dependencyRepositoryFailureOutput(): string
{
    return <<<'TXT'
STDOUT:
Installing dependencies from lock file
  - Downloading anibalealvarezs/api-driver-core (v1.1.2)
    Failed to download anibalealvarezs/api-driver-core from dist: The "https://satis.anibalalvarez.com/dist/anibalealvarezs/api-driver-core/anibalealvarezs-api-driver-core-d650ec.zip" file could not be downloaded (HTTP/2 522 )
    Source fallback is disabled. Not trying alternative sources.
  - Downloading anibalealvarezs/mailchimp-api (v1.0.0)
    Failed to download anibalealvarezs/mailchimp-api from dist: The "https://satis.anibalalvarez.com/dist/anibalealvarezs/mailchimp-api/anibalealvarezs-mailchimp-api-f422dca.zip" file could not be downloaded (HTTP/2 522 )
    Source fallback is disabled. Not trying alternative sources.

STDERR:
In CurlDownloader.php line 674:

  The "https://satis.anibalalvarez.com/dist/anibalealvarezs/api-driver-core/a
  nibalealvarezs-api-driver-core-d650ec.zip" file could not be downloaded (HTTP/2 522 )

install [--prefer-source] [--prefer-dist] [--prefer-install PREFER-INSTALL] [--dry-run] [--download-only] [--dev] [--no-suggest] [--no-dev] [--no-security-blocking] [--no-blocking] [--no-autoloader] [--no-progress] [--no-install] [--audit] [--audit-format AUDIT-FORMAT] [-v|vv|vvv|--verbose] [-o|--optimize-autoloader] [-a|--classmap-authoritative] [--strict-psr-autoloader] [--apcu-autoloader] [--apcu-autoloader-prefix APCU-AUTOLOADER-PREFIX] [--ignore-platform-req IGNORE-PLATFORM-REQ] [--ignore-platform-reqs] [--] [<packages>...]
TXT;
}

/**
 * Every command the fake SSH transport was asked to run, in order.
 */
function &sshCommandLog(): array
{
    static $log = [];

    return $log;
}

function sshRan(string $needle): bool
{
    foreach (sshCommandLog() as $command) {
        if (str_contains($command, $needle)) {
            return true;
        }
    }

    return false;
}

function clearSshCommandLog(): void
{
    $log = &sshCommandLog();
    $log = [];
}

/**
 * True when a command that reverts the checkout ran outside the upgrade chain.
 * The upgrade chain always contains the build step, so it never matches.
 */
function rollbackCheckoutRan(): bool
{
    foreach (sshCommandLog() as $command) {
        if (str_contains($command, 'git checkout') && ! str_contains($command, 'docker compose build')) {
            return true;
        }
    }

    return false;
}

/**
 * Build a fake for the SSH transport.
 *
 * $probeUp          whether the Composer repository answers the pre-flight probe
 * $chainExitCode    exit code returned by the single upgrade chain
 * $chainOutput      stdout/stderr the upgrade chain produces
 * $rollbackExitCode exit code for the recovery commands (null = they succeed)
 */
function fakeSsh(bool $probeUp, int $chainExitCode, string $chainOutput, ?int $rollbackExitCode = null): void
{
    Process::fake(function ($process) use ($probeUp, $chainExitCode, $chainOutput, $rollbackExitCode) {
        $command = $process->command;
        sshCommandLog()[] = $command;

        // Pre-flight reachability probe.
        if (str_contains($command, 'packages.json')) {
            return $probeUp
                ? Process::result('', '', 0)
                : Process::result('', 'curl: (22) The requested URL returned error: 522', 22);
        }

        // The upgrade chain always contains the build step; the recovery commands do not.
        if (str_contains($command, 'docker compose build')) {
            return Process::result($chainOutput, '', $chainExitCode);
        }

        // Recovery: bare `git checkout` of the previous tag, the .env write, and the
        // container restart that deliberately omits --build.
        if (str_contains($command, 'git checkout') || str_contains($command, 'docker compose up -d --force-recreate --remove-orphans')) {
            return $rollbackExitCode === null
                ? Process::result('recovered', '', 0)
                : Process::result('', 'recovery failed', $rollbackExitCode);
        }

        return Process::result('', '', 0);
    });
}

beforeEach(function () {
    clearSshCommandLog();

    $this->server = Server::factory()->create(['is_ready' => true]);

    $this->currentRelease = ApisHubRelease::create([
        'version_tag' => 'v1.16.0',
        'is_active' => true,
        'is_default' => false,
    ]);

    $this->targetRelease = ApisHubRelease::create([
        'version_tag' => 'v1.17.0',
        'is_active' => true,
        'is_default' => true,
    ]);

    $this->project = Project::factory()->create([
        'server_id' => $this->server->id,
        'subdomain' => 'resilience-test',
        'last_deployed_at' => now()->subDay(),
        'apis_hub_release_id' => $this->currentRelease->id,
    ]);
});

it('aborts the upgrade before touching the tenant when the composer repository is unreachable', function () {
    fakeSsh(probeUp: false, chainExitCode: 0, chainOutput: 'should never run');

    $result = app(DeployerService::class)->upgradeRelease($this->project, $this->targetRelease);

    expect($result['status'])->toBe('error')
        ->and($result['code'])->toBe('composer_repository_unreachable')
        ->and($result['auto_recovered'])->toBeFalse()
        ->and($result['output'])->toContain('aborted before any change was made');

    // Nothing that mutates the tenant may run once the probe has failed.
    expect(sshRan('git checkout'))->toBeFalse()
        ->and(sshRan('docker compose stop'))->toBeFalse()
        ->and(sshRan('docker compose build'))->toBeFalse();
});

it('rolls the tenant back to the previous release when a dependency download fails', function () {
    fakeSsh(probeUp: true, chainExitCode: 1, chainOutput: dependencyRepositoryFailureOutput());

    $result = app(DeployerService::class)->upgradeRelease($this->project, $this->targetRelease);

    expect($result['status'])->toBe('rolled_back')
        ->and($result['auto_recovered'])->toBeTrue()
        ->and($result['output'])->toContain('v1.16.0');

    // The checkout must be reverted, otherwise the bind-mounted /app would boot the new
    // release's code against the old vendor tree.
    expect(rollbackCheckoutRan())->toBeTrue();

    // Containers are recreated without --build, since rebuilding would re-enter the
    // same failing Composer download.
    $revive = array_values(array_filter(sshCommandLog(), fn ($c) => str_contains($c, 'docker compose up -d --force-recreate --remove-orphans') && ! str_contains($c, 'docker compose build')));

    expect($revive)->not->toBeEmpty()
        ->and($revive[0])->not->toContain('--build');
});

it('does not roll back once migrations have been applied', function () {
    fakeSsh(
        probeUp: true,
        chainExitCode: 1,
        chainOutput: DeployerService::MIGRATIONS_APPLIED_MARKER . "\nSomething went wrong later in the deploy",
    );

    $result = app(DeployerService::class)->upgradeRelease($this->project, $this->targetRelease);

    expect($result['status'])->toBe('error')
        ->and($result['code'])->toBe('upgrade_failed_after_migration')
        ->and($result['auto_recovered'])->toBeFalse();

    expect(rollbackCheckoutRan())->toBeFalse();
});

it('does not roll back when a pre-migration failure is not a dependency problem', function () {
    fakeSsh(probeUp: true, chainExitCode: 1, chainOutput: 'failed to solve: Dockerfile parse error on line 12');

    $result = app(DeployerService::class)->upgradeRelease($this->project, $this->targetRelease);

    expect($result['status'])->toBe('error')
        ->and($result['code'])->toBe('upgrade_failed_pre_migration')
        ->and($result['auto_recovered'])->toBeFalse();

    expect(rollbackCheckoutRan())->toBeFalse();
});

it('reports a failed rollback distinctly from a successful one', function () {
    fakeSsh(probeUp: true, chainExitCode: 1, chainOutput: dependencyRepositoryFailureOutput(), rollbackExitCode: 1);

    $result = app(DeployerService::class)->upgradeRelease($this->project, $this->targetRelease);

    expect($result['status'])->toBe('error')
        ->and($result['code'])->toBe('rollback_failed')
        ->and($result['auto_recovered'])->toBeFalse();
});

it('classifies composer repository outages as dependency failures', function () {
    $service = app(DeployerService::class);

    expect($service->isDependencyRepositoryFailure(dependencyRepositoryFailureOutput()))->toBeTrue()
        ->and($service->isDependencyRepositoryFailure('Failed to download foo/bar from dist: connection timed out'))->toBeTrue()
        ->and($service->isDependencyRepositoryFailure('packages.json file could not be downloaded (HTTP/3 523 )'))->toBeTrue()
        ->and($service->isDependencyRepositoryFailure('failed to solve: process "/bin/sh -c composer install" did not complete successfully: exit code: 1'))->toBeFalse()
        ->and($service->isDependencyRepositoryFailure(''))->toBeFalse();
});

it('surfaces the dependency outage instead of an unknown issue in the deployment summary', function () {
    $log = ProjectDeploymentLog::create([
        'project_id' => $this->project->id,
        'status' => 'failed',
        'output' => "Starting upgrade to release v1.17.0...\n\n=== UPGRADE OUTPUT ===\n" . dependencyRepositoryFailureOutput(),
        'started_at' => now(),
        'completed_at' => now(),
    ]);

    // Composer prints the real cause above its usage block, so a last-line-only read
    // used to report "unknown issue" and hide the outage entirely.
    expect($log->getSummaryMessage())->toBe('Dependency download failed: the private Composer repository was unreachable.')
        ->and($log->getSummaryMessage())->not->toContain('unknown issue');
});

it('describes a rolled back upgrade without blaming the tenant', function () {
    $log = ProjectDeploymentLog::create([
        'project_id' => $this->project->id,
        'status' => 'rolled_back',
        'output' => 'The upgrade to v1.17.0 was rolled back automatically.',
        'started_at' => now(),
        'completed_at' => now(),
    ]);

    expect($log->getSummaryMessage())->toBe('The upgrade was rolled back automatically. The project is running its previous version again.');
});

it('still reports genuinely unknown failures with their trailing output', function () {
    $log = ProjectDeploymentLog::create([
        'project_id' => $this->project->id,
        'status' => 'failed',
        'output' => "some noise\nsomething nobody has classified before",
        'started_at' => now(),
        'completed_at' => now(),
    ]);

    expect($log->getSummaryMessage())->toBe('Deployment failed due to an unknown issue. (something nobody has classified before)');
});
