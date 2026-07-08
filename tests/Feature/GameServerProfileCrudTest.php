<?php

use App\Models\GameServer;
use App\Models\GameServerProfile;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->server = GameServer::factory()->active()->create();
});

test('default profile is created when server is created', function () {
    $server = GameServer::factory()->create();

    expect($server->profiles)->toHaveCount(1);
    expect($server->profiles->first()->name)->toBe('Default');
    expect($server->profiles->first()->is_active)->toBeTrue();
});

test('index displays profiles for the active server', function () {
    GameServerProfile::factory()->count(2)->create(['game_server_id' => $this->server->id]);

    $this->get(route('profiles.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('profiles/Index')
            ->has('profiles', 3) // 2 + the auto-created default
        );
});

test('create displays form with defaults', function () {
    $this->get(route('profiles.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('profiles/Create')
            ->has('defaults')
            ->has('maps')
        );
});

test('store creates a new profile', function () {
    $data = [
        'name' => 'PvP Hardcore',
        'map' => 'ScorchedEarth_WP',
        'game_port' => 7777,
        'query_port' => 27015,
        'rcon_port' => 27020,
        'session_name' => 'Hardcore PvP',
        'admin_password' => 'admin123',
        'server_password' => null,
        'max_players' => 40,
    ];

    $this->post(route('profiles.store'), $data)
        ->assertRedirect(route('profiles.index'));

    $this->assertDatabaseHas('game_server_profiles', [
        'name' => 'PvP Hardcore',
        'map' => 'ScorchedEarth_WP',
        'game_server_id' => $this->server->id,
    ]);
});

test('store validates required fields', function () {
    $this->post(route('profiles.store'), [])
        ->assertSessionHasErrors(['name', 'map', 'game_port', 'query_port', 'rcon_port', 'session_name', 'max_players']);
});

test('edit displays profile form', function () {
    $profile = GameServerProfile::factory()->create(['game_server_id' => $this->server->id]);

    $this->get(route('profiles.edit', $profile))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('profiles/Edit')
            ->has('profile')
            ->has('maps')
        );
});

test('update modifies a profile', function () {
    $profile = GameServerProfile::factory()->create([
        'game_server_id' => $this->server->id,
        'name' => 'Old Name',
    ]);

    $data = [
        'name' => 'New Name',
        'map' => $profile->map,
        'game_port' => $profile->game_port,
        'query_port' => $profile->query_port,
        'rcon_port' => $profile->rcon_port,
        'session_name' => $profile->session_name,
        'admin_password' => $profile->admin_password,
        'server_password' => $profile->server_password,
        'max_players' => $profile->max_players,
    ];

    $this->put(route('profiles.update', $profile), $data)
        ->assertRedirect(route('profiles.index'));

    $this->assertDatabaseHas('game_server_profiles', [
        'id' => $profile->id,
        'name' => 'New Name',
    ]);
});

test('destroy deletes a profile', function () {
    $profile = GameServerProfile::factory()->create(['game_server_id' => $this->server->id]);

    $this->delete(route('profiles.destroy', $profile))
        ->assertRedirect(route('profiles.index'));

    $this->assertDatabaseMissing('game_server_profiles', ['id' => $profile->id]);
});

test('toggle active activates an inactive profile', function () {
    $profile = GameServerProfile::factory()->create([
        'game_server_id' => $this->server->id,
        'is_active' => false,
    ]);

    $this->patch(route('profiles.toggle-active', $profile))
        ->assertRedirect();

    expect($profile->fresh()->is_active)->toBeTrue();
});

test('toggle active deactivates other profiles when activating', function () {
    $active = GameServerProfile::factory()->active()->create(['game_server_id' => $this->server->id]);
    $inactive = GameServerProfile::factory()->create([
        'game_server_id' => $this->server->id,
        'is_active' => false,
    ]);

    $this->patch(route('profiles.toggle-active', $inactive))
        ->assertRedirect();

    expect($inactive->fresh()->is_active)->toBeTrue();
    expect($active->fresh()->is_active)->toBeFalse();
});

test('toggle active deactivates an active profile', function () {
    $profile = GameServerProfile::factory()->active()->create(['game_server_id' => $this->server->id]);

    $this->patch(route('profiles.toggle-active', $profile))
        ->assertRedirect();

    expect($profile->fresh()->is_active)->toBeFalse();
});

test('replicate duplicates a profile to the same server', function () {
    $profile = GameServerProfile::factory()->create([
        'game_server_id' => $this->server->id,
        'name' => 'PvE Chill',
    ]);

    $this->post(route('profiles.replicate', $profile), [
        'game_server_id' => $this->server->id,
    ])->assertRedirect(route('profiles.index'));

    $clone = GameServerProfile::where('name', 'PvE Chill (Copy)')->first();

    expect($clone)->not->toBeNull();
    expect($clone->game_server_id)->toBe($this->server->id);
    expect($clone->is_active)->toBeFalse();
    expect($clone->map)->toBe($profile->map);
});

test('replicate duplicates a profile to a different server', function () {
    $otherServer = GameServer::factory()->create();
    $profile = GameServerProfile::factory()->create([
        'game_server_id' => $this->server->id,
        'name' => 'Hardcore',
    ]);

    $this->post(route('profiles.replicate', $profile), [
        'game_server_id' => $otherServer->id,
    ])->assertRedirect(route('profiles.index'));

    $clone = GameServerProfile::where('name', 'Hardcore (Copy)')->first();

    expect($clone)->not->toBeNull();
    expect($clone->game_server_id)->toBe($otherServer->id);
});
