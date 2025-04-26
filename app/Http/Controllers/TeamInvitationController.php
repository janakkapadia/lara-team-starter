<?php

namespace App\Http\Controllers;

use App\Models\TeamInvitation;
use App\Models\User;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRules;

class TeamInvitationController extends Controller
{
    public function accept(Request $request, string $token)
    {
        $invitation = TeamInvitation::where('token', $token)->firstOrFail();

        if ($invitation->isExpired()) {
            return redirect()->route('login')->with('error', 'This invitation has expired.');
        }

        // If user is not logged in, show registration form
        if (!$request->user()) {
            return inertia('auth/Register', [
                'invitation' => $invitation,
                'email' => $invitation->email,
            ]);
        }

        // If user is logged in but email doesn't match
        if ($request->user()->email !== $invitation->email) {
            return redirect()->route('login')->with('error', 'This invitation was sent to a different email address.');
        }

        // Accept the invitation
        $request->user()->assignTeam($invitation->team);
        $invitation->delete();

        return redirect()->route('teams.show', $invitation->team)
            ->with('success', 'You have successfully joined the team.');
    }

    public function register(Request $request, string $token)
    {
        $invitation = TeamInvitation::where('token', $token)->firstOrFail();

        if ($invitation->isExpired()) {
            return redirect()->route('login')->with('error', 'This invitation has expired.');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', PasswordRules::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'current_team_id' => $invitation->team->id,
        ]);

        $user->markEmailAsVerified();

        $team = $user->currentTeam()->create([
            'name' => "My Team",
            'user_id' => $user->id,
            'personal_team' => true,
        ]);

        // Accept the invitation
        $user->assignTeam($invitation->team, $invitation->role);
        $invitation->delete();

        auth()->login($user);

        return redirect()->route('teams.edit', $invitation->team)
            ->with('success', 'Your account has been created and you have joined the team.');
    }

    public function invite(Request $request, Team $team)
    {
        $request->validate([
            'email' => [
                'required',
                'email',
                function ($attribute, $value, $fail) use ($request, $team) {
                    if ($value === $request->user()->email) {
                        $fail('You cannot invite yourself.');
                    }

                    if ($team->members()->where('email', $value)->exists()) {
                        $fail('This user is already a member of the team.');
                    }

                    if ($team->invitations()->where('email', $value)->exists()) {
                        $fail('This user has already been invited.');
                    }
                },
            ],
            'role' => 'required|in:admin,member',
        ]);

        $request->user()->inviteToTeam($team, $request->email, $request->role);

        return back();
    }

    public function cancelInvitation(Request $request, Team $team, TeamInvitation $invitation)
    {
        $request->user()->revokeTeamInvitation($invitation);
        return back();
    }
}
