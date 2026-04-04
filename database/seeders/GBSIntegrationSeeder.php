<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Project;
use App\Models\Server;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GBSIntegrationSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();
        $server = Server::first();

        $token = '40620beb-d5e9-4dc4-b0a5-c06a6e357c60';

        Project::updateOrCreate(
            ['subdomain' => 'gbs'],
            [
                'name' => 'Live Instance: GBS',
                'user_id' => $admin->id,
                'server_id' => $server->id,
                'git_repo' => 'https://github.com/anibalealvarezs/apis-hub.git',
                'git_branch' => 'main',
                'monitoring_token' => $token,
                'remote_admin_api_key' => 'YssOwY5OEKivaDPPRRxhJG1TgfqZilKuR3yWUPF6pWfU84mYcZvPkRAqGYVRTiPZ',
                'remote_app_api_key' => 'aeHRzZMB2wgJvHNG5Xh0LCipxOx9gBVu',
                'is_active' => true,
                'health_status' => 'waiting',
                'health_metrics' => [
                    'channels' => ['facebook' => false, 'google' => false],
                    'catalog' => ['campaigns' => 0, 'posts' => 0, 'queries' => 0]
                ]
            ]
        );

        echo "\n🚀 GBS INSTANCE ACTIVATED!\n";
        echo "--------------------------\n";
        echo "MONITOR_TOKEN=" . $token . "\n";
        echo "--------------------------\n";
    }
}
