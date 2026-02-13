<?php

use App\Models\Channel;
use App\Models\Tenant;
use App\Services\WhatsApp\WhatsAppWebhookHandler;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->channel = Channel::factory()->create([
        'tenant_id' => $this->tenant->id,
        'provider_webhook_verify_token' => 'test-verify-token',
    ]);
});

// --- Webhook Verification (GET) ---

test('webhook verify returns challenge with correct token', function () {
    $response = $this->get(route('webhooks.whatsapp.verify', [
        'tenantUuid' => $this->tenant->id,
        'channelSlug' => $this->channel->slug,
        'hub_challenge' => 'test-challenge-123',
        'hub_verify_token' => 'test-verify-token',
    ]));

    $response->assertSuccessful();
    $response->assertSee('test-challenge-123');
});

test('webhook verify returns 403 with incorrect token', function () {
    $response = $this->get(route('webhooks.whatsapp.verify', [
        'tenantUuid' => $this->tenant->id,
        'channelSlug' => $this->channel->slug,
        'hub_challenge' => 'test-challenge-123',
        'hub_verify_token' => 'wrong-token',
    ]));

    $response->assertForbidden();
});

test('webhook verify returns 404 for non-existent tenant', function () {
    $response = $this->get(route('webhooks.whatsapp.verify', [
        'tenantUuid' => '00000000-0000-0000-0000-000000000000',
        'channelSlug' => $this->channel->slug,
        'hub_challenge' => 'test-challenge',
        'hub_verify_token' => 'test-verify-token',
    ]));

    $response->assertNotFound();
});

test('webhook verify returns 404 for non-existent channel', function () {
    $response = $this->get(route('webhooks.whatsapp.verify', [
        'tenantUuid' => $this->tenant->id,
        'channelSlug' => 'non-existent-slug',
        'hub_challenge' => 'test-challenge',
        'hub_verify_token' => 'test-verify-token',
    ]));

    $response->assertNotFound();
});

test('webhook verify returns 404 for inactive tenant', function () {
    $this->tenant->update(['is_active' => false]);

    $response = $this->get(route('webhooks.whatsapp.verify', [
        'tenantUuid' => $this->tenant->id,
        'channelSlug' => $this->channel->slug,
        'hub_challenge' => 'test-challenge',
        'hub_verify_token' => 'test-verify-token',
    ]));

    $response->assertNotFound();
});

test('webhook verify returns 404 for inactive channel', function () {
    $this->channel->update(['is_active' => false]);

    $response = $this->get(route('webhooks.whatsapp.verify', [
        'tenantUuid' => $this->tenant->id,
        'channelSlug' => $this->channel->slug,
        'hub_challenge' => 'test-challenge',
        'hub_verify_token' => 'test-verify-token',
    ]));

    $response->assertNotFound();
});

test('webhook verify supports ycloud challenge/token params', function () {
    $response = $this->get(route('webhooks.whatsapp.verify', [
        'tenantUuid' => $this->tenant->id,
        'channelSlug' => $this->channel->slug,
        'challenge' => 'ycloud-challenge-456',
        'token' => 'test-verify-token',
    ]));

    $response->assertSuccessful();
    $response->assertSee('ycloud-challenge-456');
});

// --- Webhook Receive (POST) ---

test('webhook receive dispatches job for valid inbound message', function () {
    Queue::fake();

    $payload = makeYCloudInboundPayload();

    $response = $this->postJson(route('webhooks.whatsapp.receive', [
        'tenantUuid' => $this->tenant->id,
        'channelSlug' => $this->channel->slug,
    ]), $payload);

    $response->assertSuccessful();
    $response->assertJson(['status' => 'queued']);

    Queue::assertPushed(\App\Jobs\ProcessIncomingMessage::class, function ($job) {
        return $job->channel->id === $this->channel->id
            && $job->inboundMessage->from === '+51999888777'
            && $job->inboundMessage->content === 'Hola, quiero información';
    });
});

