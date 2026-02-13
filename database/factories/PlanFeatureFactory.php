<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PlanFeature>
 */
class PlanFeatureFactory extends Factory
{
    public function definition(): array
    {
        return [
            'plan_id' => Plan::factory(),
            'feature_slug' => fake()->unique()->slug(2),
            'value' => (string) fake()->numberBetween(1, 100),
        ];
    }

    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'value' => 'unlimited',
        ]);
    }
}
