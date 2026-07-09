<?php

use App\Models\Team;
use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('new users get a personal team on registration', function () {
    $this->post(route('register.store'), [
        'name' => 'Team Tester',
        'email' => 'teamtest@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::where('email', 'teamtest@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->currentTeam)->not->toBeNull();
    expect($user->currentTeam->personal_team)->toBeTrue();
    expect($user->currentTeam->user_id)->toBe($user->id);
    expect(Team::where('user_id', $user->id)->count())->toBe(1);
});
