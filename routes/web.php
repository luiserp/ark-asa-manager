<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GameServerController;
use App\Http\Controllers\GameServerProfileController;
use App\Http\Controllers\ProfileConfigController;
use App\Http\Controllers\ServerDetailController;
use App\Http\Controllers\ServerFilesController;
use App\Http\Controllers\ServerProcessController;
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

    Route::patch('servers/{server}/toggle-active', [GameServerController::class, 'toggleActive'])->name('servers.toggle-active');
    Route::post('servers/{server}/replicate', [GameServerController::class, 'replicate'])->name('servers.replicate');
    Route::resource('servers', GameServerController::class)->except(['show']);

    Route::get('servers/{server}/detail', [ServerDetailController::class, 'index'])->name('servers.detail');
    Route::post('servers/{server}/install', [ServerDetailController::class, 'install'])->name('servers.install');
    Route::post('servers/{server}/start', [ServerDetailController::class, 'start'])->name('servers.start');
    Route::post('servers/{server}/stop', [ServerDetailController::class, 'stop'])->name('servers.stop');
    Route::post('servers/{server}/leave', [ServerDetailController::class, 'leave'])->name('servers.leave');

    Route::get('servers/{server}/files', [ServerFilesController::class, 'index'])->name('servers.files');
    Route::get('servers/{server}/files/read', [ServerFilesController::class, 'read'])->name('servers.files.read');
    Route::post('servers/{server}/files/write', [ServerFilesController::class, 'write'])->name('servers.files.write');
    Route::get('servers/{server}/files/download', [ServerFilesController::class, 'download'])->name('servers.files.download');

    Route::get('servers/{server}/processes', [ServerProcessController::class, 'index'])->name('servers.processes');
    Route::delete('servers/{server}/processes', [ServerProcessController::class, 'kill'])->name('servers.processes.kill');

    Route::resource('profiles', GameServerProfileController::class)->except(['show']);
    Route::patch('profiles/{profile}/toggle-active', [GameServerProfileController::class, 'toggleActive'])->name('profiles.toggle-active');
    Route::post('profiles/{profile}/replicate', [GameServerProfileController::class, 'replicate'])->name('profiles.replicate');

    Route::get('profiles/{profile}/config', [ProfileConfigController::class, 'index'])->name('profiles.config');
    Route::get('profiles/{profile}/config/pull', [ProfileConfigController::class, 'pull'])->name('profiles.config.pull');
    Route::post('profiles/{profile}/config/push', [ProfileConfigController::class, 'push'])->name('profiles.config.push');
});

require __DIR__.'/settings.php';
