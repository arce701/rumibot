<?php

use App\Jobs\ProcessDocument;
use App\Models\Channel;
use App\Models\Enums\DocumentStatus;
use App\Models\KnowledgeChunk;
use App\Models\KnowledgeDocument;
use App\Models\Tenant;
use App\Services\Document\DocumentProcessor;
use App\Services\Document\TextExtractor;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Embeddings;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
});

test('process document job is dispatched on low queue', function () {
    Queue::fake();

    $document = KnowledgeDocument::factory()->create(['tenant_id' => $this->tenant->id]);

    ProcessDocument::dispatch($document);

    Queue::assertPushedOn('low', ProcessDocument::class, function ($job) use ($document) {
        return $job->document->id === $document->id;
    });
});

test('document processing pipeline extracts chunks and generates embeddings', function () {
    Embeddings::fake();
    Storage::fake('s3');

    $filePath = 'tenants/'.$this->tenant->id.'/documents/test.txt';
    Storage::disk('s3')->put($filePath, "First paragraph with enough content to form a chunk.\n\nSecond paragraph with more content for testing.\n\nThird paragraph adds additional text.");

    $document = KnowledgeDocument::factory()->create([
        'tenant_id' => $this->tenant->id,
        'file_path' => $filePath,
        'mime_type' => 'text/plain',
    ]);

    $processor = app(DocumentProcessor::class);
    $processor->process($document);

    $document->refresh();

    expect($document->status)->toBe(DocumentStatus::Ready);
    expect($document->total_chunks)->toBeGreaterThan(0);
    expect($document->error_message)->toBeNull();

    $chunks = KnowledgeChunk::withoutGlobalScopes()
        ->where('document_id', $document->id)
        ->get();

    expect($chunks)->toHaveCount($document->total_chunks);

    Embeddings::assertGenerated(fn ($prompt) => count($prompt->inputs) === $document->total_chunks);
});

test('document processing fails when no text content extracted', function () {
    Embeddings::fake();
    Storage::fake('s3');

    $filePath = 'tenants/'.$this->tenant->id.'/documents/empty.txt';
    Storage::disk('s3')->put($filePath, '   ');

    $document = KnowledgeDocument::factory()->create([
        'tenant_id' => $this->tenant->id,
        'file_path' => $filePath,
        'mime_type' => 'text/plain',
    ]);

    $processor = app(DocumentProcessor::class);

    expect(fn () => $processor->process($document))->toThrow(RuntimeException::class);

    $document->refresh();
    expect($document->status)->toBe(DocumentStatus::Failed);
    expect($document->error_message)->toContain('No text content');
});

test('document processing fails when exception occurs and sets error message', function () {
    Embeddings::fake();

    $extractor = Mockery::mock(TextExtractor::class);
    $extractor->shouldReceive('extract')
        ->andThrow(new RuntimeException('File not found: missing.pdf'));

    $this->app->instance(TextExtractor::class, $extractor);

    $document = KnowledgeDocument::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);

    $processor = app(DocumentProcessor::class);

    expect(fn () => $processor->process($document))->toThrow(RuntimeException::class);

    $document->refresh();
    expect($document->status)->toBe(DocumentStatus::Failed);
    expect($document->error_message)->toContain('File not found');
});

test('chunks have correct tenant_id and document_id', function () {
    Embeddings::fake();
    Storage::fake('s3');

    $filePath = 'tenants/'.$this->tenant->id.'/documents/test.txt';
    Storage::disk('s3')->put($filePath, "Some meaningful text content.\n\nAnother paragraph here.");

    $document = KnowledgeDocument::factory()->create([
        'tenant_id' => $this->tenant->id,
        'file_path' => $filePath,
        'mime_type' => 'text/plain',
    ]);

    $processor = app(DocumentProcessor::class);
    $processor->process($document);

    $chunks = KnowledgeChunk::withoutGlobalScopes()
        ->where('document_id', $document->id)
        ->get();

    expect($chunks)->each(function ($chunk) use ($document) {
        $chunk->tenant_id->toBe($document->tenant_id);
        $chunk->document_id->toBe($document->id);
    });
});

test('reprocessing deletes old chunks and creates new ones', function () {
    Embeddings::fake();
    Storage::fake('s3');

    $filePath = 'tenants/'.$this->tenant->id.'/documents/test.txt';
    Storage::disk('s3')->put($filePath, "First version content paragraph.\n\nSecond paragraph.");

    $document = KnowledgeDocument::factory()->ready()->create([
        'tenant_id' => $this->tenant->id,
        'file_path' => $filePath,
        'mime_type' => 'text/plain',
    ]);

    KnowledgeChunk::factory()->count(3)->create([
        'document_id' => $document->id,
        'tenant_id' => $this->tenant->id,
    ]);

    expect(KnowledgeChunk::withoutGlobalScopes()->where('document_id', $document->id)->count())->toBe(3);

    $document->update(['status' => DocumentStatus::Pending]);

    $processor = app(DocumentProcessor::class);
    $processor->process($document);

    $document->refresh();
    $newChunks = KnowledgeChunk::withoutGlobalScopes()
        ->where('document_id', $document->id)
        ->get();

    expect($document->status)->toBe(DocumentStatus::Ready);
    expect($newChunks)->toHaveCount($document->total_chunks);
});
