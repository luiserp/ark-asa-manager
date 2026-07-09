<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GameServerController;
use App\Http\Controllers\GameServerProfileController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\ProfileConfigController;
use App\Http\Controllers\ProfileModsController;
use App\Http\Controllers\ServerDetailController;
use App\Http\Controllers\ServerFilesController;
use App\Http\Controllers\ServerProcessController;
use App\Http\Controllers\ServerRconController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamInvitationController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');

})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Teams
    Route::resource('teams', TeamController::class)->except(['create', 'edit']);
    Route::post('teams/{team}/switch', [TeamController::class, 'switchTeam'])->name('teams.switch');
    Route::post('teams/{team}/leave', [TeamController::class, 'leave'])->name('teams.leave');
    Route::patch('teams/{team}/members/{userId}/role', [TeamController::class, 'updateMemberRole'])->name('teams.update-member-role');
    Route::delete('teams/{team}/members/{userId}', [TeamController::class, 'removeMember'])->name('teams.remove-member');

    // Team Invitations
    Route::post('teams/{team}/invitations', [TeamInvitationController::class, 'store'])->name('team-invitations.store');
    Route::post('team-invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('team-invitations.accept');
    Route::post('team-invitations/{invitation}/decline', [TeamInvitationController::class, 'decline'])->name('team-invitations.decline');
    Route::delete('team-invitations/{invitation}', [TeamInvitationController::class, 'destroy'])->name('team-invitations.destroy');

    // Servers
    Route::patch('servers/{server}/toggle-active', [GameServerController::class, 'toggleActive'])->name('servers.toggle-active');
    Route::post('servers/{server}/replicate', [GameServerController::class, 'replicate'])->name('servers.replicate');
    Route::post('servers/{server}/transfer', [GameServerController::class, 'transfer'])->name('servers.transfer');
    Route::resource('servers', GameServerController::class)->except(['show']);

    Route::get('servers/active/detail', [ServerDetailController::class, 'active'])->name('servers.active-detail');
    Route::get('servers/{server}/detail', [ServerDetailController::class, 'index'])->name('servers.detail');
    Route::post('servers/{server}/install', [ServerDetailController::class, 'install'])->name('servers.install');
    Route::post('servers/{server}/start', [ServerDetailController::class, 'start'])->name('servers.start');
    Route::post('servers/{server}/stop', [ServerDetailController::class, 'stop'])->name('servers.stop');
    Route::post('servers/{server}/leave', [ServerDetailController::class, 'leave'])->name('servers.leave');

    Route::get('servers/active/files', [ServerFilesController::class, 'active'])->name('servers.active-files');
    Route::get('servers/{server}/files', [ServerFilesController::class, 'index'])->name('servers.files');
    Route::get('servers/{server}/files/read', [ServerFilesController::class, 'read'])->name('servers.files.read');
    Route::post('servers/{server}/files/write', [ServerFilesController::class, 'write'])->name('servers.files.write');
    Route::get('servers/{server}/files/download', [ServerFilesController::class, 'download'])->name('servers.files.download');

    Route::get('servers/active/processes', [ServerProcessController::class, 'active'])->name('servers.active-processes');
    Route::get('servers/{server}/processes', [ServerProcessController::class, 'index'])->name('servers.processes');
    Route::delete('servers/{server}/processes', [ServerProcessController::class, 'kill'])->name('servers.processes.kill');

    Route::get('servers/active/rcon', [ServerRconController::class, 'active'])->name('servers.active-rcon');
    Route::get('servers/{server}/rcon', [ServerRconController::class, 'index'])->name('servers.rcon');
    Route::post('servers/{server}/rcon', [ServerRconController::class, 'execute'])->name('servers.rcon.execute');

    // Integrations
    Route::resource('integrations', IntegrationController::class)->except(['show']);

    // Profiles
    Route::resource('profiles', GameServerProfileController::class)->except(['show']);
    Route::patch('profiles/{profile}/toggle-active', [GameServerProfileController::class, 'toggleActive'])->name('profiles.toggle-active');
    Route::post('profiles/{profile}/replicate', [GameServerProfileController::class, 'replicate'])->name('profiles.replicate');

    Route::get('profiles/active/config', [ProfileConfigController::class, 'active'])->name('profiles.active-config');
    Route::get('profiles/{profile}/config', [ProfileConfigController::class, 'index'])->name('profiles.config');
    Route::get('profiles/{profile}/config/pull', [ProfileConfigController::class, 'pull'])->name('profiles.config.pull');
    Route::post('profiles/{profile}/config/push', [ProfileConfigController::class, 'push'])->name('profiles.config.push');
    Route::post('profiles/{profile}/config/apply', [ProfileConfigController::class, 'apply'])->name('profiles.config.apply');
    Route::post('profiles/{profile}/config/import', [ProfileConfigController::class, 'importFromServer'])->name('profiles.config.import');

    Route::get('profiles/active/mods', [ProfileModsController::class, 'active'])->name('profiles.active-mods');
    Route::get('profiles/{profile}/mods', [ProfileModsController::class, 'index'])->name('profiles.mods');
    Route::post('profiles/{profile}/mods', [ProfileModsController::class, 'store'])->name('profiles.mods.store');
    Route::get('profiles/{profile}/mods/search', [ProfileModsController::class, 'search'])->name('profiles.mods.search');
    Route::get('profiles/{profile}/mods/details', [ProfileModsController::class, 'details'])->name('profiles.mods.details');
});

require __DIR__.'/settings.php';
