<?php

namespace App\Console\Commands;

use App\Events\ServerConsoleUpdated;
use App\Events\ServerLogsUpdated;
use App\Events\ServerStatusUpdated;
use App\Events\ServerTaskCompleted;
use App\Events\ServerTaskOutput;
use App\Models\GameServer;
use App\Models\GameServerProfile;
use App\Services\Ark\ArkServerService;
use Clue\React\Redis\Factory as RedisFactory;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use React\EventLoop\Loop;

#[Signature('app:ark-asa-manager')]
#[Description('Long-running daemon that manages ARK servers with persistent SSH connections')]
class ArkAsaManager extends Command
{
    /** @var array<string, ArkServerService> */
    private array $connections = [];

    /** @var array<string, list<int>> */
    private array $watchers = [];

    /** @var array<string, float> */
    private array $lastStatusAt = [];

    /** @var array<string, float> */
    private array $lastActivityAt = [];

    /** @var array<string, string> */
    private array $lastLogHash = [];

    /** @var array<string, string> */
    private array $lastConsoleHash = [];

    /** @var array<string, bool> */
    private array $serverRunning = [];

    private int $statusInterval;

    private int $inactivityTimeout;

    public function handle(): void
    {
        $this->statusInterval = (int) config('ark.manager.status_interval', 5);
        $this->inactivityTimeout = (int) config('ark.manager.subscription_check_interval', 30) * 4;

        $this->info('ARK ASA Manager starting...');
        $this->info("Status interval: {$this->statusInterval}s | Inactivity timeout: {$this->inactivityTimeout}s");

        $this->setupSignalHandlers();

        $redisHost = config('database.redis.default.host', '127.0.0.1');
        $redisPort = config('database.redis.default.port', 6379);
        $redisPassword = config('database.redis.default.password');
        $redisDb = config('database.redis.default.database', 0);
        $redisPrefix = config('database.redis.options.prefix', '');

        $auth = $redisPassword && $redisPassword !== 'null' ? "{$redisPassword}@" : '';
        $uri = "redis://{$auth}{$redisHost}:{$redisPort}/{$redisDb}";

        $factory = new RedisFactory;
        $subscriber = $factory->createLazyClient($uri);

        $commandsChannel = $redisPrefix.'ark:commands';
        $subscriptionsChannel = $redisPrefix.'ark:subscriptions';

        $subscriber->subscribe($commandsChannel);
        $subscriber->subscribe($subscriptionsChannel);

        $this->info("Subscribing to: {$commandsChannel}, {$subscriptionsChannel}");

        $subscriber->on('message', function (string $channel, string $message) use ($commandsChannel, $subscriptionsChannel) {
            $data = json_decode($message, true);
            if (! $data) {
                return;
            }

            match ($channel) {
                $commandsChannel => $this->handleCommand($data),
                $subscriptionsChannel => $this->handleSubscription($data),
                default => null,
            };
        });

        $subscriber->on('error', function (\Exception $e) {
            $this->error("Redis subscriber error: {$e->getMessage()}");
        });

        Loop::addPeriodicTimer($this->statusInterval, fn () => $this->monitorActiveServers());

        Loop::addPeriodicTimer(60, fn () => $this->cleanupInactiveServers());

        $this->info('ARK ASA Manager is running. Listening for commands...');

        Loop::run();
    }

    /**
     * @param  array{serverId: string, task: string, profileId?: string|null}  $data
     */
    private function handleCommand(array $data): void
    {
        $serverId = $data['serverId'] ?? null;
        $task = $data['task'] ?? null;

        if (! $serverId || ! $task) {
            return;
        }

        $this->info("[CMD] Server {$serverId}: {$task}");
        $this->lastActivityAt[$serverId] = microtime(true);

        match ($task) {
            'install' => $this->runInstall($serverId),
            'start' => $this->runStart($serverId, $data['profileId'] ?? null),
            'stop' => $this->runStop($serverId),
            'logs' => $this->runLogs($serverId),
            default => $this->warn("[CMD] Unknown task: {$task}"),
        };
    }

