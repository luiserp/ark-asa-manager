<?php

namespace App\Http\Controllers;

use App\Models\GameServer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Inertia\Inertia;
use Inertia\Response;

class ServerDetailController extends Controller
{
    public function index(Request $request, GameServer $server): Response
    {
        $team = $request->user()->currentTeam ?? abort(403);
        abort_unless($server->team_id === $team->id, 403);

        return Inertia::render('servers/Detail', [
            'server' => $server,
        ]);
    }

    public function active(Request $request): Response|RedirectResponse
    {
        $server = $request->user()->currentServer();

        if (! $server) {
            return to_route('servers.index');
        }

        return Inertia::render('servers/Detail', [
            'server' => $server,
        ]);
    }

    public function install(Request $request, GameServer $server): JsonResponse
    {
        $team = $request->user()->currentTeam ?? abort(403);
        abort_unless($server->team_id === $team->id, 403);

        Redis::publish('ark:commands', json_encode([
            'serverId' => $server->id,
            'task' => 'install',
        ]));

        return response()->json(['dispatched' => true, 'task' => 'install'], 202);
    }

    public function start(Request $request, GameServer $server): JsonResponse
    {
        $team = $request->user()->currentTeam ?? abort(403);
        abort_unless($server->team_id === $team->id, 403);

        Redis::publish('ark:commands', json_encode([
            'serverId' => $server->id,
            'task' => 'start',
            'profileId' => $request->input('profileId'),
        ]));

        return response()->json(['dispatched' => true, 'task' => 'start'], 202);
    }

    public function stop(Request $request, GameServer $server): JsonResponse
    {
        $team = $request->user()->currentTeam ?? abort(403);
        abort_unless($server->team_id === $team->id, 403);

        Redis::publish('ark:commands', json_encode([
            'serverId' => $server->id,
            'task' => 'stop',
        ]));

        return response()->json(['dispatched' => true, 'task' => 'stop'], 202);
    }

    public function leave(Request $request, GameServer $server): JsonResponse
    {
        $user = $request->user();

        Redis::publish('ark:subscriptions', json_encode([
            'serverId' => $server->id,
            'action' => 'leave',
            'userId' => $user->id,
        ]));

        return response()->json(['ok' => true]);
    }
}
