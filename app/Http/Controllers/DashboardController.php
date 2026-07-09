<?php

namespace App\Http\Controllers;

use App\Models\GameServer;
use App\Services\Ark\ArkServerService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $team = $request->user()->currentTeam;

        if (! $team) {
            return Inertia::render('Dashboard', ['servers' => []]);
        }

        $currentServer = $request->user()->currentServer();

        $servers = $team->servers()->get()->map(function (GameServer $server) use ($currentServer) {
            try {
                $ark = ArkServerService::forServer($server);
                $status = $ark->status();

                return [
                    'id' => $server->id,
                    'name' => $server->name,
                    'host' => $server->host,
                    'is_current' => $currentServer?->id === $server->id,
                    'connected' => true,
                    'running' => $status['running'],
                    'pid' => $status['pid'],
                    'uptime' => $status['uptime'],
                    'error' => null,
                ];
            } catch (\Throwable $e) {
                return [
                    'id' => $server->id,
                    'name' => $server->name,
                    'host' => $server->host,
                    'is_current' => $currentServer?->id === $server->id,
                    'connected' => false,
                    'running' => false,
                    'pid' => null,
                    'uptime' => null,
                    'error' => $e->getMessage(),
                ];
            }
        });

        return Inertia::render('Dashboard', [
            'servers' => $servers,
        ]);
    }
}