    /**
     * @param  array{serverId: string, action: string, userId?: int|string}  $data
     */
    private function handleSubscription(array $data): void
    {
        $serverId = $data['serverId'] ?? null;
        $action = $data['action'] ?? null;
        $userId = $data['userId'] ?? null;

        if (! $serverId || ! $action) {
            return;
        }

        $this->lastActivityAt[$serverId] = microtime(true);

        if ($action === 'join' && $userId) {
            $this->watchers[$serverId] ??= [];
            if (! in_array((int) $userId, $this->watchers[$serverId])) {
                $this->watchers[$serverId][] = (int) $userId;
            }
            $this->info("[SUB] User {$userId} watching server {$serverId} (total: ".count($this->watchers[$serverId]).')');

            $this->pushImmediateStatus($serverId);
        } elseif ($action === 'leave' && $userId) {
            $this->watchers[$serverId] = array_values(
                array_filter($this->watchers[$serverId] ?? [], fn ($id) => $id !== (int) $userId)
            );
            $this->info("[SUB] User {$userId} left server {$serverId} (remaining: ".count($this->watchers[$serverId]).')');

            if (empty($this->watchers[$serverId])) {
                unset($this->watchers[$serverId]);
                $this->releaseConnection($serverId);
            }
        }
    }

    private function monitorActiveServers(): void
    {
        foreach (array_keys($this->watchers) as $serverId) {
            if (empty($this->watchers[$serverId])) {
                continue;
            }

            $this->pushStatus($serverId);
            $this->pushLogs($serverId);
            $this->pushConsoleLogs($serverId);
        }
    }

    private function cleanupInactiveServers(): void
    {
        $now = microtime(true);

        foreach (array_keys($this->watchers) as $serverId) {
            $lastActivity = $this->lastActivityAt[$serverId] ?? 0;

            if (($now - $lastActivity) > $this->inactivityTimeout) {
                $this->info("[CLEANUP] Server {$serverId} inactive for >{$this->inactivityTimeout}s, releasing");
                unset($this->watchers[$serverId]);
                $this->releaseConnection($serverId);
                unset($this->lastActivityAt[$serverId]);
                unset($this->lastStatusAt[$serverId]);
            }
        }
    }

    private function pushImmediateStatus(string $serverId): void
    {
        $this->pushStatus($serverId, force: true);
        $this->pushLogs($serverId, force: true);
        $this->pushConsoleLogs($serverId, force: true);
    }

    private function pushStatus(string $serverId, bool $force = false): void
    {
        $now = microtime(true);
        $last = $this->lastStatusAt[$serverId] ?? 0;

        if (! $force && ($now - $last) < $this->statusInterval) {
            return;
        }

        $this->lastStatusAt[$serverId] = $now;

        try {
            $ark = $this->getConnection($serverId);
            $status = $ark->status();
            $installed = $ark->isInstalled();
            $metrics = $ark->systemMetrics();
            $disk = $ark->diskUsage();

            $this->serverRunning[$serverId] = $status['running'];

            broadcast(new ServerStatusUpdated(
                serverId: $serverId,
                installed: $installed,
                running: $status['running'],
                pid: $status['pid'],
                uptime: $status['uptime'],
                cpuPercent: $metrics['cpu'],
                memoryUsed: $metrics['memory_used'],
                memoryTotal: $metrics['memory_total'],
                diskUsage: $disk['server'],
                players: $status['running'] ? $metrics['players'] : null,
                maxPlayers: $this->getMaxPlayers($serverId),
            ));
        } catch (\Throwable $e) {
            $this->error("[STATUS] Error for server {$serverId}: {$e->getMessage()}");
            $this->serverRunning[$serverId] = false;

            $this->reconnect($serverId);

            broadcast(new ServerStatusUpdated(
                serverId: $serverId,
                installed: false,
                running: false,
                pid: null,
                uptime: null,
                error: $e->getMessage(),
            ));
        }
    }

