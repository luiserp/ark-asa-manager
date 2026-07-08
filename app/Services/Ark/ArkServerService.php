<?php

namespace App\Services\Ark;

use App\Models\GameServer;
use App\Services\SshService;

class ArkServerService
{
    public function __construct(
        private readonly SshService $ssh,
        private readonly string $serverDir,
        private readonly string $backupsDir,
        private readonly string $logsDir,
        private readonly string $binaryPath,
        private readonly string $protonPath,
        private readonly string $steamAppId,
    ) {}

    public static function forServer(GameServer $server): static
    {
        return new static(
            ssh: SshService::forServer($server),
            serverDir: $server->server_dir,
            backupsDir: $server->backups_dir,
            logsDir: $server->logs_dir,
            binaryPath: $server->binary_path,
            protonPath: $server->proton_path,
            steamAppId: $server->steam_app_id,
        );
    }

    public static function make(): static
    {
        $default = GameServer::active()->first();

        if ($default) {
            return static::forServer($default);
        }

        return new static(
            ssh: SshService::make(),
            serverDir: config('ark.server_dir'),
            backupsDir: config('ark.backups_dir'),
            logsDir: config('ark.logs_dir'),
            binaryPath: config('ark.binary_path'),
            protonPath: config('ark.proton_path'),
            steamAppId: config('ark.steam_app_id'),
        );
    }

    /**
     * Install or update the server via SteamCMD.
     *
     * @return array{stdout: string, stderr: string, exit_code: int}
     */
    public function installOrUpdate(): array
    {
        $command = implode(' && ', [
            "mkdir -p ~/.steam {$this->serverDir}",
            implode(' ', [
                'steamcmd',
                '+@sSteamCmdForcePlatformType windows',
                "+force_install_dir {$this->serverDir}",
                '+login anonymous',
                "+app_update {$this->steamAppId}",
                "+app_update {$this->steamAppId}",
                '+quit',
            ]),
        ]);

        return $this->ssh->execute($command);
    }

    /**
     * Stream the install/update output in real-time.
     *
     * @param  callable(string $line, string $type): void  $callback
     */
    public function installOrUpdateStreaming(callable $callback): int
    {
        $command = implode(' && ', [
            "mkdir -p ~/.steam {$this->serverDir}",
            implode(' ', [
                'steamcmd',
                '+@sSteamCmdForcePlatformType windows',
                "+force_install_dir {$this->serverDir}",
                '+login anonymous',
                "+app_update {$this->steamAppId}",
                "+app_update {$this->steamAppId}",
                '+quit',
            ]),
        ]);

        return $this->ssh->stream($command, $callback);
    }

    /**
     * Start the ARK server with optional map and flags.
     *
     * @param  array<string, string>  $options
     */
    public function start(string $map = 'TheIsland_WP', array $options = []): array
    {
        $serverArgs = $this->buildServerArgs($map, $options);

        $command = sprintf(
            'mkdir -p %s && cd %s && nohup xvfb-run --auto-servernum %s %s/%s %s > %s/server_$(date +%%Y%%m%%d_%%H%%M%%S).log 2>&1 & echo $!',
            $this->logsDir,
            $this->serverDir,
            $this->protonPath,
            $this->serverDir,
            $this->binaryPath,
            $serverArgs,
            $this->logsDir,
        );

        return $this->ssh->execute($command);
    }

    /**
     * Stop the ARK server gracefully (SIGTERM), then force-kill if still running.
     */
    public function stop(): array
    {
        $command = implode('; ', [
            "PID=$(pgrep -f '[A]rkAscendedServer.exe')",
            'if [ -n "$PID" ]; then kill $PID && sleep 5; fi',
            "if pgrep -f '[A]rkAscendedServer.exe' > /dev/null; then pkill -9 -f '[A]rkAscendedServer.exe'; echo 'force-killed'; else echo 'stopped'; fi",
        ]);

        return $this->ssh->execute($command);
    }

    /**
     * Restart the server (stop then start).
     *
     * @param  array<string, string>  $options
     */
    public function restart(string $map = 'TheIsland_WP', array $options = []): array
    {
        $this->stop();
        sleep(2);

        return $this->start($map, $options);
    }

    /**
     * Check if the server process is running.
     *
     * @return array{running: bool, pid: int|null, uptime: string|null}
     */
    public function status(): array
    {
        $result = $this->ssh->execute("pgrep -f '[A]rkAscendedServer.exe' 2>/dev/null | head -1");

        if ($result['exit_code'] !== 0 || empty($result['stdout'])) {
            return ['running' => false, 'pid' => null, 'uptime' => null];
        }

        $pid = (int) trim($result['stdout']);

        $uptimeResult = $this->ssh->execute("ps -p {$pid} -o etime= 2>/dev/null");

        return [
            'running' => true,
            'pid' => $pid,
            'uptime' => trim($uptimeResult['stdout']) ?: null,
        ];
    }

