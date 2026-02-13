<?php

use App\Ai\Agents\TenantChatAgent;
use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Enums\DocumentStatus;
use App\Models\KnowledgeChunk;
use App\Models\KnowledgeDocument;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Laravel\Ai\Tools\SimilaritySearch;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->channel = Channel::factory()->sales()->create(['tenant_id' => $this->tenant->id]);
    $this->conversation = Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $this->channel->id,
    ]);
});

test('agent tools include SimilaritySearch', function () {
    $agent = new TenantChatAgent($this->tenant, $this->channel, $this->conversation);
    $tools = $agent->tools();

    $toolClasses = array_map(fn ($tool) => $tool::class, is_array($tools) ? $tools : iterator_to_array($tools));

    expect($toolClasses)->toContain(SimilaritySearch::class);
});

test('SimilaritySearch is the first tool in the agent', function () {
    $agent = new TenantChatAgent($this->tenant, $this->channel, $this->conversation);
    $tools = is_array($agent->tools()) ? $agent->tools() : iterator_to_array($agent->tools());

    expect($tools[0])->toBeInstanceOf(SimilaritySearch::class);
});

test('knowledge document factory creates valid records', function () {
    $document = KnowledgeDocument::factory()->create(['tenant_id' => $this->tenant->id]);

    expect($document)->not->toBeNull();
    expect($document->tenant_id)->toBe($this->tenant->id);
    expect($document->status)->toBe(DocumentStatus::Pending);
});

test('knowledge document ready factory state works', function () {
    $document = KnowledgeDocument::factory()->ready()->create(['tenant_id' => $this->tenant->id]);

    expect($document->status)->toBe(DocumentStatus::Ready);
    expect($document->total_chunks)->toBeGreaterThan(0);
});

test('knowledge document respects channel_scope', function () {
    $document = KnowledgeDocument::factory()
        ->scopedToChannels([$this->channel->id])
        ->create(['tenant_id' => $this->tenant->id]);

    expect($document->channel_scope)->toBe([$this->channel->id]);
});

test('soft deleting document preserves record', function () {
    $document = KnowledgeDocument::factory()->create(['tenant_id' => $this->tenant->id]);

    $document->delete();

    expect(KnowledgeDocument::withoutGlobalScope(TenantScope::class)->withTrashed()->find($document->id))->not->toBeNull();
    expect(KnowledgeDocument::withoutGlobalScope(TenantScope::class)->find($document->id))->toBeNull();
});

test('deleting document cascades to chunks', function () {
    $document = KnowledgeDocument::factory()->ready()->create(['tenant_id' => $this->tenant->id]);

    KnowledgeChunk::factory()->count(3)->create([
        'document_id' => $document->id,
        'tenant_id' => $this->tenant->id,
    ]);

    expect(KnowledgeChunk::withoutGlobalScopes()->where('document_id', $document->id)->count())->toBe(3);

    $document->forceDelete();

    expect(KnowledgeChunk::withoutGlobalScopes()->where('document_id', $document->id)->count())->toBe(0);
});

test('document has chunks relationship', function () {
    $document = KnowledgeDocument::factory()->create(['tenant_id' => $this->tenant->id]);

    KnowledgeChunk::factory()->count(2)->create([
        'document_id' => $document->id,
        'tenant_id' => $this->tenant->id,
    ]);

    expect($document->chunks()->withoutGlobalScopes()->count())->toBe(2);
});

test('chunk belongs to document', function () {
    $document = KnowledgeDocument::factory()->create(['tenant_id' => $this->tenant->id]);

    $chunk = KnowledgeChunk::factory()->create([
        'document_id' => $document->id,
        'tenant_id' => $this->tenant->id,
    ]);

    expect($chunk->document()->withoutGlobalScopes()->first()->id)->toBe($document->id);
});

test('isProcessable returns true for pending and failed documents', function () {
    $pending = KnowledgeDocument::factory()->create([
        'tenant_id' => $this->tenant->id,
        'status' => DocumentStatus::Pending,
    ]);

    $failed = KnowledgeDocument::factory()->failed()->create([
        'tenant_id' => $this->tenant->id,
    ]);

    $processing = KnowledgeDocument::factory()->processing()->create([
        'tenant_id' => $this->tenant->id,
    ]);

    $ready = KnowledgeDocument::factory()->ready()->create([
        'tenant_id' => $this->tenant->id,
    ]);

    expect($pending->isProcessable())->toBeTrue();
    expect($failed->isProcessable())->toBeTrue();
    expect($processing->isProcessable())->toBeFalse();
    expect($ready->isProcessable())->toBeFalse();
});

test('knowledge document logs activity', function () {
    $document = KnowledgeDocument::factory()->create(['tenant_id' => $this->tenant->id]);

    $document->update(['title' => 'Updated Title']);

    $activities = $document->activities()->get();

    expect($activities)->toHaveCount(2);
    expect($activities->pluck('description')->toArray())->toContain('created', 'updated');
});
