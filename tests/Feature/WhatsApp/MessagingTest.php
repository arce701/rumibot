<?php

use App\Ai\Agents\TenantChatAgent;
use App\Jobs\ProcessIncomingMessage;
use App\Jobs\SendWhatsAppMessage;
use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Enums\ConversationStatus;
use App\Models\Message;
use App\Models\Tenant;
use App\Services\WhatsApp\InboundMessage;
use App\Services\WhatsApp\MessageResponse;
use App\Services\WhatsApp\YCloudProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->channel = Channel::factory()->create([
        'tenant_id' => $this->tenant->id,
        'provider_api_key' => 'test-api-key',
        'provider_phone_number_id' => '+51912345678',
    ]);
});

// --- ProcessIncomingMessage Job ---

test('process incoming message creates conversation and message', function () {
    TenantChatAgent::fake(['Respuesta del bot']);
    Queue::fake([SendWhatsAppMessage::class]);

    $inbound = new InboundMessage(
        messageId: 'wamid.test123',
        from: '+51999888777',
        to: '+51912345678',
        type: 'text',
        content: 'Hola, necesito ayuda',
        contactName: 'María García',
        timestamp: '2026-02-13T10:30:00Z',
    );

    ProcessIncomingMessage::dispatchSync($this->channel, $inbound);

    $conversation = Conversation::withoutGlobalScopes()
        ->where('channel_id', $this->channel->id)
        ->where('contact_phone', '+51999888777')
        ->first();

    expect($conversation)->not->toBeNull();
    expect($conversation->tenant_id)->toBe($this->tenant->id);
    expect($conversation->contact_name)->toBe('María García');
    expect($conversation->status)->toBe(ConversationStatus::Active);
    expect($conversation->messages_count)->toBe(2); // user + assistant
    expect($conversation->last_message_at)->not->toBeNull();

    $message = Message::withoutGlobalScopes()
        ->where('conversation_id', $conversation->id)
        ->where('role', 'user')
        ->first();

    expect($message)->not->toBeNull();
    expect($message->content)->toBe('Hola, necesito ayuda');
    expect($message->tenant_id)->toBe($this->tenant->id);
    expect($message->metadata['whatsapp_message_id'])->toBe('wamid.test123');
});

test('process incoming message reuses existing active conversation', function () {
    TenantChatAgent::fake(['Respuesta']);
    Queue::fake([SendWhatsAppMessage::class]);

    $conversation = Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $this->channel->id,
        'contact_phone' => '+51999888777',
        'status' => ConversationStatus::Active,
        'messages_count' => 3,
    ]);

    $inbound = new InboundMessage(
        messageId: 'wamid.second',
        from: '+51999888777',
        to: '+51912345678',
        type: 'text',
        content: 'Segundo mensaje',
    );

    ProcessIncomingMessage::dispatchSync($this->channel, $inbound);

    // Should NOT create a new conversation
    expect(Conversation::withoutGlobalScopes()->where('channel_id', $this->channel->id)->count())->toBe(1);

    // Should increment messages_count (+2: user + assistant)
    $conversation->refresh();
    expect($conversation->messages_count)->toBe(5);
});

test('process incoming message creates new conversation if existing is closed', function () {
    TenantChatAgent::fake(['Bienvenido de vuelta']);
    Queue::fake([SendWhatsAppMessage::class]);

    Conversation::factory()->closed()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $this->channel->id,
        'contact_phone' => '+51999888777',
    ]);

    $inbound = new InboundMessage(
        messageId: 'wamid.new',
        from: '+51999888777',
        to: '+51912345678',
        type: 'text',
        content: 'Nuevo mensaje',
    );

    ProcessIncomingMessage::dispatchSync($this->channel, $inbound);

    // Should create a second conversation
    expect(Conversation::withoutGlobalScopes()
        ->where('channel_id', $this->channel->id)
        ->where('contact_phone', '+51999888777')
        ->count())->toBe(2);
});

