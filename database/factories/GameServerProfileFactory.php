<?php

namespace Database\Factories;

use App\Models\GameServer;
use App\Models\GameServerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameServerProfile>
 */
class GameServerProfileFactory extends Factory
{
    protected $model = GameServerProfile::class;

    public function definition(): array
    {
        return [
            'game_server_id' => GameServer::factory(),
            'name' => fake()->words(2, true).' Profile',
            'map' => fake()->randomElement(array_keys(config('ark.maps', ['TheIsland_WP' => 'The Island']))),
            'game_port' => 7777,
            'query_port' => 27015,
            'rcon_port' => 27020,
            'session_name' => fake()->words(3, true),
            'admin_password' => 'secret',
            'server_password' => null,
            'max_players' => 70,
            'extra_args' => null,
            'is_active' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['is_active' => true]);
    }
}
