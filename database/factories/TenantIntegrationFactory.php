<?php

namespace Database\Factories;

use App\Models\Enums\IntegrationProvider;
use App\Models\Enums\IntegrationStatus;
use App\Models\Enums\WebhookEvent;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TenantIntegration>
 */
class TenantIntegrationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->words(2, true).' Integration',
            'provider' => fake()->randomElement(IntegrationProvider::cases()),
            'url' => fake()->url(),
            'secret' => Str::random(64),
            'events' => [WebhookEvent::MessageReceived->value],
            'status' => IntegrationStatus::Active,
            'is_primary' => false,
            'failure_count' => 0,
            'metadata' => [],
        ];
    }

    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => IntegrationStatus::Suspended,
        ]);
    }

    public function withAllEvents(): static
    {
        return $this->state(fn (array $attributes) => [
            'events' => array_map(fn ($case) => $case->value, WebhookEvent::cases()),
        ]);
    }
}
