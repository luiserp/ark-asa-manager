<?php

namespace Database\Factories;

use App\Models\GameServer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameServer>
 */
class GameServerFactory extends Factory
{
    protected $model = GameServer::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true).' Server',
            'host' => fake()->ipv4(),
            'port' => 22,
            'username' => 'ark-sa',
            'ssh_key_path' => '/shared-ssh/id_ed25519',
            'server_dir' => '/ark-server/server',
            'backups_dir' => '/ark-server/backups',
            'logs_dir' => '/ark-server/logs',
            'binary_path' => 'ShooterGame/Binaries/Win64/ArkAscendedServer.exe',
            'proton_path' => '/opt/glorious_eggroll/proton/bin/wine64',
            'steam_app_id' => '2430930',
            'is_active' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['is_active' => true]);
    }

    public function localDocker(): static
    {
        return $this->state(fn () => [
            'host' => 'ark-server',
            'port' => 22,
            'ssh_key_path' => '/shared-ssh/id_ed25519',
        ]);
    }
}
