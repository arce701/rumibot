<?php

namespace Database\Factories;

use App\Models\Enums\AiProvider;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LlmCredential>
 */
class LlmCredentialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->words(2, true).' API Key',
            'provider' => fake()->randomElement(AiProvider::cases()),
            'api_key' => fake()->sha256(),
            'metadata' => [],
        ];
    }

    public function provider(AiProvider $provider): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => $provider,
        ]);
    }
}
