<?php

namespace Database\Factories;

use App\Models\Server;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServerFactory extends Factory
{
    protected $model = Server::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word() . ' Server',
            'ip_address' => $this->faker->ipv4(),
            'ssh_user' => 'ubuntu',
            'is_ready' => true,
        ];
    }
}
