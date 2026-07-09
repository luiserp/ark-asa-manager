<?php

namespace App\Services;

use App\Models\GameServer;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SSH2;
use RuntimeException;

class SshService
{
    private ?SSH2 $connection = null;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $user,
        private readonly string $keyPath,
    ) {}

    public static function forServer(GameServer $server): static
    {
        return new static(
            host: $server->host,
            port: $server->port,
            user: $server->username,
            keyPath: $server->ssh_key_path,
        );
    }

    public static function make(): static
    {
        $keyPath = env('ARK_SERVER_SSH_KEY', '/shared-ssh/id_ed25519');

        if (! file_exists($keyPath)) {
            $keyPath = base_path('ssh/id_ed25519');
        }

        return new static(
            host: env('ARK_SERVER_HOST', 'ark-server'),
            port: (int) env('ARK_SERVER_SSH_PORT', 22),
            user: env('ARK_SERVER_SSH_USER', 'ark-sa'),
            keyPath: $keyPath,
        );
    }

    public function connect(): static
    {
        if ($this->connection && $this->connection->isConnected()) {
            return $this;
        }

        $this->connection = null;

        if (! file_exists($this->keyPath)) {
            throw new RuntimeException("SSH private key not found at: {$this->keyPath}");
        }

        $key = PublicKeyLoader::load(file_get_contents($this->keyPath));

        $this->connection = new SSH2($this->host, $this->port, timeout: 5);
        $this->connection->setTimeout(10);

        if (! $this->connection->login($this->user, $key)) {
            $this->connection = null;
            throw new RuntimeException("SSH authentication failed for {$this->user}@{$this->host}:{$this->port}");
        }

        return $this;
    }

    /**
     * Execute a command and return the full output.
     *
     * @param  int|null  $timeout  Override the default execution timeout (in seconds).
     * @return array{stdout: string, stderr: string, exit_code: int}
     */
    public function execute(string $command, ?int $timeout = null): array
    {
        $this->connect();

        if ($timeout !== null) {
            $this->connection->setTimeout($timeout);
        }

        $this->connection->enableQuietMode();

        $stdout = $this->connection->exec($command);
        $stderr = $this->connection->getStdError();
        $exitCode = $this->connection->getExitStatus();

        if ($timeout !== null) {
            $this->connection->setTimeout(10);
        }

        return [
            'stdout' => trim($stdout),
            'stderr' => trim($stderr),
            'exit_code' => $exitCode === false ? -1 : $exitCode,
        ];
    }

    /**
     * Execute a command and stream output line by line via a callback.
     *
     * @param  callable(string $line, string $type): void  $callback  Receives each line and its type ('stdout'|'stderr')
     */
    public function stream(string $command, callable $callback): int
    {
        $this->connect();

        $this->connection->setTimeout(0);

        $this->connection->exec($command, function (string $output) use ($callback) {
            $clean = preg_replace('/\x1b\[[0-9;]*m/', '', $output);
            $lines = explode("\n", $clean);
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed !== '') {
                    $callback($trimmed, 'stdout');
                }
            }
        });

        $stderr = $this->connection->getStdError();
        if ($stderr) {
            $clean = preg_replace('/\x1b\[[0-9;]*m/', '', $stderr);
            $lines = explode("\n", trim($clean));
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed !== '') {
                    $callback($trimmed, 'stderr');
                }
            }
        }

        $exitCode = $this->connection->getExitStatus();

        return $exitCode === false ? -1 : $exitCode;
    }

    /**
     * Check if the connection is alive.
     */
    public function isConnected(): bool
    {
        return $this->connection?->isConnected() ?? false;
    }

    public function disconnect(): void
    {
        $this->connection?->disconnect();
        $this->connection = null;
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
