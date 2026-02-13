<?php

namespace Database\Factories;

use App\Models\Channel;
use App\Models\Enums\ConversationStatus;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Conversation>
 */
class ConversationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'channel_id' => Channel::factory(),
            'contact_phone' => fake()->numerify('+51#########'),
            'contact_name' => fake()->name(),
            'status' => ConversationStatus::Active,
            'current_intent' => null,
            'metadata' => [],
            'messages_count' => 0,
            'total_input_tokens' => 0,
            'total_output_tokens' => 0,
            'last_message_at' => null,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ConversationStatus::Closed,
        ]);
    }

    public function escalated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ConversationStatus::Escalated,
        ]);
    }
}
