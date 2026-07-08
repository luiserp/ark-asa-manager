<?php

namespace App\Http\Controllers;

use App\Http\Requests\GameServerRequest;
use App\Models\GameServer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class GameServerController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('servers/Index', [
            'servers' => GameServer::latest()->get(),
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
        $data = $request->safe()->except('ssh_key');

        $keyPath = $request->file('ssh_key')->store('ssh-keys', 'local');
        $data['ssh_key_path'] = Storage::disk('local')->path($keyPath);

        if (! GameServer::exists()) {
            $data['is_active'] = true;
        }

        GameServer::create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Server created.']);

        return to_route('servers.index');
    }

    public function edit(GameServer $server): Response
    {
        return Inertia::render('servers/Edit', [
            'server' => $server,
        ]);
    }

    public function update(GameServerRequest $request, GameServer $server): RedirectResponse
    {
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

    public function toggleActive(GameServer $server): RedirectResponse
    {
        if ($server->is_active) {
            $server->update(['is_active' => false]);
        } else {
            GameServer::where('is_active', true)->update(['is_active' => false]);
            $server->update(['is_active' => true]);
        }

        return back();
    }

    public function replicate(GameServer $server): RedirectResponse
    {
        $clone = $server->replicate(['is_active']);
        $clone->name = $server->name.' (Copy)';
        $clone->is_active = false;
        $clone->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Server replicated.']);

        return to_route('servers.index');
    }

    public function destroy(GameServer $server): RedirectResponse
    {
        $oldPath = str_replace(Storage::disk('local')->path(''), '', $server->ssh_key_path);
        Storage::disk('local')->delete($oldPath);

        $server->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Server deleted.']);

        return to_route('servers.index');
    }
}
