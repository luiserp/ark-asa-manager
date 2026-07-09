<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TeamInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('teams/Index', [
            'teams' => $user->teams()->withCount('members')->get()->map(fn (Team $team) => [
                'id' => $team->id,
                'name' => $team->name,
                'personal_team' => $team->personal_team,
                'role' => $team->pivot->role,
                'members_count' => $team->members_count,
                'is_owner' => $team->pivot->role === 'owner',
                'is_creator' => $team->user_id === $user->id,
                'is_current' => $team->id === $user->current_team_id,
            ]),
            'pendingInvitations' => TeamInvitation::where('email', $user->email)
                ->with('team:id,name')
                ->get(),
        ]);
    }

    public function show(Request $request, Team $team): Response
    {
        $user = $request->user();
        abort_unless($team->hasUser($user), 403);

        return Inertia::render('teams/Show', [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'personal_team' => $team->personal_team,
                'is_owner' => $team->isOwner($user),
                'is_creator' => $team->isCreator($user),
                'owner_id' => $team->user_id,
            ],
            'members' => $team->members()->select('users.id', 'users.name', 'users.email')->get()->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'email' => $m->email,
                'role' => $m->pivot->role,
            ]),
            'invitations' => $team->isOwner($user)
                ? $team->invitations()->latest()->get()
                : [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $team = $request->user()->ownedTeams()->create($data);
        $team->members()->attach($request->user()->id, ['role' => 'owner']);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Team created.']);

        return to_route('teams.index');
    }

    public function update(Request $request, Team $team): RedirectResponse
    {
        abort_unless($team->isOwner($request->user()), 403);
        abort_if($team->personal_team, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $team->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Team updated.']);

        return to_route('teams.show', $team);
    }

    public function destroy(Request $request, Team $team): RedirectResponse
    {
        abort_unless($team->isCreator($request->user()), 403);
        abort_if($team->personal_team, 403);

        $user = $request->user();

        if ($user->current_team_id === $team->id) {
            $personalTeam = $user->ownedTeams()->where('personal_team', true)->firstOrFail();
            $user->switchTeam($personalTeam);
        }

        $team->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Team deleted.']);

        return to_route('teams.index');
    }

    public function switchTeam(Request $request, Team $team): RedirectResponse
    {
        abort_unless($team->hasUser($request->user()), 403);

        $request->user()->switchTeam($team);

        return to_route('dashboard');
    }

    public function updateMemberRole(Request $request, Team $team, int $userId): RedirectResponse
    {
        abort_unless($team->isOwner($request->user()), 403);
        abort_if($userId === $request->user()->id, 403);

        $data = $request->validate([
            'role' => ['required', 'string', 'in:member,owner'],
        ]);

        $team->members()->updateExistingPivot($userId, ['role' => $data['role']]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Role updated.']);

        return to_route('teams.show', $team);
    }

    public function removeMember(Request $request, Team $team, int $userId): RedirectResponse
    {
        abort_unless($team->isOwner($request->user()), 403);
        abort_if($userId === $request->user()->id, 403);

        $team->members()->detach($userId);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Member removed.']);

        return to_route('teams.show', $team);
    }

    public function leave(Request $request, Team $team): RedirectResponse
    {
        $user = $request->user();
        abort_if($team->isCreator($user), 403);

        $team->members()->detach($user->id);

        if ($user->current_team_id === $team->id) {
            $personalTeam = $user->ownedTeams()->where('personal_team', true)->firstOrFail();
            $user->switchTeam($personalTeam);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'You have left the team.']);

        return to_route('teams.index');
    }
}
