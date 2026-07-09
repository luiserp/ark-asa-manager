<?php

use App\Models\Integration;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('index renders integrations page', function () {
    $this->get(route('integrations.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('integrations/Index')
            ->has('providers')
            ->where('integrations', [])
        );
});

test('index lists existing integrations', function () {
    Integration::factory()->create(['provider' => 'curseforge']);
    Integration::factory()->create(['provider' => 'openai']);

    $this->get(route('integrations.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('integrations', 2)
        );
});

test('create renders the form', function () {
    $this->get(route('integrations.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('integrations/Create')
            ->has('providers')
        );
});

test('store creates an integration', function () {
    $data = [
        'provider' => 'curseforge',
        'label' => 'My CurseForge Key',
        'api_key' => 'cf-abc123xyz',
    ];

    $this->post(route('integrations.store'), $data)
        ->assertRedirect(route('integrations.index'));

    $this->assertDatabaseHas('integrations', [
        'provider' => 'curseforge',
        'label' => 'My CurseForge Key',
    ]);
});

test('store validates required fields', function () {
    $this->post(route('integrations.store'), [])
        ->assertSessionHasErrors(['provider', 'api_key']);
});

test('edit renders the form with integration data', function () {
    $integration = Integration::factory()->create(['provider' => 'openai']);

    $this->get(route('integrations.edit', $integration))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('integrations/Edit')
            ->has('integration')
            ->has('providers')
        );
});

test('update modifies an integration', function () {
    $integration = Integration::factory()->create([
        'provider' => 'openai',
        'label' => 'Old label',
    ]);

    $this->put(route('integrations.update', $integration), [
        'provider' => 'openai',
        'label' => 'New label',
        'api_key' => '',
        'is_active' => false,
    ])->assertRedirect(route('integrations.index'));

    $integration->refresh();
    expect($integration->label)->toBe('New label');
    expect($integration->is_active)->toBeFalse();
});

test('update preserves api_key when left blank', function () {
    $integration = Integration::factory()->create([
        'provider' => 'claude',
        'api_key' => 'original-secret-key',
    ]);

    $this->put(route('integrations.update', $integration), [
        'provider' => $integration->provider,
        'label' => $integration->label,
        'api_key' => '',
        'is_active' => true,
    ])->assertRedirect(route('integrations.index'));

    expect($integration->fresh()->api_key)->toBe('original-secret-key');
});

test('destroy deletes an integration', function () {
    $integration = Integration::factory()->create(['provider' => 'openai']);

    $this->delete(route('integrations.destroy', $integration))
        ->assertRedirect(route('integrations.index'));

    $this->assertDatabaseMissing('integrations', ['id' => $integration->id]);
});

test('integrations require authentication', function () {
    auth()->logout();

    $this->get(route('integrations.index'))
        ->assertRedirect(route('login'));
});

test('api_key is never exposed in index response', function () {
    Integration::factory()->create([
        'provider' => 'curseforge',
        'api_key' => 'super-secret-key',
    ]);

    $this->get(route('integrations.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('integrations', 1, fn ($integration) => $integration
                ->has('id')
                ->has('provider')
                ->has('label')
                ->has('is_active')
                ->has('has_key')
                ->has('created_at')
                ->missing('api_key')
            )
        );
});
