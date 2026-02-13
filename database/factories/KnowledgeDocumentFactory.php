<?php

namespace Database\Factories;

use App\Models\Enums\DocumentStatus;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\KnowledgeDocument>
 */
class KnowledgeDocumentFactory extends Factory
{
    public function definition(): array
    {
        $fileName = fake()->words(3, true).'.pdf';

        return [
            'tenant_id' => Tenant::factory(),
            'title' => fake()->sentence(4),
            'file_name' => $fileName,
            'file_path' => 'tenants/'.fake()->uuid().'/documents/'.$fileName,
            'file_size' => fake()->numberBetween(1024, 10485760),
            'mime_type' => 'application/pdf',
            'status' => DocumentStatus::Pending,
            'error_message' => null,
            'total_chunks' => 0,
            'channel_scope' => [],
            'metadata' => [],
        ];
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DocumentStatus::Processing,
        ]);
    }

    public function ready(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DocumentStatus::Ready,
            'total_chunks' => fake()->numberBetween(5, 50),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DocumentStatus::Failed,
            'error_message' => 'Processing failed: '.fake()->sentence(),
        ]);
    }

    /**
     * @param  string[]  $channelIds
     */
    public function scopedToChannels(array $channelIds): static
    {
        return $this->state(fn (array $attributes) => [
            'channel_scope' => $channelIds,
        ]);
    }
}
