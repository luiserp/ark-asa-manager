<?php

namespace App\Jobs;

use App\Events\ServerTaskCompleted;
use App\Events\ServerTaskOutput;
use App\Models\GameServer;
use App\Models\GameServerProfile;
use App\Services\Ark\ArkServerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class StartGameServer implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public int $tries = 1;

    public function __construct(
        public readonly string $serverId,
        public readonly ?string $profileId = null,
    ) {
        $this->onQueue('ark-tasks');
    }

    public function handle(): void
    {
        $server = GameServer::findOrFail($this->serverId);
        $ark = ArkServerService::forServer($server);

        try {
            $profile = $this->profileId
                ? GameServerProfile::find($this->profileId)
                : GameServerProfile::where('game_server_id', $this->serverId)->active()->first();

            $map = $profile?->map ?? 'TheIsland_WP';
            $options = [
                'SessionName' => $profile?->session_name ?? 'ARK Server',
                'Port' => (string) ($profile?->game_port ?? 7777),
                'QueryPort' => (string) ($profile?->query_port ?? 27015),
                'RCONPort' => (string) ($profile?->rcon_port ?? 27020),
                'RCONEnabled' => 'True',
            ];

            $this->emit("Starting server with map: {$map}");
            $this->emit('Starting server with options: '.json_encode($options));

            if ($profile?->admin_password) {
                $options['ServerAdminPassword'] = $profile->admin_password;
            }

            if ($profile?->server_password) {
                $options['ServerPassword'] = $profile->server_password;
            }

            if ($profile?->max_players) {
                $options['MaxPlayers'] = (string) $profile->max_players;
            }

            $flags = [];
            if ($profile && ! $profile->battleye_enabled) {
                $flags[] = '-NoBattlEye';
            }

            if ($profile?->mods) {
                $enabledMods = collect($profile->mods)
                    ->filter(fn (array $mod): bool => ($mod['is_enabled'] ?? true));

                $activeMods = $enabledMods
                    ->filter(fn (array $mod): bool => ! $mod['is_passive'])
                    ->pluck('mod_id')
                    ->implode(',');

                $passiveMods = $enabledMods
                    ->filter(fn (array $mod): bool => $mod['is_passive'])
                    ->pluck('mod_id')
                    ->implode(',');

                if ($activeMods) {
                    $flags[] = "-mods={$activeMods}";
                }

                if ($passiveMods) {
                    $flags[] = "-passivemods={$passiveMods}";
                }
            }

            $this->emit('Starting server with flags: '.json_encode($flags));

            $result = $ark->start($map, $options, $flags);

            if ($result['exit_code'] !== 0) {
                $this->emit($result['stderr'] ?: 'Start command failed.', 'stderr');
                $this->finish(false, $result['exit_code']);

                return;
            }

            $pid = trim($result['stdout']);
            $this->emit("Start command sent (PID: {$pid}). Waiting for process...");

            if ($result['stderr']) {
                $this->emit($result['stderr'], 'stderr');
            }

            sleep(5);

            $status = $ark->status();

            if ($status['running']) {
                $this->emit("Server process confirmed running (PID: {$status['pid']}).");
                $this->finish(true, 0);
            } else {
                $logResult = $ark->consoleLogs(20);
                $logTail = trim($logResult['stdout'] ?? '');

                $this->emit('Server process is NOT running after launch. It may have crashed.', 'stderr');

                if ($logTail && $logTail !== 'No console logs available.') {
                    $this->emit("Last console output:\n{$logTail}", 'stderr');
                }

                $this->finish(false, 1);
            }
        } catch (\Throwable $e) {
            Log::error("Error starting game server {$this->serverId}: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString(),
            ]);

            $this->emit("Error: {$e->getMessage()}", 'stderr');
            $this->finish(false, -1);

            throw $e;
        }
    }

    private function emit(string $line, string $type = 'stdout'): void
    {
        broadcast(new ServerTaskOutput(
            serverId: $this->serverId,
            task: 'start',
            line: $line,
            type: $type,
        ));
    }

    private function finish(bool $success, int $exitCode): void
    {
        broadcast(new ServerTaskCompleted(
            serverId: $this->serverId,
            task: 'start',
            success: $success,
            exitCode: $exitCode,
        ));
    }
}
