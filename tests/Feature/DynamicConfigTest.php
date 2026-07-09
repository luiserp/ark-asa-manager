<?php

use App\Models\GameServer;
use App\Models\GameServerProfile;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->user->createPersonalTeam();
    $this->actingAs($this->user);
    $this->team = $this->user->currentTeam;
    $this->server = GameServer::factory()->create(['team_id' => $this->team->id]);
    $this->user->switchServer($this->server);
    $this->profile = GameServerProfile::factory()->active()->create([
        'game_server_id' => $this->server->id,
    ]);
});

test('dynamic config page renders for a profile', function () {
    $this->get(route('profiles.dynamic-config', $this->profile))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('profiles/DynamicConfig')
            ->has('profile')
            ->has('schema')
            ->has('serveUrl')
        );
});

test('active dynamic config page redirects when no active profile', function () {
    GameServerProfile::where('game_server_id', $this->server->id)->update(['is_active' => false]);

    $this->get(route('profiles.active-dynamic-config'))
        ->assertRedirect(route('profiles.index'));
});

test('active dynamic config page renders for active profile', function () {
    GameServerProfile::where('game_server_id', $this->server->id)
        ->where('id', '!=', $this->profile->id)
        ->update(['is_active' => false]);

    $this->get(route('profiles.active-dynamic-config'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('profiles/DynamicConfig')
            ->where('profile.id', $this->profile->id)
        );
});

test('update saves dynamic config settings', function () {
    $this->postJson(route('profiles.dynamic-config.update', $this->profile), [
        'dynamic_config_enabled' => true,
        'dynamic_config' => [
            'XPMultiplier' => 2.0,
            'TamingSpeedMultiplier' => 3.0,
        ],
    ])->assertOk()
        ->assertJson(['success' => true]);

    $this->profile->refresh();

    expect($this->profile->dynamic_config_enabled)->toBeTrue();
    expect((float) $this->profile->dynamic_config['XPMultiplier'])->toBe(2.0);
    expect((float) $this->profile->dynamic_config['TamingSpeedMultiplier'])->toBe(3.0);
});

test('update validates dynamic_config_enabled is required', function () {
    $this->postJson(route('profiles.dynamic-config.update', $this->profile), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['dynamic_config_enabled']);
});

test('update rejects non-boolean dynamic_config_enabled', function () {
    $this->postJson(route('profiles.dynamic-config.update', $this->profile), [
        'dynamic_config_enabled' => 'not-a-bool',
    ])->assertUnprocessable();
});

test('serve endpoint returns empty text when no config set', function () {
    $this->profile->update([
        'dynamic_config_enabled' => true,
        'dynamic_config' => null,
    ]);

    $response = $this->get(route('dynamic-config.serve', $this->profile));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');
    expect($response->getContent())->toBe('');
});

test('serve endpoint returns ini content for non-default values', function () {
    $this->profile->update([
        'dynamic_config_enabled' => true,
        'dynamic_config' => [
            'XPMultiplier' => 2.5,
            'TamingSpeedMultiplier' => 5.0,
            'HarvestAmountMultiplier' => 1.0,
            'bDisableDinoDecayPvE' => true,
        ],
    ]);

    $response = $this->get(route('dynamic-config.serve', $this->profile));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');

    $content = $response->getContent();

    expect($content)->toContain('XPMultiplier=2.5');
    expect($content)->toContain('TamingSpeedMultiplier=5');
    expect($content)->toContain('bDisableDinoDecayPvE=True');
    expect($content)->not->toContain('HarvestAmountMultiplier');
});

test('serve endpoint skips default values', function () {
    $this->profile->update([
        'dynamic_config_enabled' => true,
        'dynamic_config' => [
            'XPMultiplier' => 1.0,
            'TamingSpeedMultiplier' => 1.0,
        ],
    ]);

    $response = $this->get(route('dynamic-config.serve', $this->profile));

    expect($response->getContent())->toBe('');
});

test('serve endpoint is public (no auth required)', function () {
    auth()->logout();

    $this->profile->update([
        'dynamic_config_enabled' => true,
        'dynamic_config' => ['XPMultiplier' => 3.0],
    ]);

    $response = $this->get(route('dynamic-config.serve', $this->profile));

    $response->assertOk();
    expect($response->getContent())->toContain('XPMultiplier=3');
});

test('update is forbidden for profiles on another team', function () {
    $otherUser = User::factory()->create();
    $otherUser->createPersonalTeam();
    $otherTeam = $otherUser->currentTeam;
    $otherServer = GameServer::factory()->create(['team_id' => $otherTeam->id]);
    $otherProfile = GameServerProfile::factory()->create(['game_server_id' => $otherServer->id]);

    $this->postJson(route('profiles.dynamic-config.update', $otherProfile), [
        'dynamic_config_enabled' => true,
        'dynamic_config' => ['XPMultiplier' => 2.0],
    ])->assertForbidden();
});
