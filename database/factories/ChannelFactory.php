<?php

namespace Database\Factories;

use App\Models\Enums\ChannelType;
use App\Models\Enums\WhatsAppProviderType;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Channel>
 */
class ChannelFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->words(2, true).' Channel';

        return [
            'tenant_id' => Tenant::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'type' => ChannelType::Sales,
            'provider_type' => WhatsAppProviderType::YCloud,
            'provider_api_key' => fake()->sha256(),
            'provider_phone_number_id' => fake()->numerify('##########'),
            'provider_webhook_verify_token' => Str::random(32),
            'system_prompt_override' => null,
            'ai_model_override' => null,
            'is_active' => true,
            'settings' => [],
        ];
    }

    public function sales(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ChannelType::Sales,
            'system_prompt_override' => 'Eres un asesor comercial amigable y persuasivo. Tu objetivo es captar el interés del prospecto, responder sus preguntas sobre el producto, y capturar sus datos de contacto.',
        ]);
    }

    public function support(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ChannelType::Support,
            'system_prompt_override' => 'Eres un asistente de soporte técnico paciente y didáctico. Ayudas a los clientes a resolver problemas con el producto, enviando guías paso a paso e instrucciones claras.',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
