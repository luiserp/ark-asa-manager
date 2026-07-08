<?php

namespace App\Http\Controllers;

use App\Models\GameServerProfile;
use App\Services\SshService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileConfigController extends Controller
{
    private const ALLOWED_FILES = [
        'GameUserSettings.ini',
        'Game.ini',
    ];

    private function configPath(GameServerProfile $profile, string $file): string
    {
        $serverDir = $profile->gameServer->server_dir;

        return "{$serverDir}/ShooterGame/Saved/Config/WindowsServer/{$file}";
    }

    public function index(GameServerProfile $profile): Response
    {
        return Inertia::render('profiles/Config', [
            'profile' => $profile->only('id', 'name'),
            'server' => $profile->gameServer->only('id', 'name'),
            'files' => self::ALLOWED_FILES,
        ]);
    }

    public function pull(GameServerProfile $profile, Request $request): JsonResponse
    {
        $file = $request->validate(['file' => ['required', 'string']])['file'];

        if (! in_array($file, self::ALLOWED_FILES, true)) {
            return response()->json(['error' => 'Invalid file name.'], 422);
        }

        try {
            $ssh = SshService::forServer($profile->gameServer);
            $remotePath = $this->configPath($profile, $file);
            $result = $ssh->execute("cat {$remotePath} 2>/dev/null || echo ''");

            return response()->json(['content' => $result['stdout']]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage(), 'content' => ''], 500);
        }
    }

    public function push(GameServerProfile $profile, Request $request): JsonResponse
    {
        $data = $request->validate([
            'file' => ['required', 'string'],
            'content' => ['required', 'string'],
        ]);

        if (! in_array($data['file'], self::ALLOWED_FILES, true)) {
            return response()->json(['error' => 'Invalid file name.'], 422);
        }

        try {
            $ssh = SshService::forServer($profile->gameServer);
            $remotePath = $this->configPath($profile, $data['file']);

            $escapedContent = str_replace("'", "'\\''", $data['content']);
            $result = $ssh->execute("mkdir -p $(dirname {$remotePath}) && printf '%s' '{$escapedContent}' > {$remotePath}");

            if ($result['exit_code'] !== 0) {
                return response()->json(['error' => "Failed to write file: {$result['stderr']}"], 500);
            }

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
