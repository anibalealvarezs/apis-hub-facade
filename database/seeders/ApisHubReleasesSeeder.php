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

        $upgradeCommands = [
            [
                'command' => 'docker compose exec -T master php bin/cli.php orm:schema-tool:update --force',
            ],
        ];

        ApisHubRelease::updateOrCreate(
            ['version_tag' => 'v1.13.3'], // Rama o tag estable actual
            [
                'is_active' => true,
                'description' => 'Stable production release.',
                'changelog' => "## v1.13.3\n\n- Bug fixes\n- Performance improvements",
                'supported_channels' => array_keys($schemaV1),
                'config_schemas' => $schemaV1,
                'upgrade_commands' => $upgradeCommands,
            ]
        );

            $this->command->info('ApisHubReleases seeded successfully.');
        }
    }
