<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TeamInvitationController extends Controller
{
    public function store(Request $request, Team $team): RedirectResponse
    {
        abort_unless($team->isOwner($request->user()), 403);

        $data = $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('team_invitations')->where('team_id', $team->id),
            ],
            'role' => ['sometimes', 'string', 'in:member,owner'],
        ]);

        $existingMember = User::where('email', $data['email'])->first();
        if ($existingMember && $team->hasUser($existingMember)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'This user is already a member.']);

            return back();
        }

        $team->invitations()->create([
            'email' => $data['email'],
            'role' => $data['role'] ?? 'member',
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Invitation sent.']);

        return to_route('teams.show', $team);
    }

    public function accept(Request $request, TeamInvitation $invitation): RedirectResponse
    {
        abort_unless($invitation->email === $request->user()->email, 403);

        $invitation->team->members()->attach($request->user()->id, [
            'role' => $invitation->role,
        ]);

        $invitation->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "You joined {$invitation->team->name}."]);

        return to_route('teams.index');
    }

    public function decline(Request $request, TeamInvitation $invitation): RedirectResponse
    {
        abort_unless($invitation->email === $request->user()->email, 403);

        $invitation->delete();

        Inertia::flash('toast', ['type' => 'info', 'message' => 'Invitation declined.']);

        return to_route('teams.index');
    }

    public function destroy(Request $request, TeamInvitation $invitation): RedirectResponse
    {
        abort_unless($invitation->team->isOwner($request->user()), 403);

        $invitation->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Invitation cancelled.']);

        return back();
    }
}
