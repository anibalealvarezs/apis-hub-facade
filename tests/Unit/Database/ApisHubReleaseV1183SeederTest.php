<?php

namespace Tests\Unit\Database;

use App\Domain\ChannelProfiles\ChannelProfileRegistry;
use App\Models\ApisHubRelease;
use Database\Seeders\ApisHubReleaseV1183Seeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApisHubReleaseV1183SeederTest extends TestCase
{
    private string $previousConnection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousConnection = DB::getDefaultConnection();
        config(['database.connections.release_seeder_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::setDefaultConnection('release_seeder_test');
        DB::connection()->getSchemaBuilder()->create('apis_hub_releases', function (Blueprint $table) {
            $table->id();
            $table->string('version_tag')->unique();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->text('description')->nullable();
            $table->text('changelog')->nullable();
            $table->json('supported_channels')->nullable();
            $table->json('config_schemas')->nullable();
            $table->json('upgrade_commands')->nullable();
            $table->json('upgrade_impact')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::purge('release_seeder_test');
        DB::setDefaultConnection($this->previousConnection);
        parent::tearDown();
    }

    public function testRepeatRegistrationPreservesChannelsAndCreatesOneDefault(): void
    {
        ApisHubRelease::create(['version_tag' => 'v1.18.2', 'is_default' => true,
            'supported_channels' => ['google_search_console'],
            'config_schemas' => ['google_search_console' => ['fields' => ['preserved' => true]]]]);
        $seeder = new ApisHubReleaseV1183Seeder();
        $seeder->run();
        $id = ApisHubRelease::where('version_tag', 'v1.18.3')->value('id');
        $seeder->run();
        $release = ApisHubRelease::where('version_tag', 'v1.18.3')->sole();
        self::assertSame($id, $release->id);
        self::assertSame(1, ApisHubRelease::where('is_default', true)->count());
        self::assertTrue($release->is_default);
        self::assertFalse(ApisHubRelease::where('version_tag', 'v1.18.2')->first()->is_default);
        self::assertSame(['google_search_console'], $release->supported_channels);
        self::assertTrue($release->config_schemas['google_search_console']['fields']['preserved']);
        self::assertSame([], $release->upgrade_commands);
        self::assertStringContainsString('^1.3.0', $release->changelog);
    }

    public function testFreshInstallationUsesTheChannelRegistry(): void
    {
        (new ApisHubReleaseV1183Seeder())->run();
        $release = ApisHubRelease::where('version_tag', 'v1.18.3')->sole();
        self::assertSame(array_keys(app(ChannelProfileRegistry::class)->all()), $release->supported_channels);
        self::assertTrue($release->is_active);
        self::assertTrue($release->is_default);
    }

    public function testReleaseRegistrationBeforeProgressMigrationAndMetadataBackfill(): void
    {
        $schema = DB::connection()->getSchemaBuilder();
        $schema->table('apis_hub_releases', fn (Blueprint $table) => $table->dropColumn('upgrade_impact'));
        (new ApisHubReleaseV1183Seeder())->run();
        $schema->create('project_deployment_logs', fn (Blueprint $table) => $table->id());
        $migration = require database_path('migrations/2026_10_29_230000_add_upgrade_progress.php');
        $migration->up();
        self::assertTrue($schema->hasColumn('project_deployment_logs', 'progress_stage'));
        self::assertTrue($schema->hasColumn('project_deployment_logs', 'progress_updated_at'));
        self::assertSame('google_search_console', ApisHubRelease::where('version_tag', 'v1.18.3')->sole()->upgrade_impact[0]['channel']);
        $migration->down();
        self::assertFalse($schema->hasColumn('project_deployment_logs', 'progress_stage'));
    }
}
