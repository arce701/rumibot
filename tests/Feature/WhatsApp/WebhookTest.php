<?php

use App\Http\Controllers\WhatsAppWebhookController;
use App\Models\Channel;
use App\Models\Tenant;
use App\Services\WhatsApp\WhatsAppWebhookHandler;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->channel = Channel::factory()->create([
        'tenant_id' => $this->tenant->id,
        'provider_phone_number_id' => '123456789',
    ]);
    $this->verifyToken = WhatsAppWebhookController::generateVerifyToken($this->tenant->id);
});

// --- Webhook Verification (GET) ---

test('webhook verify returns challenge with correct token', function () {
    $response = $this->get(route('webhooks.whatsapp.verify', [
        'tenantUuid' => $this->tenant->id,
        'hub_mode' => 'subscribe',
        'hub_challenge' => 'test-challenge-123',
        'hub_verify_token' => $this->verifyToken,
    ]));

    $response->assertSuccessful();
    $response->assertSee('test-challenge-123');
});

test('webhook verify returns 403 with incorrect token', function () {
    $response = $this->get(route('webhooks.whatsapp.verify', [
        'tenantUuid' => $this->tenant->id,
        'hub_mode' => 'subscribe',
        'hub_challenge' => 'test-challenge-123',
        'hub_verify_token' => 'wrong-token',
    ]));

    $response->assertForbidden();
});

test('webhook verify returns 403 without subscribe mode', function () {
    $response = $this->get(route('webhooks.whatsapp.verify', [
        'tenantUuid' => $this->tenant->id,
        'hub_challenge' => 'test-challenge-123',
        'hub_verify_token' => $this->verifyToken,
    ]));

    $response->assertForbidden();
});

test('webhook verify token is deterministic per tenant', function () {
    $token1 = WhatsAppWebhookController::generateVerifyToken($this->tenant->id);
    $token2 = WhatsAppWebhookController::generateVerifyToken($this->tenant->id);

    expect($token1)->toBe($token2);

    $otherTenant = Tenant::factory()->create();
    $otherToken = WhatsAppWebhookController::generateVerifyToken($otherTenant->id);

    expect($otherToken)->not->toBe($token1);
});

// --- Webhook Receive (POST) ---

test('webhook receive dispatches job for valid inbound message', function () {
    Queue::fake();

    $payload = makeMetaInboundPayload('123456789', '+51999888777');

    $response = $this->postJson(route('webhooks.whatsapp.receive', [
        'tenantUuid' => $this->tenant->id,
    ]), $payload);

    $response->assertSuccessful();
    $response->assertJson(['status' => 'queued']);

    Queue::assertPushed(\App\Jobs\ProcessIncomingMessage::class, function ($job) {
        return $job->channel->id === $this->channel->id
            && $job->inboundMessage->from === '+51999888777'
            && $job->inboundMessage->content === 'Hola, quiero información';
    });
});

test('webhook receive ignores status events', function () {
    Queue::fake();

    $payload = [
        'object' => 'whatsapp_business_account',
        'entry' => [[
            'id' => '123',
            'changes' => [[
                'field' => 'messages',
                'value' => [
                    'messaging_product' => 'whatsapp',
                    'metadata' => ['phone_number_id' => '123456789'],
                    'statuses' => [['id' => 'wamid.xyz', 'status' => 'delivered']],
                ],
            ]],
        ]],
    ];

    $response = $this->postJson(route('webhooks.whatsapp.receive', [
        'tenantUuid' => $this->tenant->id,
    ]), $payload);

    $response->assertSuccessful();
    $response->assertJson(['status' => 'ignored']);

    Queue::assertNothingPushed();
});

test('webhook receive ignores non-whatsapp events', function () {
    Queue::fake();

    $payload = ['object' => 'instagram', 'entry' => []];

    $response = $this->postJson(route('webhooks.whatsapp.receive', [
        'tenantUuid' => $this->tenant->id,
    ]), $payload);

    $response->assertSuccessful();
    $response->assertJson(['status' => 'ignored']);

    Queue::assertNothingPushed();
});

test('webhook receive returns 404 for unknown phone number id', function () {
    $payload = makeMetaInboundPayload('999999999', '+51999888777');

    $response = $this->postJson(route('webhooks.whatsapp.receive', [
        'tenantUuid' => $this->tenant->id,
    ]), $payload);

    $response->assertNotFound();
});

