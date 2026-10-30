<?php

namespace Tests\Unit\Services;

use App\Models\ApisHubRelease;
use App\Models\Project;
use App\Models\ProjectDeploymentLog;
use App\Services\UpgradeAdvisory;
use App\Services\UpgradeProgress;
use Tests\TestCase;

class UpgradeExperienceTest extends TestCase
{
    public function testStreamingChunksProduceOnlyKnownStages(): void
    {
        $parser = new UpgradeProgress();
        $stages = [];
        $update = function ($stage) use (&$stages) { $stages[] = $stage; };
        $parser->consume("private payload\n__UPGRADE_STA", $update);
        $parser->consume("GE__:database\nGSC Web backfill: staged 500 configurations\n", $update);
        $parser->consume("GSC Web backfill: reconciling 100 duplicate identity groups.\nGSC Web backfill: merged 100 duplicate configurations.\n", $update);
        $parser->consume("GSC Web backfill: updating metric hashes...\n__UPGRADE_STAGE__:unknown\n", $update);
        self::assertSame(['database', 'staging', 'duplicates', 'merging', 'finalizing'], $stages);
    }

    public function testProgressSummaryDoesNotExposeRawLogOutput(): void
    {
        $log = new ProjectDeploymentLog(['status' => 'running', 'progress_stage' => 'merging', 'output' => 'Technical SQL or private output']);
        self::assertSame('Organizing cached metrics', $log->getSummaryMessage());
        $log->status = 'success';
        self::assertStringContainsString('successfully', $log->getSummaryMessage());
    }

    public function testAdvisoryUsesReleaseWorkChannelAndCachedData(): void
    {
        $project = new Project(['subdomain' => 'test', 'last_deployed_at' => '2026-10-29',
            'sync_config' => ['google_search_console' => ['enabled' => false]],
            'health_metrics' => ['catalog' => ['queries' => 60000]]]);
        $project->setRelation('apisHubRelease', new ApisHubRelease(['version_tag' => 'v1.18.2']));
        $target = new ApisHubRelease(['version_tag' => 'v1.18.3', 'upgrade_impact' => [
            ['introduced_in' => '1.18.3', 'channel' => 'google_search_console', 'label' => 'Google Search Console'],
        ]]);
        $description = (new UpgradeAdvisory())->describe($project, $target);
        self::assertStringContainsString('reorganizes cached data for Google Search Console', $description);
        self::assertStringContainsString('more than an hour', $description);
        self::assertStringContainsString('large amount of saved data', $description);
        self::assertStringContainsString('outside synchronization hours', $description);
        self::assertStringContainsString('low-activity', $description);
        // A disabled channel can still contain cached history requiring migration.
        $project->setRelation('apisHubRelease', new ApisHubRelease(['version_tag' => 'v1.18.3']));
        self::assertStringNotContainsString('reorganizes cached data', (new UpgradeAdvisory())->describe($project, $target));
    }

    public function testUndeployedProjectsDoNotGetDowntimeWarnings(): void
    {
        self::assertSame('The new version will be used when this project is first deployed.',
            (new UpgradeAdvisory())->describe(new Project(['subdomain' => 'new']), null));
    }
}