    private function pushLogs(string $serverId, bool $force = false): void
    {
        if (! ($this->serverRunning[$serverId] ?? false)) {
            return;
        }

        try {
            $ark = $this->getConnection($serverId);
            $result = $ark->logs(50);
            $output = $result['stdout'] ?: '';

            $hash = md5($output);
            if (! $force && isset($this->lastLogHash[$serverId]) && $this->lastLogHash[$serverId] === $hash) {
                return;
            }

            $this->lastLogHash[$serverId] = $hash;

            if ($output) {
                broadcast(new ServerLogsUpdated(
                    serverId: $serverId,
                    lines: $output,
                ));
            }
        } catch (\Throwable) {
            // Logs are best-effort, don't break the monitoring loop
        }
    }

    private function pushConsoleLogs(string $serverId, bool $force = false): void
    {
        if (! ($this->serverRunning[$serverId] ?? false)) {
            return;
        }

        try {
            $ark = $this->getConnection($serverId);
            $result = $ark->consoleLogs(80);
            $output = $result['stdout'] ?: '';

            $hash = md5($output);
            if (! $force && isset($this->lastConsoleHash[$serverId]) && $this->lastConsoleHash[$serverId] === $hash) {
                return;
            }

            $this->lastConsoleHash[$serverId] = $hash;

            if ($output) {
                broadcast(new ServerConsoleUpdated(
                    serverId: $serverId,
                    lines: $output,
                ));
            }
        } catch (\Throwable) {
            // Console logs are best-effort
        }
    }

    private function runLogs(string $serverId): void
    {
        try {
            $ark = $this->getConnection($serverId);
            $result = $ark->logs(100);

            $lines = explode("\n", $result['stdout'] ?: 'No logs available.');
            $chunks = array_chunk($lines, 20);

            foreach ($chunks as $chunk) {
                broadcast(new ServerTaskOutput(
                    serverId: $serverId,
                    task: 'logs',
                    line: implode("\n", $chunk),
                    type: 'stdout',
                ));
            }
        } catch (\Throwable $e) {
            broadcast(new ServerTaskOutput(
                serverId: $serverId,
                task: 'logs',
                line: "Error: {$e->getMessage()}",
                type: 'stderr',
            ));
            $this->reconnect($serverId);
        }

        broadcast(new ServerTaskCompleted(
            serverId: $serverId,
            task: 'logs',
            success: true,
            exitCode: 0,
        ));
    }

    private function runInstall(string $serverId): void
    {
        try {
            $ark = $this->getConnection($serverId);

            $exitCode = $ark->installOrUpdateStreaming(function (string $line, string $type) use ($serverId) {
                broadcast(new ServerTaskOutput(
                    serverId: $serverId,
                    task: 'install',
                    line: $line,
                    type: $type,
                ));
            });

            broadcast(new ServerTaskCompleted(
                serverId: $serverId,
                task: 'install',
                success: $exitCode === 0,
                exitCode: $exitCode,
            ));
        } catch (\Throwable $e) {
            $this->error("[INSTALL] Error for server {$serverId}: {$e->getMessage()}");
            $this->reconnect($serverId);

            broadcast(new ServerTaskOutput(
                serverId: $serverId,
                task: 'install',
                line: "Error: {$e->getMessage()}",
                type: 'stderr',
            ));

            broadcast(new ServerTaskCompleted(
                serverId: $serverId,
                task: 'install',
                success: false,
                exitCode: -1,
            ));
        }
    }

