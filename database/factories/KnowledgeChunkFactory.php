<?php

namespace Database\Factories;

use App\Models\KnowledgeDocument;
use App\Models\Tenant;
use Laravel\Ai\Embeddings;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\KnowledgeChunk>
 */
class KnowledgeChunkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'document_id' => KnowledgeDocument::factory(),
            'tenant_id' => Tenant::factory(),
            'chunk_index' => fake()->numberBetween(0, 100),
            'content' => fake()->paragraphs(3, true),
            'token_count' => fake()->numberBetween(100, 600),
            'embedding' => Embeddings::fakeEmbedding(1536),
            'metadata' => [],
            'created_at' => now(),
        ];
    }
}