test('webhook receive returns 404 for inactive tenant', function () {
    $this->tenant->update(['is_active' => false]);

    $payload = makeMetaInboundPayload('123456789', '+51999888777');

    $response = $this->postJson(route('webhooks.whatsapp.receive', [
        'tenantUuid' => $this->tenant->id,
    ]), $payload);

    $response->assertNotFound();
});

test('webhook receive returns 404 for inactive channel', function () {
    $this->channel->update(['is_active' => false]);

    $payload = makeMetaInboundPayload('123456789', '+51999888777');

    $response = $this->postJson(route('webhooks.whatsapp.receive', [
        'tenantUuid' => $this->tenant->id,
    ]), $payload);

    $response->assertNotFound();
});

// --- WhatsAppWebhookHandler ---

test('handler resolves channel by phone number id', function () {
    $handler = app(WhatsAppWebhookHandler::class);

    $channel = $handler->resolveChannelByPhoneNumberId($this->tenant->id, '123456789');

    expect($channel)->not->toBeNull();
    expect($channel->id)->toBe($this->channel->id);
});

test('handler returns null for inactive tenant', function () {
    $this->tenant->update(['is_active' => false]);
    $handler = app(WhatsAppWebhookHandler::class);

    expect($handler->resolveChannelByPhoneNumberId($this->tenant->id, '123456789'))->toBeNull();
});

test('handler returns null for inactive channel', function () {
    $this->channel->update(['is_active' => false]);
    $handler = app(WhatsAppWebhookHandler::class);

    expect($handler->resolveChannelByPhoneNumberId($this->tenant->id, '123456789'))->toBeNull();
});

test('handler identifies inbound message events', function () {
    $handler = app(WhatsAppWebhookHandler::class);

    $validPayload = makeMetaInboundPayload('123456789', '+51999888777');
    expect($handler->isInboundMessageEvent($validPayload))->toBeTrue();

    $statusPayload = [
        'object' => 'whatsapp_business_account',
        'entry' => [['changes' => [['field' => 'messages', 'value' => ['statuses' => []]]]]],
    ];
    expect($handler->isInboundMessageEvent($statusPayload))->toBeFalse();

    expect($handler->isInboundMessageEvent([]))->toBeFalse();
});

test('handler extracts phone number id from payload', function () {
    $handler = app(WhatsAppWebhookHandler::class);

    $payload = makeMetaInboundPayload('123456789', '+51999888777');
    expect($handler->extractPhoneNumberId($payload))->toBe('123456789');

    expect($handler->extractPhoneNumberId([]))->toBeNull();
});

test('handler parses inbound text message', function () {
    $handler = app(WhatsAppWebhookHandler::class);

    $payload = makeMetaInboundPayload('123456789', '+51999888777');
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
        'object' => 'whatsapp_business_account',
        'entry' => [[
            'id' => '123',
            'changes' => [[
                'field' => 'messages',
                'value' => [
                    'messaging_product' => 'whatsapp',
                    'metadata' => [
                        'display_phone_number' => '+51912345678',
                        'phone_number_id' => '123456789',
                    ],
                    'contacts' => [['profile' => ['name' => 'Juan']]],
                    'messages' => [[
                        'id' => 'wamid.image123',
                        'from' => '+51999888777',
                        'timestamp' => '1708858200',
                        'type' => 'image',
                        'image' => [
                            'id' => 'img123',
                            'caption' => 'Mira esta foto',
                        ],
                    ]],
                ],
            ]],
        ]],
    ];

    $message = $handler->parseInboundMessage($this->channel, $payload);

    expect($message->type)->toBe('image');
    expect($message->content)->toBe('Mira esta foto');
    expect($message->media)->not->toBeNull();
    expect($message->isMedia())->toBeTrue();
    expect($message->isText())->toBeFalse();
});

// --- Helper ---

function makeMetaInboundPayload(string $phoneNumberId, string $from): array
{
    return [
        'object' => 'whatsapp_business_account',
        'entry' => [[
            'id' => '123',
            'changes' => [[
                'field' => 'messages',
                'value' => [
                    'messaging_product' => 'whatsapp',
                    'metadata' => [
                        'display_phone_number' => '+51912345678',
                        'phone_number_id' => $phoneNumberId,
                    ],
                    'contacts' => [[
                        'profile' => ['name' => 'Juan Pérez'],
                        'wa_id' => $from,
                    ]],
                    'messages' => [[
                        'id' => 'wamid.test123',
                        'from' => $from,
                        'timestamp' => '1708858200',
                        'type' => 'text',
                        'text' => [
                            'body' => 'Hola, quiero información',
                        ],
                    ]],
                ],
            ]],
        ]],
    ];
}
