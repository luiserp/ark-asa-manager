<?php

namespace Database\Seeders;

use App\Models\RconCommand;
use Illuminate\Database\Seeder;

class RconCommandSeeder extends Seeder
{
    public function run(): void
    {
        $commands = [
            // Server Management
            ['name' => 'Save World', 'command' => 'SaveWorld', 'category' => 'server', 'description' => 'Forces the server to save the game world to disk', 'sort_order' => 1],
            ['name' => 'Destroy Wild Dinos', 'command' => 'DestroyWildDinos', 'category' => 'server', 'description' => 'Destroys all wild dinosaurs on the map (forces respawn)', 'sort_order' => 2],
            ['name' => 'Set Time of Day', 'command' => 'SetTimeOfDay 12:00:00', 'category' => 'server', 'description' => 'Sets the in-game time (format HH:MM:SS)', 'sort_order' => 3],
            ['name' => 'List Players', 'command' => 'ListPlayers', 'category' => 'server', 'description' => 'Lists all currently connected players', 'sort_order' => 4],
            ['name' => 'Get Chat', 'command' => 'GetChat', 'category' => 'server', 'description' => 'Returns the latest chat messages from the server', 'sort_order' => 5],
            ['name' => 'Server Chat', 'command' => 'ServerChat Hello from admin', 'category' => 'server', 'description' => 'Sends a chat message to all players from the server', 'sort_order' => 6],
            ['name' => 'Broadcast', 'command' => 'Broadcast Server maintenance in 10 minutes', 'category' => 'server', 'description' => 'Displays a large message on all players screens', 'sort_order' => 7],
            ['name' => 'Do Exit', 'command' => 'DoExit', 'category' => 'server', 'description' => 'Shuts down the server immediately', 'sort_order' => 8],

            // Player Management
            ['name' => 'Kick Player', 'command' => 'KickPlayer <SteamID>', 'category' => 'players', 'description' => 'Kicks a player by their Steam ID', 'sort_order' => 10],
            ['name' => 'Ban Player', 'command' => 'BanPlayer <SteamID>', 'category' => 'players', 'description' => 'Bans a player by their Steam ID', 'sort_order' => 11],
            ['name' => 'Unban Player', 'command' => 'UnbanPlayer <SteamID>', 'category' => 'players', 'description' => 'Unbans a previously banned player', 'sort_order' => 12],
            ['name' => 'White List Player', 'command' => 'AllowPlayerToJoinNoCheck <SteamID>', 'category' => 'players', 'description' => 'Adds a player to the server whitelist', 'sort_order' => 13],
            ['name' => 'Remove Whitelist', 'command' => 'DisallowPlayerToJoinNoCheck <SteamID>', 'category' => 'players', 'description' => 'Removes a player from the server whitelist', 'sort_order' => 14],

            // Dino & Spawning
            ['name' => 'Destroy All Enemies', 'command' => 'DestroyAllEnemies', 'category' => 'dinos', 'description' => 'Destroys all hostile creatures', 'sort_order' => 20],
            ['name' => 'Force Tame Range', 'command' => 'DoTame', 'category' => 'dinos', 'description' => 'Force tames the targeted dinosaur', 'sort_order' => 21],

            // World
            ['name' => 'Start Rain', 'command' => 'ce start_rain', 'category' => 'world', 'description' => 'Triggers a rain weather event', 'sort_order' => 30],
            ['name' => 'Stop Rain', 'command' => 'ce stop_rain', 'category' => 'world', 'description' => 'Stops the current rain weather event', 'sort_order' => 31],
            ['name' => 'Start Fog', 'command' => 'ce start_fog', 'category' => 'world', 'description' => 'Triggers a fog weather event', 'sort_order' => 32],
            ['name' => 'Stop Fog', 'command' => 'ce stop_fog', 'category' => 'world', 'description' => 'Stops the current fog weather event', 'sort_order' => 33],
            ['name' => 'Destroy Structures', 'command' => 'DestroyStructures', 'category' => 'world', 'description' => 'Destroys all structures owned by all players', 'sort_order' => 34],

            // Info & Debug
            ['name' => 'Show Message of the Day', 'command' => 'ShowMessageOfTheDay', 'category' => 'info', 'description' => 'Displays the current message of the day', 'sort_order' => 40],
            ['name' => 'Set Message of the Day', 'command' => 'SetMessageOfTheDay Welcome to the server!', 'category' => 'info', 'description' => 'Sets the server message of the day', 'sort_order' => 41],
            ['name' => 'Get Game Log', 'command' => 'GetGameLog', 'category' => 'info', 'description' => 'Returns the server game log', 'sort_order' => 42],
        ];

        foreach ($commands as $command) {
            RconCommand::updateOrCreate(
                ['command' => $command['command']],
                $command,
            );
        }
    }
}
