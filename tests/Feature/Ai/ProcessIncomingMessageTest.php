<?php

use App\Ai\Agents\TenantChatAgent;
use App\Jobs\ProcessIncomingMessage;
use App\Jobs\SendWhatsAppMessage;
use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Enums\ConversationStatus;
use App\Models\LlmCredential;
use App\Models\Message;
use App\Models\Tenant;
use App\Services\WhatsApp\InboundMessage;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create([
        'system_prompt' => 'Eres el asistente de pruebas.',
        'default_ai_model' => 'gpt-4o-mini',
    ]);

    $credential = LlmCredential::factory()->create([
        'tenant_id' => $this->tenant->id,
        'provider' => 'openai',
        'api_key' => 'test-key',
    ]);
    $this->tenant->update(['default_llm_credential_id' => $credential->id]);

    $this->channel = Channel::factory()->sales()->create(['tenant_id' => $this->tenant->id]);
});

function makeInboundMessage(array $overrides = []): InboundMessage
{
    return new InboundMessage(
        messageId: $overrides['messageId'] ?? 'wamid_test_123',
        from: $overrides['from'] ?? '+51999888777',
        to: $overrides['to'] ?? '+51111222333',
        type: $overrides['type'] ?? 'text',
        content: $overrides['content'] ?? 'Hola, quiero información',
        contactName: $overrides['contactName'] ?? 'Juan Test',
    );
}

test('job creates conversation for new contact', function () {
    TenantChatAgent::fake(['Respuesta del bot']);
    Queue::fake([SendWhatsAppMessage::class]);

    $inbound = makeInboundMessage();

    (new ProcessIncomingMessage($this->channel, $inbound))->handle();

    $conversation = Conversation::withoutGlobalScopes()
        ->where('channel_id', $this->channel->id)
        ->where('contact_phone', '+51999888777')
        ->first();

    expect($conversation)->not->toBeNull();
    expect($conversation->contact_name)->toBe('Juan Test');
    expect($conversation->status)->toBe(ConversationStatus::Active);
});

test('job reuses existing active conversation', function () {
    TenantChatAgent::fake(['Respuesta 1', 'Respuesta 2']);
    Queue::fake([SendWhatsAppMessage::class]);

    $existing = Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $this->channel->id,
        'contact_phone' => '+51999888777',
        'status' => ConversationStatus::Active,
    ]);

    $inbound = makeInboundMessage();

    (new ProcessIncomingMessage($this->channel, $inbound))->handle();

    $conversations = Conversation::withoutGlobalScopes()
        ->where('channel_id', $this->channel->id)
        ->where('contact_phone', '+51999888777')
        ->get();

    expect($conversations)->toHaveCount(1);
    expect($conversations->first()->id)->toBe($existing->id);
});

test('job stores user message', function () {
    TenantChatAgent::fake(['Bot responde']);
    Queue::fake([SendWhatsAppMessage::class]);

    $inbound = makeInboundMessage(['content' => 'Quiero cotizar un servicio']);

    (new ProcessIncomingMessage($this->channel, $inbound))->handle();

    $userMessage = Message::withoutGlobalScopes()
        ->where('role', 'user')
        ->where('content', 'Quiero cotizar un servicio')
        ->first();

    expect($userMessage)->not->toBeNull();
    expect($userMessage->tenant_id)->toBe($this->tenant->id);
    expect($userMessage->metadata['whatsapp_message_id'])->toBe('wamid_test_123');
    expect($userMessage->metadata['message_type'])->toBe('text');
});

test('job prompts AI agent and stores assistant message', function () {
    TenantChatAgent::fake(['Hola Juan, te puedo ayudar con eso.']);
    Queue::fake([SendWhatsAppMessage::class]);

    $inbound = makeInboundMessage(['content' => 'Necesito ayuda']);

    (new ProcessIncomingMessage($this->channel, $inbound))->handle();

    TenantChatAgent::assertPrompted(fn ($prompt) => $prompt->prompt === 'Necesito ayuda');

    $assistantMessage = Message::withoutGlobalScopes()
        ->where('role', 'assistant')
        ->first();

    expect($assistantMessage)->not->toBeNull();
    expect($assistantMessage->content)->toBe('Hola Juan, te puedo ayudar con eso.');
});

