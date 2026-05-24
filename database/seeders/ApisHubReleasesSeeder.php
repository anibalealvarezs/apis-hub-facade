<?php

namespace Database\Seeders;

use App\Models\ApisHubRelease;
use Illuminate\Database\Seeder;

class ApisHubReleasesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $registry = app(\App\Domain\ChannelProfiles\ChannelProfileRegistry::class);
        $schemaV1 = [];

        foreach ($registry->all() as $profile) {
            $schemaV1[$profile->getChannelKey()] = $profile->getSchemaDefinition();
        }

        ApisHubRelease::updateOrCreate(
            ['version_tag' => 'v1.13.2.6'], // Rama o tag estable actual
            [
                'is_active' => true,
                'supported_channels' => array_keys($schemaV1),
                'config_schemas' => $schemaV1,
            ]
        );
        
        $this->command->info('ApisHubReleases seeded successfully.');
    }
}
