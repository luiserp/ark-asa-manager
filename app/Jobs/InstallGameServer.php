<?php

namespace App\Jobs;

use App\Events\ServerTaskCompleted;
use App\Events\ServerTaskOutput;
use App\Models\GameServer;
use App\Services\Ark\ArkServerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class InstallGameServer implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public int $tries = 1;

    public function __construct(
        public readonly string $serverId,
    ) {
        $this->onQueue('ark-tasks');
    }

    public function handle(): void
    {
        $server = GameServer::findOrFail($this->serverId);
        $ark = ArkServerService::forServer($server);

        try {
            $exitCode = $ark->installOrUpdateStreaming(function (string $line, string $type) {
                broadcast(new ServerTaskOutput(
                    serverId: $this->serverId,
                    task: 'install',
                    line: $line,
                    type: $type,
                ));
            });

            broadcast(new ServerTaskCompleted(
                serverId: $this->serverId,
                task: 'install',
                success: $exitCode === 0,
                exitCode: $exitCode,
            ));
        } catch (\Throwable $e) {
            broadcast(new ServerTaskOutput(
                serverId: $this->serverId,
                task: 'install',
                line: "Error: {$e->getMessage()}",
                type: 'stderr',
            ));

            broadcast(new ServerTaskCompleted(
                serverId: $this->serverId,
                task: 'install',
                success: false,
                exitCode: -1,
            ));

            throw $e;
        }
    }
}
