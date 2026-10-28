<?php

namespace App\Console\Commands;

use App\Domain\ChannelProfiles\ChannelProfileRegistry;
use App\Jobs\UpgradeProjectReleaseJob;
use App\Models\ApisHubRelease;
use App\Models\Project;
use Illuminate\Console\Command;

class RegisterReleaseV1182Command extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'release:register-v1-18-2 {--dispatch : Whether to automatically dispatch upgrades for matching projects}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Registers v1.18.2 release in Facade and optionally dispatches tenant upgrades';

    /**
     * Execute the console command.
     */
    public function handle(ChannelProfileRegistry $registry): int
    {
        $this->info('Registering APIs Hub v1.18.2 release in Facade...');

        $schema = [];
        foreach ($registry->all() as $profile) {
            $schema[$profile->getChannelKey()] = $profile->getSchemaDefinition();
        }
        $supportedChannels = array_keys($schema);

        $rel181 = ApisHubRelease::where('version_tag', 'v1.18.1')->first();

        $release = ApisHubRelease::updateOrCreate(
            ['version_tag' => 'v1.18.2'],
            [
                'is_active' => true,
                'is_default' => true,
                'description' => 'Modernizes Meta Ads with configurable breakdown passes, expanded conversion metrics, and optional metrics wipe on nuclear resync.',
                'changelog' => "## v1.18.2\n\n- Meta Ads Modernization: Configurable multi-pass breakdowns, top-level null breakdowns, and expanded conversion metrics.\n- Nuclear Resync: Added optional `--wipe-metrics` flag to purge historical metrics for targeted channels before resynchronizing.\n- Dependencies: Updated `anibalealvarezs/meta-hub-driver` to `^1.2.0` and `anibalealvarezs/facebook-graph-api` to `^1.2.0`.",
                'supported_channels' => $rel181?->supported_channels ?? $supportedChannels,
                'config_schemas' => $rel181?->config_schemas ?? $schema,
                'upgrade_commands' => [],
            ]
        );

        // Unset previous defaults
        ApisHubRelease::where('id', '!=', $release->id)->where('is_default', true)->update(['is_default' => false]);

        $this->info("✔ Registered {$release->version_tag} (ID: {$release->id}) as default release.");

        if ($this->option('dispatch')) {
            $this->info('Dispatching queued upgrade jobs for deployed tenants on v1.18.1...');

            $projects = Project::whereNotNull('last_deployed_at')
                ->where('apis_hub_release_id', $rel181?->id)
                ->get();

            foreach ($projects as $project) {
                UpgradeProjectReleaseJob::dispatch($project, $release);
                $this->line(" -> Queued upgrade to v1.18.2 for project: {$project->name} ({$project->subdomain})");
            }

            $this->info("Successfully dispatched {$projects->count()} tenant upgrade job(s).");
        } else {
            $this->comment('Tip: To automatically dispatch upgrades for all tenants, run with: --dispatch');
        }

        return Command::SUCCESS;
    }
}
