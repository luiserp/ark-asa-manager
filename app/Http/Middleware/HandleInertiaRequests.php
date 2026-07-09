<?php

namespace App\Http\Middleware;

use App\Models\GameServerProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * @var string
     */
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();
        $team = $user?->currentTeam;
        $activeServer = $user?->currentServer();
        $activeProfile = $activeServer
            ? GameServerProfile::where('game_server_id', $activeServer->id)->active()->first()
            : null;

        $teamServers = $team ? $team->servers()->select('id', 'name')->get() : collect();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'currentTeam' => $team ? [
                'id' => $team->id,
                'name' => $team->name,
                'personal_team' => $team->personal_team,
                'is_owner' => $user ? $team->isOwner($user) : false,
            ] : null,
            'teams' => $user
                ? $user->teams()->select('teams.id', 'teams.name', 'teams.personal_team')->get()->map(fn ($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'personal_team' => $t->personal_team,
                ])
                : [],
            'counts' => [
                'servers' => $teamServers->count(),
                'profiles' => $activeServer ? GameServerProfile::where('game_server_id', $activeServer->id)->count() : 0,
                'mods' => $activeProfile ? count($activeProfile->mods ?? []) : 0,
            ],
            'servers' => $teamServers,
            'activeServer' => $activeServer ? [
                'id' => $activeServer->id,
                'name' => $activeServer->name,
            ] : null,
            'profiles' => $activeServer
                ? $activeServer->profiles()->select('id', 'name')->get()
                : [],
            'activeProfile' => $activeProfile ? [
                'id' => $activeProfile->id,
                'name' => $activeProfile->name,
            ] : null,
        ];
    }
}
