<?php

namespace App\Http\Controllers;

use App\Models\GameServerProfile;
use App\Services\CurseForge\CurseForgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileModsController extends Controller
{
    public function index(Request $request, GameServerProfile $profile): Response
    {
        $team = $request->user()->currentTeam ?? abort(403);
        abort_unless($profile->gameServer->team_id === $team->id, 403);

        return Inertia::render('profiles/Mods', [
            'profile' => $profile->only('id', 'name'),
            'server' => $profile->gameServer->only('id', 'name'),
            'mods' => $profile->mods ?? [],
            'curseforgeUrl' => config('ark.curseforge_url'),
            'hasCurseforgeIntegration' => $team->integrations()
                ->where('provider', 'curseforge')
                ->where('is_active', true)
                ->exists(),
        ]);
    }

    public function active(Request $request): Response|RedirectResponse
    {
        $server = $request->user()->currentServer();
        $profile = $server
            ? GameServerProfile::where('game_server_id', $server->id)->active()->first()
            : null;

        if (! $profile) {
            return to_route('profiles.index');
        }

        $team = $request->user()->currentTeam;

        return Inertia::render('profiles/Mods', [
            'profile' => $profile->only('id', 'name'),
            'server' => $profile->gameServer->only('id', 'name'),
            'mods' => $profile->mods ?? [],
            'curseforgeUrl' => config('ark.curseforge_url'),
            'hasCurseforgeIntegration' => $team
                ? $team->integrations()
                    ->where('provider', 'curseforge')
                    ->where('is_active', true)
                    ->exists()
                : false,
        ]);
    }

    /**
     * @param  array<int, array{mod_id: string, name: string, is_passive: bool, is_enabled: bool}>  $mods
     */
    public function store(Request $request, GameServerProfile $profile): JsonResponse
    {
        $team = $request->user()->currentTeam ?? abort(403);
        abort_unless($profile->gameServer->team_id === $team->id, 403);

        $data = $request->validate([
            'mods' => ['present', 'array'],
            'mods.*.mod_id' => ['required', 'string', 'max:20'],
            'mods.*.name' => ['required', 'string', 'max:255'],
            'mods.*.is_passive' => ['required', 'boolean'],
            'mods.*.is_enabled' => ['required', 'boolean'],
        ]);

        $profile->update(['mods' => $data['mods']]);

        return response()->json(['success' => true, 'mods' => $profile->mods]);
    }

    public function search(Request $request, GameServerProfile $profile): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'page' => ['nullable', 'integer', 'min:0'],
        ]);

        try {
            $team = $request->user()->currentTeam;
            $service = CurseForgeService::make($team);

            $params = [
                'pageSize' => 12,
            ];

            if ($request->filled('q')) {
                $params['searchFilter'] = $request->input('q');
            }

            if ($request->filled('page')) {
                $params['index'] = (int) $request->input('page') * 12;
            }

            $results = $service->searchMods($params);

            return response()->json($results);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function details(Request $request, GameServerProfile $profile): JsonResponse
    {
        $mods = $profile->mods ?? [];

        if (empty($mods)) {
            return response()->json(['details' => []]);
        }

        try {
            $team = $request->user()->currentTeam;
            $service = CurseForgeService::make($team);
            $modIds = array_map(fn ($m) => (int) $m['mod_id'], $mods);
            $details = $service->getModsByIds($modIds);

            return response()->json(['details' => $details]);
        } catch (\RuntimeException $e) {
            return response()->json(['details' => [], 'error' => $e->getMessage()]);
        }
    }
}
