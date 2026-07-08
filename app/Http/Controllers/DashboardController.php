<?php

namespace App\Http\Controllers;

use App\Models\GameServer;
use App\Services\Ark\ArkServerService;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $servers = GameServer::all()->map(function (GameServer $server) {
            try {
                $ark = ArkServerService::forServer($server);
                $status = $ark->status();

                return [
                    'id' => $server->id,
                    'name' => $server->name,
                    'host' => $server->host,
                    'is_active' => $server->is_active,
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
                    'is_active' => $server->is_active,
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
