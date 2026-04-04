<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use App\Models\Server;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'subdomain' => $this->faker->unique()->slug(1),
            'user_id' => User::factory(),
            'server_id' => Server::factory(),
            'is_active' => true,
            'git_repo' => 'anibalealvarezs/apis-hub',
            'git_branch' => 'main',
            'monitoring_token' => (string) Str::uuid(),
            'remote_admin_api_key' => bin2hex(random_bytes(32)),
            'health_status' => 'offline',
        ];
    }
}
