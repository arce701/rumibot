<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Enums\LeadStatus;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lead>
 */
class LeadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'conversation_id' => Conversation::factory(),
            'full_name' => fake()->name(),
            'country' => fake()->randomElement(['Perú', 'Colombia', 'México', 'Chile', 'Argentina']),
            'phone' => fake()->numerify('+51#########'),
            'email' => fake()->safeEmail(),
            'company_name' => fake()->company(),
            'interests' => [],
            'qualification_score' => null,
            'status' => LeadStatus::New,
            'notes' => null,
        ];
    }

    public function contacted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeadStatus::Contacted,
        ]);
    }

    public function converted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeadStatus::Converted,
            'converted_at' => now(),
        ]);
    }
}
