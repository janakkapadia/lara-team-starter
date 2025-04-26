<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamInvitationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])->name('password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance');

    Route::post('settings/teams/{team}', [TeamController::class, 'switch'])->name('teams.switch');
    Route::delete('settings/teams/{team}/users/{user}', [TeamController::class, 'removeMember'])->name('team-members.remove');

    Route::post('settings/teams/{team}/invite', [TeamInvitationController::class, 'invite'])->name('team-members.invite');
    Route::delete('settings/teams/{team}/invitations/{invitation}', [TeamInvitationController::class, 'cancelInvitation'])->name('team-members.cancel-invitation');

    Route::resource('settings/teams', TeamController::class)->except(['create']);
});

Route::get('team-invitations/{token}', [TeamInvitationController::class, 'accept'])->name('team-invitations.accept');
Route::post('team-invitations/{token}/register', [TeamInvitationController::class, 'register'])->name('team-invitations.register');

