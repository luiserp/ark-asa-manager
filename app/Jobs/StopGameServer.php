<?php

namespace App\Jobs;

use App\Events\ServerTaskCompleted;
use App\Events\ServerTaskOutput;
use App\Models\GameServer;
use App\Services\Ark\ArkServerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class StopGameServer implements ShouldQueue
{
    use Queueable;

    public int $timeout = 60;

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
            $result = $ark->stop();

            broadcast(new ServerTaskOutput(
                serverId: $this->serverId,
                task: 'stop',
                line: $result['stdout'] ?: 'Stop command sent.',
                type: 'stdout',
            ));

            if ($result['stderr']) {
                broadcast(new ServerTaskOutput(
                    serverId: $this->serverId,
                    task: 'stop',
                    line: $result['stderr'],
                    type: 'stderr',
                ));
            }

            broadcast(new ServerTaskCompleted(
                serverId: $this->serverId,
                task: 'stop',
                success: $result['exit_code'] === 0,
                exitCode: $result['exit_code'],
            ));
        } catch (\Throwable $e) {
            broadcast(new ServerTaskOutput(
                serverId: $this->serverId,
                task: 'stop',
                line: "Error: {$e->getMessage()}",
                type: 'stderr',
            ));

            broadcast(new ServerTaskCompleted(
                serverId: $this->serverId,
                task: 'stop',
                success: false,
                exitCode: -1,
            ));

            throw $e;
        }
    }
}
