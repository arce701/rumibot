<?php

use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Enums\ChannelType;
use App\Models\Enums\ConversationStatus;
use App\Models\Enums\WhatsAppProviderType;
use App\Models\Message;
use App\Models\Tenant;
use App\Services\Tenant\TenantContext;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->otherTenant = Tenant::factory()->create();
});

test('channel is created with uuid primary key', function () {
    $channel = Channel::factory()->create(['tenant_id' => $this->tenant->id]);

    expect($channel->id)
        ->toBeString()
        ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
});

test('channel belongs to tenant', function () {
    $channel = Channel::factory()->create(['tenant_id' => $this->tenant->id]);

    expect($channel->tenant->id)->toBe($this->tenant->id);
});

test('channel casts type to enum', function () {
    $channel = Channel::factory()->sales()->create(['tenant_id' => $this->tenant->id]);

    expect($channel->type)->toBe(ChannelType::Sales);
});

test('channel casts provider_type to enum', function () {
    $channel = Channel::factory()->create(['tenant_id' => $this->tenant->id]);

    expect($channel->provider_type)->toBe(WhatsAppProviderType::YCloud);
});

test('channel encrypts provider_api_key', function () {
    $channel = Channel::factory()->create([
        'tenant_id' => $this->tenant->id,
        'provider_api_key' => 'test-secret-key',
    ]);

    // The value in the database should NOT be the plain text
    $rawValue = DB::table('channels')->where('id', $channel->id)->value('provider_api_key');
    expect($rawValue)->not->toBe('test-secret-key');

    // But the model should decrypt it
    expect($channel->fresh()->provider_api_key)->toBe('test-secret-key');
});

test('channel factory sales state sets correct type', function () {
    $channel = Channel::factory()->sales()->create(['tenant_id' => $this->tenant->id]);

    expect($channel->type)->toBe(ChannelType::Sales);
    expect($channel->system_prompt_override)->not->toBeNull();
});

test('channel factory support state sets correct type', function () {
    $channel = Channel::factory()->support()->create(['tenant_id' => $this->tenant->id]);

    expect($channel->type)->toBe(ChannelType::Support);
    expect($channel->system_prompt_override)->not->toBeNull();
});

test('channel can be soft deleted', function () {
    $channel = Channel::factory()->create(['tenant_id' => $this->tenant->id]);
    $channel->delete();

    expect($channel->trashed())->toBeTrue();
    expect(Channel::withoutGlobalScopes()->withTrashed()->where('id', $channel->id)->exists())->toBeTrue();
    expect(Channel::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->where('id', $channel->id)->exists())->toBeFalse();
});

test('channel has conversations relationship', function () {
    $channel = Channel::factory()->create(['tenant_id' => $this->tenant->id]);

    Conversation::factory()->count(3)->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $channel->id,
    ]);

    expect($channel->conversations)->toHaveCount(3);
});

test('channel is scoped by tenant', function () {
    $channelA = Channel::factory()->create(['tenant_id' => $this->tenant->id]);
    $channelB = Channel::factory()->create(['tenant_id' => $this->otherTenant->id]);

    $context = app(TenantContext::class);
    $context->set($this->tenant);

    $channels = Channel::all();

    expect($channels)->toHaveCount(1);
    expect($channels->first()->id)->toBe($channelA->id);
});

test('conversation is created with uuid primary key', function () {
    $channel = Channel::factory()->create(['tenant_id' => $this->tenant->id]);
    $conversation = Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $channel->id,
    ]);

    expect($conversation->id)
        ->toBeString()
        ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
});

test('conversation belongs to channel', function () {
    $channel = Channel::factory()->create(['tenant_id' => $this->tenant->id]);
    $conversation = Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $channel->id,
    ]);

    expect($conversation->channel->id)->toBe($channel->id);
});

test('conversation casts status to enum', function () {
    $channel = Channel::factory()->create(['tenant_id' => $this->tenant->id]);
    $conversation = Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $channel->id,
        'status' => ConversationStatus::Escalated,
    ]);

    expect($conversation->status)->toBe(ConversationStatus::Escalated);
});

test('conversation has messages relationship', function () {
    $channel = Channel::factory()->create(['tenant_id' => $this->tenant->id]);
    $conversation = Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $channel->id,
    ]);

    Message::factory()->count(5)->create([
        'conversation_id' => $conversation->id,
        'tenant_id' => $this->tenant->id,
    ]);

    expect($conversation->messages)->toHaveCount(5);
});

test('conversation is scoped by tenant', function () {
    $channelA = Channel::factory()->create(['tenant_id' => $this->tenant->id]);
    $channelB = Channel::factory()->create(['tenant_id' => $this->otherTenant->id]);

    Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $channelA->id,
    ]);
    Conversation::factory()->create([
        'tenant_id' => $this->otherTenant->id,
        'channel_id' => $channelB->id,
    ]);

    $context = app(TenantContext::class);
    $context->set($this->tenant);

    expect(Conversation::count())->toBe(1);
});

test('message belongs to conversation', function () {
    $channel = Channel::factory()->create(['tenant_id' => $this->tenant->id]);
    $conversation = Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $channel->id,
    ]);
    $message = Message::factory()->create([
        'conversation_id' => $conversation->id,
        'tenant_id' => $this->tenant->id,
    ]);

    expect($message->conversation->id)->toBe($conversation->id);
});

test('message has no updated_at timestamp but has created_at from database default', function () {
    $channel = Channel::factory()->create(['tenant_id' => $this->tenant->id]);
    $conversation = Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $channel->id,
    ]);
    $message = Message::factory()->create([
        'conversation_id' => $conversation->id,
        'tenant_id' => $this->tenant->id,
    ]);

    expect($message->timestamps)->toBeFalse();
    // created_at is set by database default (useCurrent), need fresh() to get it
    expect($message->fresh()->created_at)->not->toBeNull();
});

test('message is scoped by tenant', function () {
    $channelA = Channel::factory()->create(['tenant_id' => $this->tenant->id]);
    $channelB = Channel::factory()->create(['tenant_id' => $this->otherTenant->id]);
    $convA = Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $channelA->id,
    ]);
    $convB = Conversation::factory()->create([
        'tenant_id' => $this->otherTenant->id,
        'channel_id' => $channelB->id,
    ]);

    Message::factory()->create([
        'conversation_id' => $convA->id,
        'tenant_id' => $this->tenant->id,
    ]);
    Message::factory()->create([
        'conversation_id' => $convB->id,
        'tenant_id' => $this->otherTenant->id,
    ]);

    $context = app(TenantContext::class);
    $context->set($this->tenant);

    expect(Message::count())->toBe(1);
});

test('message factory assistant state includes model info', function () {
    $channel = Channel::factory()->create(['tenant_id' => $this->tenant->id]);
    $conversation = Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $channel->id,
    ]);
    $message = Message::factory()->fromAssistant()->create([
        'conversation_id' => $conversation->id,
        'tenant_id' => $this->tenant->id,
    ]);

    expect($message->role)->toBe('assistant');
    expect($message->model_used)->not->toBeNull();
    expect($message->tokens_input)->toBeGreaterThan(0);
    expect($message->tokens_output)->toBeGreaterThan(0);
});
