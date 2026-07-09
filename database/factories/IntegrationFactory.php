<?php

namespace Database\Factories;

use App\Models\Integration;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Integration>
 */
class IntegrationFactory extends Factory
{
    protected $model = Integration::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'provider' => fake()->unique()->randomElement(['curseforge', 'openai', 'claude', 'other']),
            'label' => fake()->optional()->words(2, true),
            'api_key' => fake()->sha256(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
