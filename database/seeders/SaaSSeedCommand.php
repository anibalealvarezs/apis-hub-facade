<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Project;
use App\Models\Server;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SaaSSeedCommand extends Seeder
{
    public function run(): void
    {
        // 1. Elevate first user to Admin
        $admin = User::first();
        if ($admin) {
            $admin->update(['is_admin' => true, 'is_active' => true]);
            echo "✅ Admin Account Ready: {$admin->email}\n";
        }

        // 2. Create a Server if none exists
        $server = Server::firstOrCreate(
            ['ip_address' => '127.0.0.1'],
            [
                'name' => 'Demo Hetzner Node',
                'ssh_user' => 'root',
                'ssh_private_key' => 'fake-key',
                'ssh_port' => 22,
                'is_ready' => true
            ]
        );

        // 3. Create a Demo Standard User
        $client = User::firstOrCreate(
            ['email' => 'client@test.com'],
            [
                'name' => 'Demo Client Account',
                'password' => Hash::make('password'),
                'is_admin' => false,
                'is_active' => true
            ]
        );
        echo "✅ Demo Client: client@test.com / password\n";

        // 4. Create a Demo Instance for the Client
        $instance = Project::updateOrCreate(
            ['subdomain' => 'alpha'],
            [
                'name' => 'Instance Alpha',
                'server_id' => $server->id,
                'user_id' => $client->id,
                'git_repo' => 'https://github.com/anibalealvarezs/apis-hub.git',
                'git_branch' => 'main',
                'is_active' => true,
                'health_status' => 'online',
                'monitoring_token' => \Illuminate\Support\Str::uuid(),
                'health_metrics' => [
                    'channels' => ['facebook' => true, 'google' => false],
                    'catalog' => ['campaigns' => 42, 'posts' => 128, 'queries' => 1024, 'pages' => 5]
                ]
            ]
        );
        echo "✅ Demo Instance Assigned to Client: Instance Alpha\n";

        // 5. Create a Demo Instance for the Admin (You!)
        Project::updateOrCreate(
            ['subdomain' => 'admin-node'],
            [
                'name' => 'Admin Control Node',
                'server_id' => $server->id,
                'user_id' => $admin->id,
                'git_repo' => 'https://github.com/anibalealvarezs/apis-hub.git',
                'git_branch' => 'main',
                'is_active' => true,
                'health_status' => 'online',
                'monitoring_token' => \Illuminate\Support\Str::uuid(),
                'health_metrics' => [
                    'channels' => ['facebook' => true, 'google' => true],
                    'catalog' => ['campaigns' => 10, 'posts' => 50, 'queries' => 200, 'pages' => 2]
                ]
            ]
        );
        echo "✅ Admin Instance Assigned: admin-node\n";
    }
}