test('job dispatches send whatsapp message with ai response and messageId', function () {
    TenantChatAgent::fake(['Gracias por tu consulta.']);
    Queue::fake([SendWhatsAppMessage::class]);

    $inbound = makeInboundMessage();

    (new ProcessIncomingMessage($this->channel, $inbound))->handle();

    Queue::assertPushed(SendWhatsAppMessage::class, function ($job) {
        return $job->text === 'Gracias por tu consulta.'
            && $job->messageId !== null;
    });
});

test('job increments messages count for user and assistant messages', function () {
    TenantChatAgent::fake(['Respuesta']);
    Queue::fake([SendWhatsAppMessage::class]);

    $conversation = Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $this->channel->id,
        'contact_phone' => '+51999888777',
        'messages_count' => 0,
    ]);

    $inbound = makeInboundMessage();

    (new ProcessIncomingMessage($this->channel, $inbound))->handle();

    $conversation->refresh();

    // +1 for user message, +1 for assistant message
    expect($conversation->messages_count)->toBe(2);
});

test('job updates last message at timestamp', function () {
    TenantChatAgent::fake(['Respuesta']);
    Queue::fake([SendWhatsAppMessage::class]);

    $inbound = makeInboundMessage();

    (new ProcessIncomingMessage($this->channel, $inbound))->handle();

    $conversation = Conversation::withoutGlobalScopes()
        ->where('contact_phone', '+51999888777')
        ->first();

    expect($conversation->last_message_at)->not->toBeNull();
});

test('job uses channel ai model override when set', function () {
    $channel = Channel::factory()->sales()->create([
        'tenant_id' => $this->tenant->id,
        'ai_model_override' => 'claude-sonnet-4-5-20250514',
    ]);

    TenantChatAgent::fake(['Respuesta con modelo override']);
    Queue::fake([SendWhatsAppMessage::class]);

    $inbound = makeInboundMessage();

    (new ProcessIncomingMessage($channel, $inbound))->handle();

    $assistantMessage = Message::withoutGlobalScopes()
        ->where('role', 'assistant')
        ->first();

    expect($assistantMessage->model_used)->toBe('claude-sonnet-4-5-20250514');
});

test('job stores model used in assistant message metadata', function () {
    TenantChatAgent::fake(['Respuesta']);
    Queue::fake([SendWhatsAppMessage::class]);

    $inbound = makeInboundMessage();

    (new ProcessIncomingMessage($this->channel, $inbound))->handle();

    $assistantMessage = Message::withoutGlobalScopes()
        ->where('role', 'assistant')
        ->first();

    expect($assistantMessage->model_used)->not->toBeNull();
});

test('job creates new conversation for closed contact', function () {
    TenantChatAgent::fake(['Bienvenido de vuelta']);
    Queue::fake([SendWhatsAppMessage::class]);

    Conversation::factory()->closed()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $this->channel->id,
        'contact_phone' => '+51999888777',
    ]);

    $inbound = makeInboundMessage();

    (new ProcessIncomingMessage($this->channel, $inbound))->handle();

    $conversations = Conversation::withoutGlobalScopes()
        ->where('channel_id', $this->channel->id)
        ->where('contact_phone', '+51999888777')
        ->get();

    // Should have 2: the closed one and a new active one
    expect($conversations)->toHaveCount(2);
    expect($conversations->where('status', ConversationStatus::Active))->toHaveCount(1);
});

test('job skips AI response when conversation AI is paused', function () {
    Queue::fake([SendWhatsAppMessage::class]);

    $conversation = Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $this->channel->id,
        'contact_phone' => '+51999888777',
        'ai_paused_until' => now()->addHours(12),
        'messages_count' => 0,
    ]);

    $inbound = makeInboundMessage();

    (new ProcessIncomingMessage($this->channel, $inbound))->handle();

    // Should store the user message
    $userMessage = Message::withoutGlobalScopes()
        ->where('conversation_id', $conversation->id)
        ->where('role', 'user')
        ->first();
    expect($userMessage)->not->toBeNull();

    // Should NOT create an assistant message
    $assistantMessage = Message::withoutGlobalScopes()
        ->where('conversation_id', $conversation->id)
        ->where('role', 'assistant')
        ->first();
    expect($assistantMessage)->toBeNull();

    // Should NOT dispatch SendWhatsAppMessage
    Queue::assertNotPushed(SendWhatsAppMessage::class);

    // messages_count should only include the user message
    $conversation->refresh();
    expect($conversation->messages_count)->toBe(1);
});
