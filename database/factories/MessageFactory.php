<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'tenant_id' => Tenant::factory(),
            'role' => 'user',
            'content' => fake()->paragraph(),
            'tokens_input' => null,
            'tokens_output' => null,
            'model_used' => null,
            'response_time_ms' => null,
            'metadata' => [],
        ];
    }

    public function fromUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'user',
        ]);
    }

    public function fromAssistant(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'assistant',
            'model_used' => 'gpt-4o-mini',
            'tokens_input' => fake()->numberBetween(100, 500),
            'tokens_output' => fake()->numberBetween(50, 300),
            'response_time_ms' => fake()->numberBetween(200, 2000),
        ]);
    }

    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'system',
        ]);
    }
}
