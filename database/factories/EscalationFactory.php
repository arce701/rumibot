<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Escalation>
 */
class EscalationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'conversation_id' => Conversation::factory(),
            'reason' => fake()->randomElement(['complex_question', 'customer_request', 'negative_sentiment', 'urgent']),
            'note' => fake()->sentence(),
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'resolved_at' => now(),
            'resolution_note' => fake()->sentence(),
        ]);
    }
}
