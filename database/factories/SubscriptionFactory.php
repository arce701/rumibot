<?php

namespace Database\Factories;

use App\Models\Enums\PaymentProviderType;
use App\Models\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    public function definition(): array
    {
        $plan = Plan::factory()->create();
        $planPrice = PlanPrice::factory()->create(['plan_id' => $plan->id]);

        return [
            'tenant_id' => Tenant::factory(),
            'plan_id' => $plan->id,
            'plan_price_id' => $planPrice->id,
            'status' => SubscriptionStatus::Active,
            'payment_provider' => PaymentProviderType::Manual,
            'external_subscription_id' => null,
            'external_customer_id' => null,
            'trial_starts_at' => null,
            'trial_ends_at' => null,
            'current_period_starts_at' => now(),
            'current_period_ends_at' => now()->addMonths(3),
            'canceled_at' => null,
            'grace_period_ends_at' => null,
        ];
    }

    public function trialing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::Trialing,
            'trial_starts_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);
    }

    public function canceled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::Canceled,
            'canceled_at' => now(),
            'grace_period_ends_at' => now()->addDays(3),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::Expired,
            'current_period_ends_at' => now()->subDay(),
        ]);
    }
}
