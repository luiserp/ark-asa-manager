<?php

use App\Models\GameServer;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->user->createPersonalTeam();
    $this->actingAs($this->user);
    $this->team = $this->user->currentTeam;
    Storage::fake('local');
});

test('index displays servers list', function () {
    GameServer::factory()->count(3)->create(['team_id' => $this->team->id]);

    $this->get(route('servers.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('servers/Index')
            ->has('servers', 3)
        );
});

test('create displays form with defaults', function () {
    $this->get(route('servers.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('servers/Create')
            ->has('defaults')
        );
});

test('store creates a new server with uploaded key', function () {
    $key = UploadedFile::fake()->create('id_ed25519', 1);

    $data = [
        'name' => 'Test Server',
        'host' => '192.168.1.100',
        'port' => 22,
        'username' => 'ark-sa',
        'ssh_key' => $key,
        'server_dir' => '/ark-server/server',
        'backups_dir' => '/ark-server/backups',
        'logs_dir' => '/ark-server/logs',
        'binary_path' => 'ShooterGame/Binaries/Win64/ArkAscendedServer.exe',
        'proton_path' => '/opt/glorious_eggroll/proton/bin/wine64',
        'steam_app_id' => '2430930',
    ];

    $this->post(route('servers.store'), $data)
        ->assertRedirect(route('servers.index'));

    $this->assertDatabaseHas('game_servers', [
        'name' => 'Test Server',
        'host' => '192.168.1.100',
        'team_id' => $this->team->id,
    ]);

    Storage::disk('local')->assertExists('ssh-keys/'.$key->hashName());
});

test('store validates required fields', function () {
    $this->post(route('servers.store'), [])
        ->assertSessionHasErrors(['name', 'host', 'port', 'username', 'ssh_key']);
});

test('edit displays server form', function () {
    $server = GameServer::factory()->create(['team_id' => $this->team->id]);

    $this->get(route('servers.edit', $server))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('servers/Edit')
            ->has('server')
        );
});

test('update modifies the server without replacing key', function () {
    $server = GameServer::factory()->create(['team_id' => $this->team->id, 'name' => 'Old Name']);

    $data = [
        'name' => 'New Name',
        'host' => $server->host,
        'port' => $server->port,
        'username' => $server->username,
        'server_dir' => $server->server_dir,
        'backups_dir' => $server->backups_dir,
        'logs_dir' => $server->logs_dir,
        'binary_path' => $server->binary_path,
        'proton_path' => $server->proton_path,
        'steam_app_id' => $server->steam_app_id,
    ];

    $this->put(route('servers.update', $server), $data)
        ->assertRedirect(route('servers.index'));

    $this->assertDatabaseHas('game_servers', [
        'id' => $server->id,
        'name' => 'New Name',
    ]);
});

test('update replaces ssh key when new file uploaded', function () {
    $server = GameServer::factory()->create(['team_id' => $this->team->id]);
    $newKey = UploadedFile::fake()->create('new_key', 1);

    $data = [
        'name' => $server->name,
        'host' => $server->host,
        'port' => $server->port,
        'username' => $server->username,
        'ssh_key' => $newKey,
        'server_dir' => $server->server_dir,
        'backups_dir' => $server->backups_dir,
        'logs_dir' => $server->logs_dir,
        'binary_path' => $server->binary_path,
        'proton_path' => $server->proton_path,
        'steam_app_id' => $server->steam_app_id,
    ];

    $this->put(route('servers.update', $server), $data)
        ->assertRedirect(route('servers.index'));

    Storage::disk('local')->assertExists('ssh-keys/'.$newKey->hashName());
});

test('destroy deletes the server', function () {
    $server = GameServer::factory()->create(['team_id' => $this->team->id]);

    $this->delete(route('servers.destroy', $server))
        ->assertRedirect(route('servers.index'));

    $this->assertDatabaseMissing('game_servers', ['id' => $server->id]);
});

test('first server is automatically set as current for user', function () {
    $key = UploadedFile::fake()->create('id_ed25519', 1);

    $data = [
        ...GameServer::factory()->make(['team_id' => $this->team->id])->toArray(),
        'ssh_key' => $key,
    ];

    $this->post(route('servers.store'), $data);

    $server = GameServer::first();
    expect($this->user->fresh()->currentServer()?->id)->toBe($server->id);
});

test('toggle active switches the current server for the user', function () {
    $server1 = GameServer::factory()->create(['team_id' => $this->team->id]);
    $server2 = GameServer::factory()->create(['team_id' => $this->team->id]);
    $this->user->switchServer($server1);

    $this->patch(route('servers.toggle-active', $server2))
        ->assertRedirect();

    expect($this->user->fresh()->currentServer()?->id)->toBe($server2->id);
});

test('replicate duplicates a server', function () {
    $server = GameServer::factory()->create(['team_id' => $this->team->id, 'name' => 'Production']);

    $this->post(route('servers.replicate', $server))
        ->assertRedirect(route('servers.index'));

    $clone = GameServer::where('name', 'Production (Copy)')->first();

    expect($clone)->not->toBeNull();
    expect($clone->host)->toBe($server->host);
    expect($clone->team_id)->toBe($this->team->id);
});

test('cannot access servers from another team', function () {
    $otherTeam = Team::factory()->create();
    $otherServer = GameServer::factory()->create(['team_id' => $otherTeam->id]);

    $this->get(route('servers.edit', $otherServer))
        ->assertForbidden();
});
