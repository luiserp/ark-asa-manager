<?php

namespace App\Http\Controllers;

use App\Models\GameServer;
use App\Services\SshService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ServerFilesController extends Controller
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

        $path = $request->query('path', $server->server_dir);
        $path = $this->resolvePath($path, $server->server_dir);

        $entries = [];
        $error = null;

        try {
            $ssh = SshService::forServer($server)->connect();
            $result = $ssh->execute("ls -la --group-directories-first {$path} 2>&1");

            $entries = $this->parseLsOutput($result['stdout'] ?? '', $path);
            $error = $result['exit_code'] !== 0 ? ($result['stderr'] ?: $result['stdout']) : null;
        } catch (\Throwable) {
            $error = 'Unable to connect to the server. Make sure the server container is running.';
        }

        return Inertia::render('servers/Files', [
            'server' => $server,
            'currentPath' => $path,
            'basePath' => $server->server_dir,
            'entries' => $entries,
            'error' => $error,
        ]);
    }

    public function read(Request $request, GameServer $server): JsonResponse
    {
        $team = $request->user()->currentTeam ?? abort(403);
        abort_unless($server->team_id === $team->id, 403);

        $path = $request->query('path', '');
        $path = $this->resolvePath($path, $server->server_dir);

        try {
            $ssh = SshService::forServer($server)->connect();
            $result = $ssh->execute("cat {$path} 2>/dev/null");

            if ($result['exit_code'] !== 0) {
                return response()->json(['error' => 'Unable to read file.', 'content' => ''], 404);
            }

            return response()->json(['content' => $result['stdout']]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage(), 'content' => ''], 500);
        }
    }

    public function write(Request $request, GameServer $server): JsonResponse
    {
        $team = $request->user()->currentTeam ?? abort(403);
        abort_unless($server->team_id === $team->id, 403);

        $data = $request->validate([
            'path' => ['required', 'string'],
            'content' => ['required', 'string'],
        ]);

        $path = $this->resolvePath($data['path'], $server->server_dir);

        try {
            $ssh = SshService::forServer($server)->connect();
            $escapedContent = str_replace("'", "'\\''", $data['content']);
            $result = $ssh->execute("printf '%s' '{$escapedContent}' > {$path}");

            if ($result['exit_code'] !== 0) {
                return response()->json(['error' => "Failed to write: {$result['stderr']}"], 500);
            }

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function download(Request $request, GameServer $server): StreamedResponse
    {
        $team = $request->user()->currentTeam ?? abort(403);
        abort_unless($server->team_id === $team->id, 403);

        $path = $request->query('path', '');
        $path = $this->resolvePath($path, $server->server_dir);

        try {
            $ssh = SshService::forServer($server)->connect();
        } catch (\Throwable) {
            abort(503, 'Unable to connect to the server. Make sure the server container is running.');
        }

        $check = $ssh->execute("test -f {$path} && echo 'ok'");
        if (trim($check['stdout'] ?? '') !== 'ok') {
            abort(404, 'File not found on remote server.');
        }

        $filename = basename($path);

        return response()->streamDownload(function () use ($ssh, $path) {
            $result = $ssh->execute("cat {$path}");
            echo $result['stdout'] ?? '';
        }, $filename);
    }

    private function resolvePath(string $path, string $basePath): string
    {
        $path = str_replace(['../', '..\\'], '', $path);

        if (! str_starts_with($path, '/')) {
            $path = $basePath.'/'.$path;
        }

        if (! str_starts_with($path, $basePath)) {
            return $basePath;
        }

        return rtrim($path, '/');
    }

    /**
     * @return array<int, array{name: string, type: string, size: string, modified: string, permissions: string}>
     */
    private function parseLsOutput(string $output, string $currentPath): array
    {
        $lines = array_filter(explode("\n", $output));
        $entries = [];

        foreach ($lines as $line) {
            if (str_starts_with($line, 'total ') || trim($line) === '') {
                continue;
            }

            $parts = preg_split('/\s+/', $line, 9);
            if (! $parts || count($parts) < 9) {
                continue;
            }

            $name = $parts[8];

            if ($name === '.' || $name === '..') {
                continue;
            }

            $permissions = $parts[0];
            $isDir = str_starts_with($permissions, 'd');
            $isLink = str_starts_with($permissions, 'l');

            $entries[] = [
                'name' => $name,
                'type' => $isDir ? 'directory' : ($isLink ? 'link' : 'file'),
                'size' => $this->formatSize((int) $parts[4]),
                'modified' => "{$parts[5]} {$parts[6]} {$parts[7]}",
                'permissions' => $permissions,
                'path' => $currentPath.'/'.$name,
            ];
        }

        return $entries;
    }

    private function formatSize(int $bytes): string
    {
        if ($bytes === 0) {
            return '—';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 1).' '.$units[$i];
    }
}
