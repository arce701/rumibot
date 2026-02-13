<?php

namespace Database\Factories;

use App\Models\Enums\BillingInterval;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PlanPrice>
 */
class PlanPriceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'plan_id' => Plan::factory(),
            'billing_interval' => BillingInterval::Quarterly,
            'currency' => 'PEN',
            'price_amount' => fake()->randomElement([15000, 27000, 45000]),
        ];
    }
}
