<?php

namespace App\Http\Controllers;

use App\Models\GameServer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redis;
use Inertia\Inertia;
use Inertia\Response;

class ServerDetailController extends Controller
{
    public function index(GameServer $server): Response
    {
        return Inertia::render('servers/Detail', [
            'server' => $server,
        ]);
    }

    public function install(GameServer $server): JsonResponse
    {
        Redis::publish('ark:commands', json_encode([
            'serverId' => $server->id,
            'task' => 'install',
        ]));

        return response()->json(['dispatched' => true, 'task' => 'install'], 202);
    }

    public function start(GameServer $server): JsonResponse
    {
        Redis::publish('ark:commands', json_encode([
            'serverId' => $server->id,
            'task' => 'start',
            'profileId' => request('profileId'),
        ]));

        return response()->json(['dispatched' => true, 'task' => 'start'], 202);
    }

    public function stop(GameServer $server): JsonResponse
    {
        Redis::publish('ark:commands', json_encode([
            'serverId' => $server->id,
            'task' => 'stop',
        ]));

        return response()->json(['dispatched' => true, 'task' => 'stop'], 202);
    }

    public function leave(GameServer $server): JsonResponse
    {
        $user = request()->user();

        Redis::publish('ark:subscriptions', json_encode([
            'serverId' => $server->id,
            'action' => 'leave',
            'userId' => $user->id,
        ]));

        return response()->json(['ok' => true]);
    }
}