test('process incoming message stores media metadata', function () {
    TenantChatAgent::fake(['Foto recibida']);
    Queue::fake([SendWhatsAppMessage::class]);

    $inbound = new InboundMessage(
        messageId: 'wamid.media123',
        from: '+51999888777',
        to: '+51912345678',
        type: 'image',
        content: 'Mira esto',
        media: ['link' => 'https://example.com/photo.jpg', 'caption' => 'Mira esto'],
    );

    ProcessIncomingMessage::dispatchSync($this->channel, $inbound);

    $message = Message::withoutGlobalScopes()->where('metadata->whatsapp_message_id', 'wamid.media123')->first();

    expect($message)->not->toBeNull();
    expect($message->metadata['message_type'])->toBe('image');
    expect($message->metadata['media'])->not->toBeNull();
});

// --- SendWhatsAppMessage Job ---

test('send whatsapp message stores assistant message on success', function () {
    Http::fake([
        'api.ycloud.com/v2/whatsapp/messages/sendDirectly' => Http::response([
            'id' => 'ycloud-msg-001',
            'status' => 'accepted',
        ], 200),
    ]);

    $conversation = Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $this->channel->id,
        'contact_phone' => '+51999888777',
        'messages_count' => 1,
    ]);

    SendWhatsAppMessage::dispatchSync($conversation, 'Hola, gracias por contactarnos');

    $message = Message::withoutGlobalScopes()
        ->where('conversation_id', $conversation->id)
        ->where('role', 'assistant')
        ->first();

    expect($message)->not->toBeNull();
    expect($message->content)->toBe('Hola, gracias por contactarnos');
    expect($message->metadata['whatsapp_message_id'])->toBe('ycloud-msg-001');
    expect($message->metadata['provider'])->toBe('ycloud');

    $conversation->refresh();
    expect($conversation->messages_count)->toBe(2);
    expect($conversation->last_message_at)->not->toBeNull();
});

test('send whatsapp message fails gracefully on api error', function () {
    Http::fake([
        'api.ycloud.com/v2/whatsapp/messages/sendDirectly' => Http::response([
            'message' => 'Invalid phone number',
        ], 400),
    ]);

    $conversation = Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $this->channel->id,
        'contact_phone' => '+51999888777',
    ]);

    Log::shouldReceive('warning')->once(); // YCloudProvider logs warning on failure
    Log::shouldReceive('error')
        ->once()
        ->withArgs(function ($message, $context) {
            return str_contains($message, 'Failed to send WhatsApp message')
                && isset($context['conversation_id']);
        });

    try {
        SendWhatsAppMessage::dispatchSync($conversation, 'Test message');
    } catch (\RuntimeException $e) {
        expect($e->getMessage())->toContain('WhatsApp send failed');
    }

    // No assistant message should be created
    expect(Message::withoutGlobalScopes()
        ->where('conversation_id', $conversation->id)
        ->where('role', 'assistant')
        ->count())->toBe(0);
});

// --- YCloudProvider ---

test('ycloud provider sends text message successfully', function () {
    Http::fake([
        'api.ycloud.com/v2/whatsapp/messages/sendDirectly' => Http::response([
            'id' => 'msg-success-001',
        ], 200),
    ]);

    $provider = new YCloudProvider('test-api-key');
    $response = $provider->sendText('+51912345678', '+51999888777', 'Hello World');

    expect($response->success)->toBeTrue();
    expect($response->messageId)->toBe('msg-success-001');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.ycloud.com/v2/whatsapp/messages/sendDirectly'
            && $request->header('X-API-Key')[0] === 'test-api-key'
            && $request['from'] === '+51912345678'
            && $request['to'] === '+51999888777'
            && $request['type'] === 'text'
            && $request['text']['body'] === 'Hello World';
    });
});

test('ycloud provider returns failure on api error', function () {
    Http::fake([
        'api.ycloud.com/v2/whatsapp/messages/sendDirectly' => Http::response([
            'message' => 'Rate limit exceeded',
        ], 429),
    ]);

    $provider = new YCloudProvider('test-api-key');
    $response = $provider->sendText('+51912345678', '+51999888777', 'Hello');

    expect($response->success)->toBeFalse();
    expect($response->error)->toBe('Rate limit exceeded');
});

