<?php

namespace App\Http\Controllers;

use App\Models\GameServer;
use App\Services\Ark\ArkServerService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $team = $user->currentTeam;

        if (! $team) {
            return Inertia::render('Dashboard', [
                'servers' => [],
                'teamStats' => ['members' => 0, 'servers' => 0, 'profiles' => 0, 'integrations' => 0],
                'serverMetrics' => Inertia::defer(fn () => []),
            ]);
        }

        $currentServer = $user->currentServer();
        $servers = $team->servers()->get();

        $serverList = $servers->map(fn (GameServer $server) => [
            'id' => $server->id,
            'name' => $server->name,
            'host' => $server->host,
            'is_current' => $currentServer?->id === $server->id,
            'profile_count' => $server->profiles()->count(),
            'active_profile' => $server->profiles()->active()->value('name'),
        ]);

        $teamStats = [
            'members' => $team->members()->count(),
            'servers' => $servers->count(),
            'profiles' => $servers->sum(fn (GameServer $s) => $s->profiles()->count()),
            'integrations' => $team->integrations()->count(),
        ];

        return Inertia::render('Dashboard', [
            'servers' => $serverList,
            'teamStats' => $teamStats,
            'serverMetrics' => Inertia::defer(fn () => $this->collectServerMetrics($servers, $currentServer)),
        ]);
    }

    /**
     * @param  Collection<int, GameServer>  $servers
     * @return array<string, array<string, mixed>>
     */
    private function collectServerMetrics($servers, ?GameServer $currentServer): array
    {
        $metrics = [];

        foreach ($servers as $server) {
            try {
                $ark = ArkServerService::forServer($server);
                $status = $ark->status();
                $system = $status['running'] ? $ark->systemMetrics() : null;

                $activeProfile = $server->profiles()->active()->first();
                $maxPlayers = $activeProfile?->max_players ?? 70;

                $metrics[$server->id] = [
                    'connected' => true,
                    'running' => $status['running'],
                    'pid' => $status['pid'],
                    'uptime' => $status['uptime'],
                    'cpu' => $system['cpu'] ?? null,
                    'memory_used' => $system['memory_used'] ?? null,
                    'memory_total' => $system['memory_total'] ?? null,
                    'players' => $system['players'] ?? null,
                    'max_players' => $maxPlayers,
                    'error' => null,
                ];
            } catch (\Throwable $e) {
                $metrics[$server->id] = [
                    'connected' => false,
                    'running' => false,
                    'pid' => null,
                    'uptime' => null,
                    'cpu' => null,
                    'memory_used' => null,
                    'memory_total' => null,
                    'players' => null,
                    'max_players' => null,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $metrics;
    }
}
