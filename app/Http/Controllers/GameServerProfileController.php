<?php

namespace App\Http\Controllers;

use App\Http\Requests\GameServerProfileRequest;
use App\Models\GameServer;
use App\Models\GameServerProfile;
use App\Models\Team;
use App\Services\Ark\ArkServerService;
use App\Services\SshService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GameServerProfileController extends Controller
{
    private function currentTeam(Request $request): Team
    {
        return $request->user()->currentTeam ?? abort(403);
    }

    private function activeServer(Request $request): GameServer
    {
        return $request->user()->currentServer() ?? abort(404, 'No active server selected.');
    }

    public function index(Request $request): Response
    {
        $server = $this->activeServer($request);
        $team = $this->currentTeam($request);

        return Inertia::render('profiles/Index', [
            'server' => $server,
            'profiles' => $server->profiles()->latest()->get(),
            'servers' => $team->servers()->select('id', 'name')->get(),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('profiles/Create', [
            'server' => $this->activeServer($request),
            'defaults' => config('ark.profile_defaults'),
            'maps' => config('ark.maps'),
        ]);
    }

    public function store(GameServerProfileRequest $request): RedirectResponse
    {
        $server = $this->activeServer($request);
        $data = $request->validated();

        if (! $server->profiles()->exists()) {
            $data['is_active'] = true;
        }

        $server->profiles()->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Profile created.']);

        return to_route('profiles.index');
    }

    public function edit(Request $request, GameServerProfile $profile): Response
    {
        return Inertia::render('profiles/Edit', [
            'server' => $this->activeServer($request),
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

    public function toggleActive(Request $request, GameServerProfile $profile): RedirectResponse
    {
        if ($profile->is_active) {
            return back();
        }

        $server = $profile->gameServer;
        $team = $this->currentTeam($request);
        abort_unless($server->team_id === $team->id, 403);

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

    public function replicate(Request $request, GameServerProfile $profile): RedirectResponse
    {
        $team = $this->currentTeam($request);
        $targetServerId = $request->input('game_server_id', $profile->game_server_id);
        $targetServer = $team->servers()->findOrFail($targetServerId);

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
