<?php

namespace App\Http\Controllers;

use App\Jobs\ServerTaskJob;
use App\Models\GameServer;
use Illuminate\Http\JsonResponse;
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

    public function status(GameServer $server): JsonResponse
    {
        ServerTaskJob::dispatch($server, 'status');

        return response()->json(['dispatched' => true, 'task' => 'status'], 202);
    }

    public function install(GameServer $server): JsonResponse
    {
        ServerTaskJob::dispatch($server, 'install');

        return response()->json(['dispatched' => true, 'task' => 'install'], 202);
    }

    public function start(GameServer $server): JsonResponse
    {
        ServerTaskJob::dispatch($server, 'start');

        return response()->json(['dispatched' => true, 'task' => 'start'], 202);
    }

    public function stop(GameServer $server): JsonResponse
    {
        ServerTaskJob::dispatch($server, 'stop');

        return response()->json(['dispatched' => true, 'task' => 'stop'], 202);
    }

    public function logs(GameServer $server): JsonResponse
    {
        ServerTaskJob::dispatch($server, 'logs');

        return response()->json(['dispatched' => true, 'task' => 'logs'], 202);
    }
}
