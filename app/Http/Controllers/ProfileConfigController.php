<?php

namespace App\Http\Controllers;

use App\Models\GameServerProfile;
use App\Services\Ark\ArkServerService;
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

    private const FILE_COLUMN_MAP = [
        'GameUserSettings.ini' => 'game_user_settings_ini',
        'Game.ini' => 'game_ini',
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

    /**
     * Load stored config content from the database.
     */
    public function pull(GameServerProfile $profile, Request $request): JsonResponse
    {
        $file = $request->validate(['file' => ['required', 'string']])['file'];

        if (! in_array($file, self::ALLOWED_FILES, true)) {
            return response()->json(['error' => 'Invalid file name.'], 422);
        }

        $column = self::FILE_COLUMN_MAP[$file];

        return response()->json(['content' => $profile->{$column} ?? '']);
    }

    /**
     * Save config content to the database for this profile.
     */
    public function push(GameServerProfile $profile, Request $request): JsonResponse
    {
        $data = $request->validate([
            'file' => ['required', 'string'],
            'content' => ['required', 'string'],
        ]);

        if (! in_array($data['file'], self::ALLOWED_FILES, true)) {
            return response()->json(['error' => 'Invalid file name.'], 422);
        }

        $column = self::FILE_COLUMN_MAP[$data['file']];
        $profile->update([$column => $data['content']]);

        return response()->json(['success' => true]);
    }

    /**
     * Import config content from the remote server into the database.
     */
    public function importFromServer(GameServerProfile $profile, Request $request): JsonResponse
    {
        $file = $request->validate(['file' => ['required', 'string']])['file'];

        if (! in_array($file, self::ALLOWED_FILES, true)) {
            return response()->json(['error' => 'Invalid file name.'], 422);
        }

        try {
            $ssh = SshService::forServer($profile->gameServer);
            $remotePath = $this->configPath($profile, $file);
            $result = $ssh->execute("cat {$remotePath} 2>/dev/null || echo ''");

            $content = $result['stdout'];
            $column = self::FILE_COLUMN_MAP[$file];
            $profile->update([$column => $content]);

            return response()->json(['content' => $content]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Deploy stored configs to the remote server (only if server is stopped).
     */
    public function apply(GameServerProfile $profile): JsonResponse
    {
        $server = $profile->gameServer;
        $arkService = ArkServerService::forServer($server);
        $status = $arkService->status();

        if ($status['running']) {
            return response()->json([
                'error' => 'Cannot apply config while the server is running. Stop the server first.',
            ], 409);
        }

        try {
            $ssh = SshService::forServer($server);
            $deployed = [];

            foreach (self::FILE_COLUMN_MAP as $file => $column) {
                $content = $profile->{$column};

                if ($content === null) {
                    continue;
                }

                $remotePath = $this->configPath($profile, $file);
                $escapedContent = str_replace("'", "'\\''", $content);
                $result = $ssh->execute("mkdir -p $(dirname {$remotePath}) && printf '%s' '{$escapedContent}' > {$remotePath}");

                if ($result['exit_code'] !== 0) {
                    return response()->json([
                        'error' => "Failed to write {$file}: {$result['stderr']}",
                    ], 500);
                }

                $deployed[] = $file;
            }

            return response()->json([
                'success' => true,
                'deployed' => $deployed,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
