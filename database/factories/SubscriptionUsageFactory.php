<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SubscriptionUsage>
 */
class SubscriptionUsageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'subscription_id' => Subscription::factory(),
            'feature_slug' => 'max_messages',
            'used' => fake()->numberBetween(0, 100),
            'period_starts_at' => now()->startOfMonth(),
            'period_ends_at' => now()->endOfMonth(),
        ];
    }
}
