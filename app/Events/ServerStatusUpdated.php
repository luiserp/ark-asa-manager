<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServerStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $serverId,
        public bool $installed,
        public bool $running,
        public ?int $pid,
        public ?string $uptime,
        public ?string $error = null,
        public ?float $cpuPercent = null,
        public ?int $memoryUsed = null,
        public ?int $memoryTotal = null,
        public ?string $diskUsage = null,
        public ?int $players = null,
        public ?int $maxPlayers = null,
    ) {}

    public function broadcastAs(): string
    {
        return 'ServerStatusUpdated';
    }

    /**
     * @return array<int, PresenceChannel|PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel("server.{$this->serverId}"),
            new PrivateChannel("server-status.{$this->serverId}"),
        ];
    }
}
