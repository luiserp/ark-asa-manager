<?php

namespace App\Jobs;

use App\Events\ServerStatusUpdated;
use App\Events\ServerTaskCompleted;
use App\Events\ServerTaskOutput;
use App\Models\GameServer;
use App\Models\GameServerProfile;
use App\Services\Ark\ArkServerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ServerTaskJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(
        public GameServer $server,
        public string $task,
        public ?string $profileId = null,
    ) {}

    public function handle(): void
    {
        if ($this->task === 'status') {
            $this->runStatus();

            return;
        }

        if ($this->task === 'logs') {
            $this->runLogs();

            return;
        }

        $ark = ArkServerService::forServer($this->server);

        $exitCode = match ($this->task) {
            'install' => $this->runInstall($ark),
            'start' => $this->runStart($ark),
            'stop' => $this->runStop($ark),
            default => -1,
        };

        broadcast(new ServerTaskCompleted(
            serverId: $this->server->id,
            task: $this->task,
            success: $exitCode === 0,
            exitCode: $exitCode,
        ));
    }

    private function runStatus(): void
    {
        try {
            $ark = ArkServerService::forServer($this->server);
            $status = $ark->status();
            $installed = $ark->isInstalled();
            $metrics = $ark->systemMetrics();
            $disk = $ark->diskUsage();

            broadcast(new ServerStatusUpdated(
                serverId: $this->server->id,
                installed: $installed,
                running: $status['running'],
                pid: $status['pid'],
                uptime: $status['uptime'],
                cpuPercent: $metrics['cpu'],
                memoryUsed: $metrics['memory_used'],
                memoryTotal: $metrics['memory_total'],
                diskUsage: $disk['server'],
                players: $status['running'] ? $metrics['players'] : null,
                maxPlayers: $this->getMaxPlayers(),
            ));
        } catch (\Throwable $e) {
            broadcast(new ServerStatusUpdated(
                serverId: $this->server->id,
                installed: false,
                running: false,
                pid: null,
                uptime: null,
                error: $e->getMessage(),
            ));
        }
    }

    private function getMaxPlayers(): ?int
    {
        $profile = GameServerProfile::where('game_server_id', $this->server->id)
            ->active()
            ->first();

        return $profile?->max_players;
    }

    private function runLogs(): void
    {
        try {
            $ark = ArkServerService::forServer($this->server);
            $result = $ark->logs(100);

            $lines = explode("\n", $result['stdout'] ?: 'No logs available.');
            $chunks = array_chunk($lines, 20);

            foreach ($chunks as $chunk) {
                broadcast(new ServerTaskOutput(
                    serverId: $this->server->id,
                    task: 'logs',
                    line: implode("\n", $chunk),
                    type: 'stdout',
                ));
            }
        } catch (\Throwable $e) {
            broadcast(new ServerTaskOutput(
                serverId: $this->server->id,
                task: 'logs',
                line: "Error: {$e->getMessage()}",
                type: 'stderr',
            ));
        }

        broadcast(new ServerTaskCompleted(
            serverId: $this->server->id,
            task: 'logs',
            success: true,
            exitCode: 0,
        ));
    }

    private function runInstall(ArkServerService $ark): int
    {
        return $ark->installOrUpdateStreaming(function (string $line, string $type) {
            broadcast(new ServerTaskOutput(
                serverId: $this->server->id,
                task: $this->task,
                line: $line,
                type: $type,
            ));
        });
    }

    private function runStart(ArkServerService $ark): int
    {
        $profile = $this->profileId
            ? GameServerProfile::find($this->profileId)
            : GameServerProfile::where('game_server_id', $this->server->id)->active()->first();

        $map = $profile?->map ?? 'TheIsland_WP';
        $options = [
            'SessionName' => $profile?->session_name ?? 'ARK Server',
            'Port' => (string) ($profile?->game_port ?? 7777),
            'QueryPort' => (string) ($profile?->query_port ?? 27015),
            'RCONPort' => (string) ($profile?->rcon_port ?? 27020),
            'RCONEnabled' => 'True',
        ];

        if ($profile?->admin_password) {
            $options['ServerAdminPassword'] = $profile->admin_password;
        }

        if ($profile?->server_password) {
            $options['ServerPassword'] = $profile->server_password;
        }

        if ($profile?->max_players) {
            $options['MaxPlayers'] = (string) $profile->max_players;
        }

        $result = $ark->start($map, $options);

        broadcast(new ServerTaskOutput(
            serverId: $this->server->id,
            task: $this->task,
            line: $result['stdout'] ?: 'Server start command sent.',
            type: 'stdout',
        ));

        if ($result['stderr']) {
            broadcast(new ServerTaskOutput(
                serverId: $this->server->id,
                task: $this->task,
                line: $result['stderr'],
                type: 'stderr',
            ));
        }

        return $result['exit_code'];
    }

    private function runStop(ArkServerService $ark): int
    {
        $result = $ark->stop();

        broadcast(new ServerTaskOutput(
            serverId: $this->server->id,
            task: $this->task,
            line: $result['stdout'] ?: 'Stop command sent.',
            type: 'stdout',
        ));

        if ($result['stderr']) {
            broadcast(new ServerTaskOutput(
                serverId: $this->server->id,
                task: $this->task,
                line: $result['stderr'],
                type: 'stderr',
            ));
        }

        return $result['exit_code'];
    }
}
