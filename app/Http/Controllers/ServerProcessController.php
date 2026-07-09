<?php

namespace App\Http\Controllers;

use App\Models\GameServer;
use App\Services\Ark\ArkServerService;
use App\Services\SshService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServerProcessController extends Controller
{
    public function active(Request $request): Response|RedirectResponse
    {
        $server = $request->user()->currentServer();

        if (! $server) {
            return to_route('servers.index');
        }

        return $this->index($request, $server);
    }

    public function index(Request $request, GameServer $server): Response
    {
        $team = $request->user()->currentTeam ?? abort(403);
        abort_unless($server->team_id === $team->id, 403);

        $processes = [];
        $stats = ['cpu' => null, 'memory' => null, 'disk' => null];
        $error = null;

        try {
            $ssh = SshService::forServer($server)->connect();

            $command = 'ps axo pid,user,%cpu,%mem,rss,stat,start_time,time,args --sort=-%mem 2>&1';
            $result = $ssh->execute($command);

            $processes = $this->parsePsOutput($result['stdout'] ?? '');

            $service = new ArkServerService(
                ssh: $ssh,
                serverDir: $server->server_dir,
                backupsDir: $server->backups_dir,
                logsDir: $server->logs_dir,
                binaryPath: $server->binary_path,
                protonPath: $server->proton_path,
                steamAppId: $server->steam_app_id,
            );
            $metrics = $service->systemMetrics();
            $disk = $service->diskUsage();

            $stats = [
                'cpu' => $metrics['cpu'] !== null ? $metrics['cpu'].'%' : null,
                'memory' => $metrics['memory_used'] !== null
                    ? number_format($metrics['memory_used'], 0, ',', '.').' / '.number_format($metrics['memory_total'] ?? 0, 0, ',', '.').' MB'
                    : null,
                'disk' => $disk['server'],
            ];

            $error = $result['exit_code'] !== 0 ? ($result['stderr'] ?: $result['stdout']) : null;
        } catch (\Throwable) {
            $error = 'Unable to connect to the server. Make sure the server container is running.';
        }

        return Inertia::render('servers/Processes', [
            'server' => $server,
            'processes' => $processes,
            'stats' => $stats,
            'error' => $error,
        ]);
    }

    public function kill(Request $request, GameServer $server): JsonResponse
    {
        $team = $request->user()->currentTeam ?? abort(403);
        abort_unless($server->team_id === $team->id, 403);

        $data = $request->validate([
            'pid' => ['required', 'integer', 'min:1'],
            'signal' => ['sometimes', 'string', 'in:TERM,KILL'],
        ]);

        $pid = (int) $data['pid'];
        $signal = $data['signal'] ?? 'TERM';

        try {
            $ssh = SshService::forServer($server)->connect();
        } catch (\Throwable) {
            return response()->json([
                'error' => 'Unable to connect to the server. Make sure the server container is running.',
            ], 503);
        }

        $result = $ssh->execute("kill -{$signal} {$pid} 2>&1");

        if ($result['exit_code'] !== 0) {
            return response()->json([
                'error' => trim($result['stderr'] ?: $result['stdout']) ?: 'Failed to kill process.',
            ], 422);
        }

        return response()->json(['success' => true]);
    }

    /**
     * @return array<int, array{pid: int, user: string, cpu: float, mem: float, rss: int, stat: string, start: string, time: string, command: string}>
     */
    private function parsePsOutput(string $output): array
    {
        $lines = explode("\n", $output);
        $processes = [];

        foreach ($lines as $i => $line) {
            if ($i === 0 || trim($line) === '') {
                continue;
            }

            $parts = preg_split('/\s+/', trim($line), 9);
            if (! $parts || count($parts) < 9) {
                continue;
            }

            $processes[] = [
                'pid' => (int) $parts[0],
                'user' => $parts[1],
                'cpu' => (float) $parts[2],
                'mem' => (float) $parts[3],
                'rss' => (int) $parts[4],
                'stat' => $parts[5],
                'start' => $parts[6],
                'time' => $parts[7],
                'command' => $parts[8],
            ];
        }

        return $processes;
    }
}
