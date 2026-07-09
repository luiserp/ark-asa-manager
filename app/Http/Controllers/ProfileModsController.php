<?php

namespace App\Http\Controllers;

use App\Models\GameServerProfile;
use App\Models\Integration;
use App\Services\CurseForge\CurseForgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileModsController extends Controller
{
    public function index(GameServerProfile $profile): Response
    {
        return Inertia::render('profiles/Mods', [
            'profile' => $profile->only('id', 'name'),
            'server' => $profile->gameServer->only('id', 'name'),
            'mods' => $profile->mods ?? [],
            'curseforgeUrl' => config('ark.curseforge_url'),
            'hasCurseforgeIntegration' => Integration::where('provider', 'curseforge')
                ->where('is_active', true)
                ->exists(),
        ]);
    }

    /**
     * Save the mod list to the profile.
     *
     * @param  array<int, array{mod_id: string, name: string, is_passive: bool, is_enabled: bool}>  $mods
     */
    public function store(GameServerProfile $profile, Request $request): JsonResponse
    {
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

    /**
     * Search CurseForge for ARK mods.
     */
    public function search(GameServerProfile $profile, Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'page' => ['nullable', 'integer', 'min:0'],
        ]);

        try {
            $service = CurseForgeService::make();

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

    /**
     * Fetch CurseForge details for installed mods (cached).
     */
    public function details(GameServerProfile $profile): JsonResponse
    {
        $mods = $profile->mods ?? [];

        if (empty($mods)) {
            return response()->json(['details' => []]);
        }

        try {
            $service = CurseForgeService::make();
            $modIds = array_map(fn ($m) => (int) $m['mod_id'], $mods);
            $details = $service->getModsByIds($modIds);

            return response()->json(['details' => $details]);
        } catch (\RuntimeException $e) {
            return response()->json(['details' => [], 'error' => $e->getMessage()]);
        }
    }
}