    /**
     * Create a timestamped backup of the saved data.
     */
    public function backup(): array
    {
        $timestamp = date('Ymd_His');
        $savedDir = "{$this->serverDir}/ShooterGame/Saved";
        $backupFile = "{$this->backupsDir}/backup_{$timestamp}.tar.gz";

        $command = implode(' && ', [
            "mkdir -p {$this->backupsDir}",
            "tar -czf {$backupFile} -C {$savedDir} .",
            "echo 'Backup created: {$backupFile}'",
        ]);

        return $this->ssh->execute($command);
    }

    /**
     * List available backups.
     *
     * @return array{stdout: string, stderr: string, exit_code: int}
     */
    public function listBackups(): array
    {
        return $this->ssh->execute("ls -lhtr {$this->backupsDir}/*.tar.gz 2>/dev/null || echo \"No backups found\"");
    }

    /**
     * Get recent log output from the ARK server log.
     */
    public function logs(int $lines = 50): array
    {
        $arkLogPath = "{$this->serverDir}/ShooterGame/Saved/Logs/ShooterGame.log";
        $fallbackCommand = "find {$this->logsDir} -name '*.log' -printf '%T@ %p\\n' 2>/dev/null | sort -n | tail -1 | cut -d' ' -f2- | xargs tail -n {$lines} 2>/dev/null";

        $command = "if [ -f {$arkLogPath} ]; then tail -n {$lines} {$arkLogPath}; else {$fallbackCommand}; fi";

        return $this->ssh->execute($command);
    }

    /**
     * Check if the server files are installed.
     */
    public function isInstalled(): bool
    {
        $result = $this->ssh->execute("test -d {$this->serverDir}/ShooterGame && echo yes || echo no");

        return str_contains($result['stdout'], 'yes');
    }

    /**
     * Get disk usage for server, backups, and logs.
     *
     * @return array{server: string, backups: string, logs: string}
     */
    public function diskUsage(): array
    {
        $result = $this->ssh->execute(implode('; ', [
            "du -sh {$this->serverDir} 2>/dev/null || echo \"0\t{$this->serverDir}\"",
            "du -sh {$this->backupsDir} 2>/dev/null || echo \"0\t{$this->backupsDir}\"",
            "du -sh {$this->logsDir} 2>/dev/null || echo \"0\t{$this->logsDir}\"",
        ]));

        $lines = explode("\n", $result['stdout']);

        return [
            'server' => explode("\t", $lines[0] ?? '0')[0],
            'backups' => explode("\t", $lines[1] ?? '0')[0],
            'logs' => explode("\t", $lines[2] ?? '0')[0],
        ];
    }

    /**
     * Get system-level metrics (CPU, memory, connected players).
     *
     * @return array{cpu: float|null, memory_used: int|null, memory_total: int|null, players: int|null}
     */
    public function systemMetrics(): array
    {
        $command = "grep 'cpu ' /proc/stat | awk '{usage=(\$2+\$4)*100/(\$2+\$4+\$5)} END {printf \"%.1f\", usage}'; echo; free -m | awk '/Mem:/ {print \$3, \$2}'; ss -u -a 2>/dev/null | grep -c ':7777' || echo 0";

        $result = $this->ssh->execute($command);
        $lines = explode("\n", trim($result['stdout']));

        $cpu = isset($lines[0]) && is_numeric($lines[0]) ? (float) $lines[0] : null;

        $memParts = isset($lines[1]) ? explode(' ', trim($lines[1])) : [];
        $memoryUsed = isset($memParts[0]) && is_numeric($memParts[0]) ? (int) $memParts[0] : null;
        $memoryTotal = isset($memParts[1]) && is_numeric($memParts[1]) ? (int) $memParts[1] : null;

        $players = isset($lines[2]) ? max(0, (int) $lines[2] - 1) : null;

        return [
            'cpu' => $cpu,
            'memory_used' => $memoryUsed,
            'memory_total' => $memoryTotal,
            'players' => $players,
        ];
    }

    /**
     * @param  array<string, string>  $options
     */
    private function buildServerArgs(string $map, array $options): string
    {
        $defaults = [
            'SessionName' => 'ArkServer',
            'Port' => '7777',
            'QueryPort' => '27015',
            'RCONEnabled' => 'True',
            'RCONPort' => '27020',
        ];

        $merged = array_merge($defaults, $options);

        $args = $map.'?listen';
        foreach ($merged as $key => $value) {
            $args .= "?{$key}={$value}";
        }

        return $args;
    }
}