test('ycloud provider sends interactive button message', function () {
    Http::fake([
        'api.ycloud.com/v2/whatsapp/messages/sendDirectly' => Http::response([
            'id' => 'msg-interactive-001',
        ], 200),
    ]);

    $provider = new YCloudProvider('test-api-key');
    $response = $provider->sendInteractive(
        '+51912345678',
        '+51999888777',
        '¿Cómo puedo ayudarte?',
        [
            ['id' => 'btn_ventas', 'title' => 'Ventas'],
            ['id' => 'btn_soporte', 'title' => 'Soporte'],
        ]
    );

    expect($response->success)->toBeTrue();

    Http::assertSent(function ($request) {
        return $request['type'] === 'interactive'
            && $request['interactive']['type'] === 'button'
            && $request['interactive']['body']['text'] === '¿Cómo puedo ayudarte?'
            && count($request['interactive']['action']['buttons']) === 2;
    });
});

test('ycloud provider parses text inbound message', function () {
    $provider = new YCloudProvider('test-api-key');

    $payload = [
        'type' => 'whatsapp.inbound_message.received',
        'whatsappInboundMessage' => [
            'id' => 'wamid.parse-test',
            'from' => '+51999000111',
            'to' => '+51912345678',
            'type' => 'text',
            'text' => ['body' => 'Test message content'],
            'customerProfile' => ['name' => 'Test User'],
            'sendTime' => '2026-02-13T12:00:00Z',
        ],
    ];

    $message = $provider->parseInboundMessage($payload);

    expect($message)->toBeInstanceOf(InboundMessage::class);
    expect($message->messageId)->toBe('wamid.parse-test');
    expect($message->from)->toBe('+51999000111');
    expect($message->to)->toBe('+51912345678');
    expect($message->type)->toBe('text');
    expect($message->content)->toBe('Test message content');
    expect($message->contactName)->toBe('Test User');
    expect($message->isText())->toBeTrue();
    expect($message->isMedia())->toBeFalse();
});

test('ycloud provider parses interactive button reply', function () {
    $provider = new YCloudProvider('test-api-key');

    $payload = [
        'type' => 'whatsapp.inbound_message.received',
        'whatsappInboundMessage' => [
            'id' => 'wamid.interactive',
            'from' => '+51999000111',
            'to' => '+51912345678',
            'type' => 'interactive',
            'interactive' => [
                'button_reply' => [
                    'id' => 'btn_ventas',
                    'title' => 'Ventas',
                ],
            ],
        ],
    ];

    $message = $provider->parseInboundMessage($payload);

    expect($message->type)->toBe('interactive');
    expect($message->content)->toBe('Ventas');
    expect($message->isInteractive())->toBeTrue();
});

// --- InboundMessage DTO ---

test('inbound message dto has correct helper methods', function () {
    $textMsg = new InboundMessage(
        messageId: 'test', from: '+51999', to: '+51912',
        type: 'text', content: 'hello',
    );
    expect($textMsg->isText())->toBeTrue();
    expect($textMsg->isMedia())->toBeFalse();
    expect($textMsg->isInteractive())->toBeFalse();

    $imageMsg = new InboundMessage(
        messageId: 'test', from: '+51999', to: '+51912',
        type: 'image', content: '[image]',
    );
    expect($imageMsg->isText())->toBeFalse();
    expect($imageMsg->isMedia())->toBeTrue();

    $buttonMsg = new InboundMessage(
        messageId: 'test', from: '+51999', to: '+51912',
        type: 'button', content: 'clicked',
    );
    expect($buttonMsg->isInteractive())->toBeTrue();
});

// --- MessageResponse DTO ---

test('message response success factory creates correct instance', function () {
    $response = MessageResponse::success('msg-123', ['id' => 'msg-123']);

    expect($response->success)->toBeTrue();
    expect($response->messageId)->toBe('msg-123');
    expect($response->error)->toBeNull();
    expect($response->rawResponse)->toBe(['id' => 'msg-123']);
});

test('message response failure factory creates correct instance', function () {
    $response = MessageResponse::failure('Something went wrong', ['error' => true]);

    expect($response->success)->toBeFalse();
    expect($response->messageId)->toBeNull();
    expect($response->error)->toBe('Something went wrong');
    expect($response->rawResponse)->toBe(['error' => true]);
});
