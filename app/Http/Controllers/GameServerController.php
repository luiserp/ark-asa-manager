<?php

namespace App\Http\Controllers;

use App\Http\Requests\GameServerRequest;
use App\Models\GameServer;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class GameServerController extends Controller
{
    private function currentTeam(Request $request): Team
    {
        return $request->user()->currentTeam ?? abort(403);
    }

    public function index(Request $request): Response
    {
        $team = $this->currentTeam($request);

        return Inertia::render('servers/Index', [
            'servers' => $team->servers()->latest()->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('servers/Create', [
            'defaults' => [
                'port' => 22,
                'username' => 'ark-sa',
                'server_dir' => config('ark.server_dir'),
                'backups_dir' => config('ark.backups_dir'),
                'logs_dir' => config('ark.logs_dir'),
                'binary_path' => config('ark.binary_path'),
                'proton_path' => config('ark.proton_path'),
                'steam_app_id' => config('ark.steam_app_id'),
            ],
        ]);
    }

    public function store(GameServerRequest $request): RedirectResponse
    {
        $team = $this->currentTeam($request);
        $data = $request->safe()->except('ssh_key');

        $keyPath = $request->file('ssh_key')->store('ssh-keys', 'local');
        $data['ssh_key_path'] = Storage::disk('local')->path($keyPath);

        $server = $team->servers()->create($data);

        if (! $request->user()->currentServer()) {
            $request->user()->switchServer($server);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Server created.']);

        return to_route('servers.index');
    }

    public function edit(Request $request, GameServer $server): Response
    {
        $team = $this->currentTeam($request);
        abort_unless($server->team_id === $team->id, 403);

        return Inertia::render('servers/Edit', [
            'server' => $server,
        ]);
    }

    public function update(GameServerRequest $request, GameServer $server): RedirectResponse
    {
        $team = $this->currentTeam($request);
        abort_unless($server->team_id === $team->id, 403);

        $data = $request->safe()->except('ssh_key');

        if ($request->hasFile('ssh_key')) {
            $oldPath = str_replace(Storage::disk('local')->path(''), '', $server->ssh_key_path);
            Storage::disk('local')->delete($oldPath);

            $keyPath = $request->file('ssh_key')->store('ssh-keys', 'local');
            $data['ssh_key_path'] = Storage::disk('local')->path($keyPath);
        }

        $server->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Server updated.']);

        return to_route('servers.index');
    }

    public function toggleActive(Request $request, GameServer $server): RedirectResponse
    {
        $team = $this->currentTeam($request);
        abort_unless($server->team_id === $team->id, 403);

        $request->user()->switchServer($server);

        return back();
    }

    public function replicate(Request $request, GameServer $server): RedirectResponse
    {
        $team = $this->currentTeam($request);
        abort_unless($server->team_id === $team->id, 403);

        $clone = $server->replicate();
        $clone->name = $server->name.' (Copy)';
        $clone->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Server replicated.']);

        return to_route('servers.index');
    }

    public function transfer(Request $request, GameServer $server): RedirectResponse
    {
        $user = $request->user();
        $sourceTeam = $this->currentTeam($request);
        abort_unless($server->team_id === $sourceTeam->id, 403);
        abort_unless($sourceTeam->isOwner($user), 403);

        $data = $request->validate([
            'team_id' => ['required', 'string', 'exists:teams,id'],
        ]);

        $targetTeam = Team::findOrFail($data['team_id']);
        abort_unless($user->belongsToTeam($targetTeam), 403);
        abort_if($targetTeam->id === $sourceTeam->id, 422);

        DB::table('team_user')
            ->where('team_id', $sourceTeam->id)
            ->where('current_server_id', $server->id)
            ->update(['current_server_id' => null]);

        $server->update(['team_id' => $targetTeam->id]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Server transferred to {$targetTeam->name}."]);

        return to_route('servers.index');
    }

    public function destroy(Request $request, GameServer $server): RedirectResponse
    {
        $team = $this->currentTeam($request);
        abort_unless($server->team_id === $team->id, 403);

        $oldPath = str_replace(Storage::disk('local')->path(''), '', $server->ssh_key_path);
        Storage::disk('local')->delete($oldPath);

        $server->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Server deleted.']);

        return to_route('servers.index');
    }
}
