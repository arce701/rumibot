<?php

namespace Database\Factories;

use App\Models\Enums\PaymentProviderType;
use App\Models\Enums\PaymentStatus;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentHistory>
 */
class PaymentHistoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'subscription_id' => Subscription::factory(),
            'payment_provider' => PaymentProviderType::Manual,
            'external_payment_id' => null,
            'status' => PaymentStatus::Completed,
            'amount' => fake()->randomElement([15000, 27000, 45000]),
            'currency' => 'PEN',
            'description' => fake()->sentence(),
            'metadata' => [],
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Pending,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Failed,
        ]);
    }
}
