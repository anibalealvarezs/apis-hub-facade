<?php

namespace Tests\Unit\Database;

use App\Domain\ChannelProfiles\ChannelProfileRegistry;
use App\Models\ApisHubRelease;
use Database\Seeders\ApisHubReleaseV1184Seeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApisHubReleaseV1184SeederTest extends TestCase
{
    private string $previousConnection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousConnection = DB::getDefaultConnection();
        config(['database.connections.release_1184_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::setDefaultConnection('release_1184_test');
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
        DB::purge('release_1184_test');
        DB::setDefaultConnection($this->previousConnection);
        parent::tearDown();
    }

    public function testRegistrationIsIdempotentAndDoesNotScheduleTenantWork(): void
    {
        Bus::fake();
        $previous = ApisHubRelease::create(['version_tag' => 'v1.18.3', 'is_default' => true,
            'supported_channels' => ['google_analytics', 'facebook_marketing'],
            'config_schemas' => ['google_analytics' => ['fields' => ['preserved' => true]]],
            'upgrade_impact' => [['introduced_in' => '1.18.3', 'channel' => 'google_search_console', 'label' => 'Google Search Console']]]);
        $seeder = new ApisHubReleaseV1184Seeder();
        $seeder->run();
        $id = ApisHubRelease::where('version_tag', 'v1.18.4')->value('id');
        $seeder->run();
        $release = ApisHubRelease::where('version_tag', 'v1.18.4')->sole();
        self::assertSame($id, $release->id);
        self::assertTrue($release->is_active);
        self::assertTrue($release->is_default);
        self::assertSame(1, ApisHubRelease::where('is_default', true)->count());
        self::assertFalse($previous->fresh()->is_default);
        self::assertSame(['google_analytics', 'facebook_marketing'], $release->supported_channels);
        self::assertTrue($release->config_schemas['google_analytics']['fields']['preserved']);
        self::assertSame([], $release->upgrade_commands);
        self::assertSame([], $release->upgrade_impact);
        self::assertSame('1.18.3', $previous->fresh()->upgrade_impact[0]['introduced_in']);
        foreach (['^1.3.1', '^1.1.4', '^1.1.1', '^1.2.1', 'not unique people', 'No schema rewrite'] as $text) self::assertStringContainsString($text, $release->changelog);
        Bus::assertNothingDispatched();
    }

    public function testFreshRegistrationUsesCurrentChannelRegistry(): void
    {
        (new ApisHubReleaseV1184Seeder())->run();
        $release = ApisHubRelease::where('version_tag', 'v1.18.4')->sole();
        self::assertSame(array_keys(app(ChannelProfileRegistry::class)->all()), $release->supported_channels);
        self::assertNotEmpty($release->config_schemas);
    }

    public function testRegistrationWorksBeforeOptionalImpactMigration(): void
    {
        DB::connection()->getSchemaBuilder()->table('apis_hub_releases', fn (Blueprint $table) => $table->dropColumn('upgrade_impact'));
        (new ApisHubReleaseV1184Seeder())->run();
        self::assertTrue(ApisHubRelease::where('version_tag', 'v1.18.4')->sole()->is_default);
    }
}
