<?php

use App\Models\GameServer;
use App\Models\User;
use App\Services\Ark\ArkServerService;
use App\Services\SshService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('GameServer model can be created with factory', function () {
    $server = GameServer::factory()->create(['name' => 'Test Server']);

    expect($server)
        ->name->toBe('Test Server')
        ->port->toBe(22)
        ->username->toBe('ark-sa')
        ->steam_app_id->toBe('2430930');
});

test('GameServer active scope returns only active servers', function () {
    GameServer::factory()->count(3)->create();
    GameServer::factory()->active()->create(['name' => 'Primary']);

    $active = GameServer::active()->get();

    expect($active)->toHaveCount(1)
        ->first()->name->toBe('Primary');
});

test('GameServer factory has localDocker state', function () {
    $server = GameServer::factory()->localDocker()->create();

    expect($server)
        ->host->toBe('ark-server')
        ->port->toBe(22)
        ->ssh_key_path->toBe('/shared-ssh/id_ed25519');
});

test('SshService::forServer creates instance from GameServer model', function () {
    $server = GameServer::factory()->make([
        'host' => '10.0.0.5',
        'port' => 2222,
        'username' => 'custom-user',
        'ssh_key_path' => '/path/to/key',
    ]);

    $ssh = SshService::forServer($server);

    expect($ssh)->toBeInstanceOf(SshService::class);
});

test('ArkServerService::forServer creates instance from GameServer model', function () {
    $server = GameServer::factory()->make([
        'host' => '10.0.0.5',
        'port' => 2222,
        'username' => 'custom-user',
        'ssh_key_path' => '/path/to/key',
        'server_dir' => '/custom/server',
        'backups_dir' => '/custom/backups',
    ]);

    $ark = ArkServerService::forServer($server);

    expect($ark)->toBeInstanceOf(ArkServerService::class);
});

test('ArkServerService::make falls back to config when no default server exists', function () {
    $ark = ArkServerService::make();

    expect($ark)->toBeInstanceOf(ArkServerService::class);
});

test('ArkServerService::make uses active GameServer when one exists', function () {
    GameServer::factory()->active()->create([
        'host' => '192.168.1.100',
        'ssh_key_path' => base_path('ssh/id_ed25519'),
    ]);

    $ark = ArkServerService::make();

    expect($ark)->toBeInstanceOf(ArkServerService::class);
});

test('dashboard displays servers list', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('servers')
        );
});

test('dashboard shows each configured server', function () {
    $user = User::factory()->create();
    GameServer::factory()->count(2)->create([
        'ssh_key_path' => base_path('ssh/id_ed25519'),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('servers', 2)
        );
});
