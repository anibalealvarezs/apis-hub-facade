<?php

namespace App\Console\Commands;

use App\Domain\ChannelProfiles\ChannelProfileRegistry;
use App\Jobs\UpgradeProjectReleaseJob;
use App\Models\ApisHubRelease;
use App\Models\Project;
use Illuminate\Console\Command;

class RegisterSecurityPatchReleasesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'release:register-security-patches {--dispatch : Whether to automatically dispatch upgrades for matching projects}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Registers v1.17.1 and v1.18.1 security patch releases in Facade and optionally dispatches tenant upgrades';

    /**
     * Execute the console command.
     */
    public function handle(ChannelProfileRegistry $registry): int
    {
        $this->info('Registering emergency security patch releases in Facade...');

        $schema = [];
        foreach ($registry->all() as $profile) {
            $schema[$profile->getChannelKey()] = $profile->getSchemaDefinition();
        }
        $supportedChannels = array_keys($schema);

        // Find v1.17.0 and v1.18.0 to inherit configuration schemas
        $rel170 = ApisHubRelease::where('version_tag', 'v1.17.0')->first();
        $rel180 = ApisHubRelease::where('version_tag', 'v1.18.0')->first();

        // 1. Create or update v1.17.1
        $patch171 = ApisHubRelease::updateOrCreate(
            ['version_tag' => 'v1.17.1'],
            [
                'is_active' => true,
                'is_default' => false,
                'description' => 'Security Hotfix: Disables session.upload_progress to eliminate CVE-2026-109618 / CVE-2026-109619 heap UAF primitives.',
                'changelog' => "## v1.17.1\n\n- Security: Disabled `session.upload_progress.enabled` to mitigate CVE-2026-109618 and CVE-2026-109619.",
                'supported_channels' => $rel170?->supported_channels ?? $supportedChannels,
                'config_schemas' => $rel170?->config_schemas ?? $schema,
                'upgrade_commands' => [],
            ]
        );
        $this->info("✔ Registered {$patch171->version_tag} (ID: {$patch171->id})");

        // 2. Create or update v1.18.1
        $patch181 = ApisHubRelease::updateOrCreate(
            ['version_tag' => 'v1.18.1'],
            [
                'is_active' => true,
                'is_default' => true, // Make v1.18.1 the default for future projects
                'description' => 'Security Hotfix: Disables session.upload_progress to eliminate CVE-2026-109618 / CVE-2026-109619 heap UAF primitives.',
                'changelog' => "## v1.18.1\n\n- Security: Disabled `session.upload_progress.enabled` to mitigate CVE-2026-109618 and CVE-2026-109619.",
                'supported_channels' => $rel180?->supported_channels ?? $supportedChannels,
                'config_schemas' => $rel180?->config_schemas ?? $schema,
                'upgrade_commands' => [],
            ]
        );
        $this->info("✔ Registered {$patch181->version_tag} (ID: {$patch181->id})");

        // Unset is_default on older releases if v1.18.1 is default
        ApisHubRelease::where('id', '!=', $patch181->id)->where('is_default', true)->update(['is_default' => false]);

        if ($this->option('dispatch')) {
            $this->info('Dispatching queued upgrade jobs for deployed tenants...');

            // Upgrades for 1.17.0 -> 1.17.1
            $projects17 = Project::whereNotNull('deployed_at')
                ->where('apis_hub_release_id', $rel170?->id)
                ->get();

            foreach ($projects17 as $project) {
                UpgradeProjectReleaseJob::dispatch($project, $patch171);
                $this->line(" -> Queued upgrade to v1.17.1 for project: {$project->name} ({$project->subdomain})");
            }

            // Upgrades for 1.18.0 -> 1.18.1
            $projects18 = Project::whereNotNull('deployed_at')
                ->where('apis_hub_release_id', $rel180?->id)
                ->get();

            foreach ($projects18 as $project) {
                UpgradeProjectReleaseJob::dispatch($project, $patch181);
                $this->line(" -> Queued upgrade to v1.18.1 for project: {$project->name} ({$project->subdomain})");
            }

            $total = $projects17->count() + $projects18->count();
            $this->info("Successfully dispatched {$total} tenant upgrade job(s).");
        } else {
            $this->comment('Tip: To automatically dispatch upgrades for all tenants, run with: --dispatch');
        }

        return Command::SUCCESS;
    }
}
