<?php

namespace App\Http\Controllers;

use App\Models\GameServer;
use App\Models\GameServerProfile;
use App\Models\RconCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Thedudeguy\Rcon;

class ServerRconController extends Controller
{
    public function active(): Response|RedirectResponse
    {
        $server = GameServer::active()->first();

        if (! $server) {
            return to_route('servers.index');
        }

        return $this->index($server);
    }

    public function index(GameServer $server): Response
    {
        $commands = RconCommand::orderBy('category')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('servers/Rcon', [
            'server' => $server,
            'commands' => $commands,
        ]);
    }

    public function execute(Request $request, GameServer $server): JsonResponse
    {
        $data = $request->validate([
            'command' => ['required', 'string', 'max:500'],
        ]);

        $profile = GameServerProfile::where('game_server_id', $server->id)
            ->active()
            ->first();

        if (! $profile) {
            return response()->json([
                'error' => 'No active profile found for this server.',
            ], 422);
        }

        if (! $profile->admin_password) {
            return response()->json([
                'error' => 'No admin password configured on the active profile. RCON requires ServerAdminPassword.',
            ], 422);
        }

        try {
            $rcon = new Rcon(
                $server->host,
                $profile->rcon_port,
                $profile->admin_password,
                5,
            );

            if (! @$rcon->connect()) {
                $reason = $rcon->getResponse();

                return response()->json([
                    'error' => $reason
                        ? "Connection refused: {$reason}"
                        : "Connection refused: the server is not reachable on port {$profile->rcon_port}. Is the server running with RCON enabled?",
                ], 503);
            }

            $result = $rcon->sendCommand($data['command']);
            $rcon->disconnect();
        } catch (\Throwable $e) {
            $message = match (true) {
                str_contains($e->getMessage(), 'fread') => 'The server is not responding to RCON. It may still be starting up or RCON is not enabled.',
                str_contains($e->getMessage(), 'unpack') => 'The server did not return a response. The command may have executed but produced no RCON output.',
                str_contains($e->getMessage(), 'Connection refused') => "Connection refused on port {$profile->rcon_port}. Is the server running?",
                default => "Connection failed: {$e->getMessage()}",
            };

            return response()->json(['error' => $message], 503);
        }

        if ($result === false) {
            return response()->json([
                'error' => 'Command execution failed. The server may have closed the RCON connection.',
            ], 422);
        }

        return response()->json([
            'response' => trim($result) ?: 'Command executed (no output returned).',
        ]);
    }
}
