<?php

use App\Models\GameServer;
use App\Models\GameServerProfile;
use App\Models\Integration;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->server = GameServer::factory()->active()->create();
    $this->profile = GameServerProfile::factory()->active()->create([
        'game_server_id' => $this->server->id,
    ]);
});

test('mods index renders the Mods page', function () {
    $this->get(route('profiles.mods', $this->profile))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('profiles/Mods')
            ->has('profile')
            ->has('server')
            ->where('mods', [])
        );
});

test('mods index returns existing mods', function () {
    $mods = [
        ['mod_id' => '928793', 'name' => 'Structures Plus', 'is_passive' => false],
        ['mod_id' => '899987', 'name' => 'Dino Storage', 'is_passive' => true],
    ];

    $this->profile->update(['mods' => $mods]);

    $this->get(route('profiles.mods', $this->profile))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('profiles/Mods')
            ->where('mods', $mods)
        );
});

test('store saves the mod list', function () {
    $mods = [
        ['mod_id' => '928793', 'name' => 'Structures Plus', 'is_passive' => false, 'is_enabled' => true],
        ['mod_id' => '899987', 'name' => 'Dino Storage', 'is_passive' => true, 'is_enabled' => true],
    ];

    $this->postJson(route('profiles.mods.store', $this->profile), ['mods' => $mods])
        ->assertOk()
        ->assertJson(['success' => true, 'mods' => $mods]);

    expect($this->profile->fresh()->mods)->toBe($mods);
});

test('store saves disabled mods without removing them', function () {
    $mods = [
        ['mod_id' => '928793', 'name' => 'Structures Plus', 'is_passive' => false, 'is_enabled' => false],
        ['mod_id' => '899987', 'name' => 'Dino Storage', 'is_passive' => true, 'is_enabled' => true],
    ];

    $this->postJson(route('profiles.mods.store', $this->profile), ['mods' => $mods])
        ->assertOk()
        ->assertJson(['success' => true, 'mods' => $mods]);

    expect($this->profile->fresh()->mods)->toHaveCount(2);
    expect($this->profile->fresh()->mods[0]['is_enabled'])->toBeFalse();
});

test('store validates mod_id is required', function () {
    $mods = [
        ['mod_id' => '', 'name' => 'Missing ID', 'is_passive' => false, 'is_enabled' => true],
    ];

    $this->postJson(route('profiles.mods.store', $this->profile), ['mods' => $mods])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['mods.0.mod_id']);
});

test('store validates name is required', function () {
    $mods = [
        ['mod_id' => '928793', 'name' => '', 'is_passive' => false, 'is_enabled' => true],
    ];

    $this->postJson(route('profiles.mods.store', $this->profile), ['mods' => $mods])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['mods.0.name']);
});

test('store validates is_enabled is required', function () {
    $mods = [
        ['mod_id' => '928793', 'name' => 'Test', 'is_passive' => false],
    ];

    $this->postJson(route('profiles.mods.store', $this->profile), ['mods' => $mods])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['mods.0.is_enabled']);
});

test('store allows an empty mod list', function () {
    $this->profile->update(['mods' => [
        ['mod_id' => '928793', 'name' => 'Structures Plus', 'is_passive' => false],
    ]]);

    $this->postJson(route('profiles.mods.store', $this->profile), ['mods' => []])
        ->assertOk()
        ->assertJson(['success' => true]);

    expect($this->profile->fresh()->mods)->toBe([]);
});

test('store requires authentication', function () {
    auth()->logout();

    $this->postJson(route('profiles.mods.store', $this->profile), ['mods' => []])
        ->assertUnauthorized();
});

test('mods index includes hasCurseforgeIntegration flag when integration exists', function () {
    Integration::factory()->create([
        'provider' => 'curseforge',
        'is_active' => true,
    ]);

    $this->get(route('profiles.mods', $this->profile))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('hasCurseforgeIntegration', true)
        );
});

test('mods index hasCurseforgeIntegration is false without integration', function () {
    $this->get(route('profiles.mods', $this->profile))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('hasCurseforgeIntegration', false)
        );
});

test('search requires authentication', function () {
    auth()->logout();

    $this->getJson(route('profiles.mods.search', $this->profile))
        ->assertUnauthorized();
});

test('search returns error when no curseforge integration exists', function () {
    $this->getJson(route('profiles.mods.search', $this->profile))
        ->assertUnprocessable()
        ->assertJsonStructure(['error']);
});

test('search validates query parameters', function () {
    Integration::factory()->create([
        'provider' => 'curseforge',
        'is_active' => true,
    ]);

    $this->getJson(route('profiles.mods.search', [
        'profile' => $this->profile->id,
        'q' => str_repeat('x', 201),
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['q']);
});
