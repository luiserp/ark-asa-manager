<?php

namespace App\Http\Controllers;

use App\Http\Requests\GameServerProfileRequest;
use App\Models\GameServer;
use App\Models\GameServerProfile;
use App\Services\Ark\ArkServerService;
use App\Services\SshService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GameServerProfileController extends Controller
{
    private function activeServer(): GameServer
    {
        return GameServer::active()->firstOrFail();
    }

    public function index(): Response
    {
        $server = $this->activeServer();

        return Inertia::render('profiles/Index', [
            'server' => $server,
            'profiles' => $server->profiles()->latest()->get(),
            'servers' => GameServer::select('id', 'name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('profiles/Create', [
            'server' => $this->activeServer(),
            'defaults' => config('ark.profile_defaults'),
            'maps' => config('ark.maps'),
        ]);
    }

    public function store(GameServerProfileRequest $request): RedirectResponse
    {
        $server = $this->activeServer();
        $data = $request->validated();

        if (! $server->profiles()->exists()) {
            $data['is_active'] = true;
        }

        $server->profiles()->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Profile created.']);

        return to_route('profiles.index');
    }

    public function edit(GameServerProfile $profile): Response
    {
        return Inertia::render('profiles/Edit', [
            'server' => $this->activeServer(),
            'profile' => $profile,
            'maps' => config('ark.maps'),
        ]);
    }

    public function update(GameServerProfileRequest $request, GameServerProfile $profile): RedirectResponse
    {
        $profile->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Profile updated.']);

        return to_route('profiles.index');
    }

    public function toggleActive(GameServerProfile $profile): RedirectResponse
    {
        if ($profile->is_active) {
            return back();
        }

        $server = $profile->gameServer;
        $arkService = ArkServerService::forServer($server);
        $status = $arkService->status();

        if ($status['running']) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Cannot switch profiles while the server is running. Stop it first.',
            ]);

            return back();
        }

        $server->profiles()->where('is_active', true)->update(['is_active' => false]);
        $profile->update(['is_active' => true]);

        $this->deployProfileConfigs($profile, $server);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Profile '{$profile->name}' activated and configs deployed."]);

        return back();
    }

    private function deployProfileConfigs(GameServerProfile $profile, GameServer $server): void
    {
        $fileColumnMap = [
            'GameUserSettings.ini' => 'game_user_settings_ini',
            'Game.ini' => 'game_ini',
        ];

        try {
            $ssh = SshService::forServer($server);

            foreach ($fileColumnMap as $file => $column) {
                $content = $profile->{$column};

                if ($content === null) {
                    continue;
                }

                $remotePath = "{$server->server_dir}/ShooterGame/Saved/Config/WindowsServer/{$file}";
                $escapedContent = str_replace("'", "'\\''", $content);
                $ssh->execute("mkdir -p $(dirname {$remotePath}) && printf '%s' '{$escapedContent}' > {$remotePath}");
            }
        } catch (\Throwable) {
            // Config deployment is best-effort during profile switch
        }
    }

    public function replicate(GameServerProfile $profile): RedirectResponse
    {
        $targetServerId = request()->input('game_server_id', $profile->game_server_id);
        $targetServer = GameServer::findOrFail($targetServerId);

        $clone = $profile->replicate(['is_active']);
        $clone->game_server_id = $targetServer->id;
        $clone->name = $profile->name.' (Copy)';
        $clone->is_active = false;
        $clone->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Profile replicated.']);

        return to_route('profiles.index');
    }

    public function destroy(GameServerProfile $profile): RedirectResponse
    {
        $profile->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Profile deleted.']);

        return to_route('profiles.index');
    }
}
