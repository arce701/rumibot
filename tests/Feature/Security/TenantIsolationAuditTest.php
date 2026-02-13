<?php

use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Escalation;
use App\Models\KnowledgeChunk;
use App\Models\KnowledgeDocument;
use App\Models\Lead;
use App\Models\Message;
use App\Models\PaymentHistory;
use App\Models\Subscription;
use App\Models\SubscriptionUsage;
use App\Models\Tenant;
use App\Models\TenantIntegration;
use App\Services\Tenant\TenantContext;
use Illuminate\Database\Eloquent\Model;

beforeEach(function () {
    $this->tenantA = Tenant::factory()->create();
    $this->tenantB = Tenant::factory()->create();

    $channelA = Channel::factory()->create(['tenant_id' => $this->tenantA->id]);
    $channelB = Channel::factory()->create(['tenant_id' => $this->tenantB->id]);

    $conversationA = Conversation::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'channel_id' => $channelA->id,
    ]);

    $conversationB = Conversation::factory()->create([
        'tenant_id' => $this->tenantB->id,
        'channel_id' => $channelB->id,
    ]);

    Message::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'conversation_id' => $conversationA->id,
    ]);

    Message::factory()->create([
        'tenant_id' => $this->tenantB->id,
        'conversation_id' => $conversationB->id,
    ]);

    Lead::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'conversation_id' => $conversationA->id,
    ]);

    Lead::factory()->create([
        'tenant_id' => $this->tenantB->id,
        'conversation_id' => $conversationB->id,
    ]);

    TenantIntegration::factory()->create(['tenant_id' => $this->tenantA->id]);
    TenantIntegration::factory()->create(['tenant_id' => $this->tenantB->id]);

    $docA = KnowledgeDocument::factory()->create(['tenant_id' => $this->tenantA->id]);
    $docB = KnowledgeDocument::factory()->create(['tenant_id' => $this->tenantB->id]);

    KnowledgeChunk::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'document_id' => $docA->id,
    ]);
    KnowledgeChunk::factory()->create([
        'tenant_id' => $this->tenantB->id,
        'document_id' => $docB->id,
    ]);

    Escalation::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'conversation_id' => $conversationA->id,
    ]);
    Escalation::factory()->create([
        'tenant_id' => $this->tenantB->id,
        'conversation_id' => $conversationB->id,
    ]);

    Subscription::factory()->create(['tenant_id' => $this->tenantA->id]);
    Subscription::factory()->create(['tenant_id' => $this->tenantB->id]);

    SubscriptionUsage::factory()->create(['tenant_id' => $this->tenantA->id]);
    SubscriptionUsage::factory()->create(['tenant_id' => $this->tenantB->id]);

    PaymentHistory::factory()->create(['tenant_id' => $this->tenantA->id]);
    PaymentHistory::factory()->create(['tenant_id' => $this->tenantB->id]);
});

/**
 * @return array<int, class-string<Model>>
 */
function tenantScopedModels(): array
{
    return [
        Channel::class,
        Conversation::class,
        Message::class,
        Lead::class,
        TenantIntegration::class,
        KnowledgeDocument::class,
        KnowledgeChunk::class,
        Escalation::class,
        Subscription::class,
        SubscriptionUsage::class,
        PaymentHistory::class,
    ];
}

test('tenant scope filters records to current tenant', function (string $modelClass) {
    $context = app(TenantContext::class);
    $context->set($this->tenantA);

    $results = $modelClass::query()->get();

    $results->each(function ($record) {
        expect($record->tenant_id)->toBe($this->tenantA->id);
    });
})->with(fn () => tenantScopedModels());

test('without global scopes returns all tenant records', function (string $modelClass) {
    $count = $modelClass::withoutGlobalScopes()->count();

    expect($count)->toBeGreaterThanOrEqual(2);
})->with(fn () => tenantScopedModels());

test('tenant B cannot see tenant A records', function (string $modelClass) {
    $context = app(TenantContext::class);
    $context->set($this->tenantB);

    $results = $modelClass::query()->get();

    $results->each(function ($record) {
        expect($record->tenant_id)->toBe($this->tenantB->id);
    });
})->with(fn () => tenantScopedModels());
