<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'system_prompt' => fake()->paragraph(),
            'timezone' => 'America/Lima',
            'locale' => 'es',
            'is_active' => true,
            'is_platform_owner' => false,
            'settings' => [],
        ];
    }

    public function platformOwner(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_platform_owner' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