test('webhook receive ignores non-message events', function () {
    Queue::fake();

    $payload = [
        'type' => 'whatsapp.message.updated',
        'whatsappInboundMessage' => [],
    ];

    $response = $this->postJson(route('webhooks.whatsapp.receive', [
        'tenantUuid' => $this->tenant->id,
        'channelSlug' => $this->channel->slug,
    ]), $payload);

    $response->assertSuccessful();
    $response->assertJson(['status' => 'ignored']);

    Queue::assertNothingPushed();
});

test('webhook receive returns 404 for invalid tenant', function () {
    $payload = makeYCloudInboundPayload();

    $response = $this->postJson(route('webhooks.whatsapp.receive', [
        'tenantUuid' => '00000000-0000-0000-0000-000000000000',
        'channelSlug' => $this->channel->slug,
    ]), $payload);

    $response->assertNotFound();
});

test('webhook receive returns 404 for invalid channel', function () {
    $payload = makeYCloudInboundPayload();

    $response = $this->postJson(route('webhooks.whatsapp.receive', [
        'tenantUuid' => $this->tenant->id,
        'channelSlug' => 'invalid-slug',
    ]), $payload);

    $response->assertNotFound();
});

// --- WhatsAppWebhookHandler ---

test('handler resolves channel correctly', function () {
    $handler = app(WhatsAppWebhookHandler::class);

    $channel = $handler->resolveChannel($this->tenant->id, $this->channel->slug);

    expect($channel)->not->toBeNull();
    expect($channel->id)->toBe($this->channel->id);
});

test('handler returns null for inactive tenant', function () {
    $this->tenant->update(['is_active' => false]);
    $handler = app(WhatsAppWebhookHandler::class);

    expect($handler->resolveChannel($this->tenant->id, $this->channel->slug))->toBeNull();
});

test('handler returns null for inactive channel', function () {
    $this->channel->update(['is_active' => false]);
    $handler = app(WhatsAppWebhookHandler::class);

    expect($handler->resolveChannel($this->tenant->id, $this->channel->slug))->toBeNull();
});

test('handler identifies inbound message events', function () {
    $handler = app(WhatsAppWebhookHandler::class);

    expect($handler->isInboundMessageEvent(['type' => 'whatsapp.inbound_message.received']))->toBeTrue();
    expect($handler->isInboundMessageEvent(['type' => 'whatsapp.message.updated']))->toBeFalse();
    expect($handler->isInboundMessageEvent([]))->toBeFalse();
});

test('handler parses inbound text message', function () {
    $handler = app(WhatsAppWebhookHandler::class);

    $payload = makeYCloudInboundPayload();
    $message = $handler->parseInboundMessage($this->channel, $payload);

    expect($message->messageId)->toBe('wamid.test123');
    expect($message->from)->toBe('+51999888777');
    expect($message->type)->toBe('text');
    expect($message->content)->toBe('Hola, quiero información');
    expect($message->contactName)->toBe('Juan Pérez');
});

test('handler parses inbound image message', function () {
    $handler = app(WhatsAppWebhookHandler::class);

    $payload = [
        'type' => 'whatsapp.inbound_message.received',
        'whatsappInboundMessage' => [
            'id' => 'wamid.image123',
            'from' => '+51999888777',
            'to' => '+51912345678',
            'type' => 'image',
            'image' => [
                'link' => 'https://example.com/image.jpg',
                'caption' => 'Mira esta foto',
            ],
            'customerProfile' => ['name' => 'Juan'],
        ],
    ];

    $message = $handler->parseInboundMessage($this->channel, $payload);

    expect($message->type)->toBe('image');
    expect($message->content)->toBe('Mira esta foto');
    expect($message->media)->not->toBeNull();
    expect($message->isMedia())->toBeTrue();
    expect($message->isText())->toBeFalse();
});

// --- Helper ---

function makeYCloudInboundPayload(): array
{
    return [
        'type' => 'whatsapp.inbound_message.received',
        'whatsappInboundMessage' => [
            'id' => 'wamid.test123',
            'from' => '+51999888777',
            'to' => '+51912345678',
            'type' => 'text',
            'text' => [
                'body' => 'Hola, quiero información',
            ],
            'customerProfile' => [
                'name' => 'Juan Pérez',
            ],
            'sendTime' => '2026-02-13T10:30:00Z',
        ],
    ];
}
