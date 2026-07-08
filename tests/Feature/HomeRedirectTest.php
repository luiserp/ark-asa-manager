<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('home redirects to register when no users exist', function () {
    $this->get('/')
        ->assertRedirect(route('register'));
});

test('home redirects to login when users exist', function () {
    User::factory()->create();

    $this->get('/')
        ->assertRedirect(route('login'));
});

test('home redirects to dashboard when authenticated', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/')
        ->assertRedirect(route('dashboard'));
});
