<?php

namespace App\Services\Document;

use App\Models\Enums\DocumentStatus;
use App\Models\KnowledgeChunk;
use App\Models\KnowledgeDocument;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Embeddings;
use RuntimeException;

class DocumentProcessor
{
    public function __construct(
        private TextExtractor $extractor,
        private TextChunker $chunker,
    ) {}

    public function process(KnowledgeDocument $document): void
    {
        $document->update(['status' => DocumentStatus::Processing]);

        try {
            $extracted = $this->extractor->extract($document->file_path, $document->mime_type);

            if (trim($extracted['text']) === '') {
                throw new RuntimeException('No text content extracted from document.');
            }

            $chunks = $this->chunker->chunk($extracted['text'], $extracted['metadata']);

            if (empty($chunks)) {
                throw new RuntimeException('No chunks generated from document text.');
            }

            $contents = array_map(fn (array $chunk) => $chunk['content'], $chunks);

            $embeddingsResponse = Embeddings::for($contents)->dimensions(1536)->generate();

            $document->chunks()->withoutGlobalScopes()->delete();

            DB::transaction(function () use ($document, $chunks, $embeddingsResponse): void {
                $now = now();

                foreach ($chunks as $index => $chunk) {
                    KnowledgeChunk::withoutGlobalScopes()->create([
                        'document_id' => $document->id,
                        'tenant_id' => $document->tenant_id,
                        'chunk_index' => $index,
                        'content' => $chunk['content'],
                        'token_count' => $chunk['token_count'],
                        'embedding' => $embeddingsResponse->embeddings[$index],
                        'metadata' => $chunk['metadata'],
                        'created_at' => $now,
                    ]);
                }

                $document->update([
                    'status' => DocumentStatus::Ready,
                    'total_chunks' => count($chunks),
                    'error_message' => null,
                ]);
            });
        } catch (\Throwable $e) {
            $document->update([
                'status' => DocumentStatus::Failed,
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
