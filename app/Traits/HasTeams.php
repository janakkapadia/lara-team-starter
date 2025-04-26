<?php

namespace App\Traits;

use App\Models\Team;
use App\Models\TeamInvitation;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasTeams
{
    /**
     * Get all teams the user belongs to.
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_users')
            ->withTimestamps();
    }

    /**
     * Get all users that belong to the team.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_users')
            ->withPivot('role')
            ->withTimestamps();
    }


    /**
     * Get the user's owned teams.
     */
    public function ownedTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'user_id');
    }

    /**
     * Get the user's current team.
     */
    public function currentTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'current_team_id');
    }

    /**
     * Determine if the user belongs to a given team.
     */
    public function belongsToTeam(Team $team): bool
    {
        return $this->teams->contains($team);
    }

    /**
     * Assign the user to a given team.
     */
    public function assignTeam(Team $team, $role = 'member'): void
    {
        $this->teams()->attach([$team->id], ['role' => $role]);
    }

    /**
     * Remove the user from a given team.
     */
    public function removeFromTeam(Team $team): void
    {
        $this->teams()->detach($team->id);

        // If the removed team is the current team, reset current team ID
        if ($this->current_team_id === $team->id) {
            $ownedTeam = $this->ownedTeams()->first();
            $this->update(['current_team_id' => $ownedTeam->id]);
        }
    }

    /**
     * Invite a user to a team.
     */
    public function inviteToTeam(Team $team, $email, $role = 'member'): void
    {
        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => $email,
            'role' => $role,
            'invited_by' => $this->id,
        ]);

        // Send invitation email
        \Notification::route('mail', $email)
            ->notify(new \App\Notifications\TeamInvitationNotification($invitation));
    }

    /**
     * Accept a team invitation.
     */
    public function acceptTeamInvitation(TeamInvitation $invitation): void
    {
        if ($invitation->email !== $this->email) {
            throw new \Exception("You are not authorized to accept this invitation.");
        }

        $this->assignTeam($invitation->team);
        $invitation->delete();
    }

    /**
     * Revoke a team invitation.
     */
    public function revokeTeamInvitation(TeamInvitation $invitation): void
    {
        $invitation->delete();
    }

    /**
     * Set the current team for the user.
     */
    public function setCurrentTeam(Team $team): void
    {
        if (!$this->belongsToTeam($team)) {
            throw new \Exception("User does not belong to this team.");
        }

        $this->update(['current_team_id' => $team->id]);
    }

    /**
     * Ensure the user has a personal team.
     */
    public function ensurePersonalTeam(): void
    {
        if (!$this->personalTeam()) {
            $team = Team::create([
                'name' => "{$this->name}'s Team",
                'user_id' => $this->id,
            ]);

            $this->assignTeam($team);
            $this->update(['current_team_id' => $team->id]);
        }
    }

    /**
     * Get the user's personal team.
     */
    public function personalTeam(): ?Team
    {
        return $this->teams()->where('user_id', $this->id)->first();
    }

    public function authUserTeams()
    {
        if (!request()->user()) {
            return [];
        }
        $teams = request()->user()->teams->map(function ($team) {
            return [
                'id' => $team->id,
                'name' => $team->name,
                'role' => $team->pivot->role ?? 'member',
                'is_owner' => false,  // These are not owned by the user
            ];
        })->values();

        $ownedTeams = request()->user()->ownedTeams->map(function ($team) {
            return [
                'id' => $team->id,
                'name' => $team->name,
                'role' => 'admin',
                'is_owner' => true,  // These teams are owned by the user
            ];
        })->values();

        unset(request()->user()->teams);
        unset(request()->user()->ownedTeams);
        // Merge the two collections
        if ($ownedTeams->isEmpty()) {
            return $teams;
        }
        return $ownedTeams->merge($teams);
    }
}