    private function runStart(string $serverId, ?string $profileId): void
    {
        try {
            $ark = $this->getConnection($serverId);

            $profile = $profileId
                ? GameServerProfile::find($profileId)
                : GameServerProfile::where('game_server_id', $serverId)->active()->first();

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

            $result = $ark->start($map, $options, $flags);

            broadcast(new ServerTaskOutput(
                serverId: $serverId,
                task: 'start',
                line: $result['stdout'] ?: 'Server start command sent.',
                type: 'stdout',
            ));

            if ($result['stderr']) {
                broadcast(new ServerTaskOutput(
                    serverId: $serverId,
                    task: 'start',
                    line: $result['stderr'],
                    type: 'stderr',
                ));
            }

            broadcast(new ServerTaskCompleted(
                serverId: $serverId,
                task: 'start',
                success: $result['exit_code'] === 0,
                exitCode: $result['exit_code'],
            ));
        } catch (\Throwable $e) {
            $this->error("[START] Error for server {$serverId}: {$e->getMessage()}");
            $this->reconnect($serverId);

            broadcast(new ServerTaskOutput(
                serverId: $serverId,
                task: 'start',
                line: "Error: {$e->getMessage()}",
                type: 'stderr',
            ));

            broadcast(new ServerTaskCompleted(
                serverId: $serverId,
                task: 'start',
                success: false,
                exitCode: -1,
            ));
        }
    }

    private function runStop(string $serverId): void
    {
        try {
            $ark = $this->getConnection($serverId);
            $result = $ark->stop();

            broadcast(new ServerTaskOutput(
                serverId: $serverId,
                task: 'stop',
                line: $result['stdout'] ?: 'Stop command sent.',
                type: 'stdout',
            ));

            if ($result['stderr']) {
                broadcast(new ServerTaskOutput(
                    serverId: $serverId,
                    task: 'stop',
                    line: $result['stderr'],
                    type: 'stderr',
                ));
            }

            broadcast(new ServerTaskCompleted(
                serverId: $serverId,
                task: 'stop',
                success: $result['exit_code'] === 0,
                exitCode: $result['exit_code'],
            ));
        } catch (\Throwable $e) {
            $this->error("[STOP] Error for server {$serverId}: {$e->getMessage()}");
            $this->reconnect($serverId);

            broadcast(new ServerTaskOutput(
                serverId: $serverId,
                task: 'stop',
                line: "Error: {$e->getMessage()}",
                type: 'stderr',
            ));

            broadcast(new ServerTaskCompleted(
                serverId: $serverId,
                task: 'stop',
                success: false,
                exitCode: -1,
            ));
        }
    }

    private function getConnection(string $serverId): ArkServerService
    {
        if (isset($this->connections[$serverId])) {
            return $this->connections[$serverId];
        }

        $server = GameServer::find($serverId);

        if (! $server) {
            throw new \RuntimeException("Server {$serverId} not found");
        }

        $this->connections[$serverId] = ArkServerService::forServer($server);
        $this->info("[POOL] Created SSH connection for server {$serverId}");

        return $this->connections[$serverId];
    }

    private function reconnect(string $serverId): void
    {
        unset($this->connections[$serverId]);
        $this->info("[POOL] Dropped connection for server {$serverId}, will reconnect on next use");
    }

    private function releaseConnection(string $serverId): void
    {
        if (isset($this->connections[$serverId])) {
            unset($this->connections[$serverId]);
            $this->info("[POOL] Released connection for server {$serverId} (no watchers)");
        }
    }

    private function getMaxPlayers(string $serverId): ?int
    {
        $profile = GameServerProfile::where('game_server_id', $serverId)
            ->active()
            ->first();

        return $profile?->max_players;
    }

    private function setupSignalHandlers(): void
    {
        if (! extension_loaded('pcntl')) {
            return;
        }

        pcntl_async_signals(true);

        pcntl_signal(SIGTERM, function () {
            $this->info('Received SIGTERM, shutting down...');
            $this->shutdown();
        });

        pcntl_signal(SIGINT, function () {
            $this->info('Received SIGINT, shutting down...');
            $this->shutdown();
        });
    }

    private function shutdown(): void
    {
        $this->connections = [];
        Loop::stop();
    }
}
