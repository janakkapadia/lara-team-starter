<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);

        $team = $request->user()->ownedTeams()->create([
            'name' => $request->name,
        ]);

        $request->user()->update([
            'current_team_id' => $team->id,
        ]);

        return back();
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Team $team)
    {
        $members = $team->members;
        $invitations = $team->invitations;
        return Inertia::render('settings/teams/Edit', [
            'team' => $team,
            'members' => $members,
            'invitations' => $invitations,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Team $team)
    {
        $request->validate([
            'name' => 'required',
        ]);

        $team->update(['name' => $request->name]);

        return back();
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $team = Team::findOrFail($id);
        $team->delete();
        return back();
    }

    public function switch(Request $request, Team $team)
    {
        $request->user()->update([
            'current_team_id' => $team->id,
        ]);

        return back();  
    }

    public function removeMember(Request $request, Team $team, User $user)
    {
        $user->removeFromTeam($team);
        return back();
    }
}
