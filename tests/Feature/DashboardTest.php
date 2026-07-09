<?php

use App\Models\GameServer;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->user->createPersonalTeam();
    $this->team = $this->user->fresh()->currentTeam;
    $this->actingAs($this->user);
});

test('guests are redirected to the login page', function () {
    auth()->logout();

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard returns team stats', function () {
    $this->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('teamStats', fn (Assert $stats) => $stats
                ->where('members', 1)
                ->where('servers', 0)
                ->where('profiles', 0)
                ->where('integrations', 0)
            )
        );
});

test('dashboard returns server list from database', function () {
    GameServer::factory()->for($this->team)->create(['name' => 'Test Server']);

    $this->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('servers', 1)
            ->has('servers.0', fn (Assert $server) => $server
                ->where('name', 'Test Server')
                ->has('id')
                ->has('host')
                ->has('is_current')
                ->has('profile_count')
                ->has('active_profile')
            )
            ->where('teamStats.servers', 1)
        );
});

test('dashboard defers server metrics', function () {
    GameServer::factory()->for($this->team)->create();

    $this->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->missing('serverMetrics')
        );
});
