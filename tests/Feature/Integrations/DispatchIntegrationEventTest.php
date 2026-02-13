<?php

use App\Events\MessageReceived;
use App\Jobs\DispatchIntegrationEvent;
use App\Listeners\DispatchTenantIntegrationEvents;
use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Enums\IntegrationStatus;
use App\Models\Enums\WebhookEvent;
use App\Models\Message;
use App\Models\Tenant;
use App\Models\TenantIntegration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Pennant\Feature;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->channel = Channel::factory()->sales()->create(['tenant_id' => $this->tenant->id]);
    $this->conversation = Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $this->channel->id,
    ]);
});

test('DispatchIntegrationEvent sends HTTP POST with HMAC signature', function () {
    Http::fake([
        '*' => Http::response(['ok' => true], 200),
    ]);

    $integration = TenantIntegration::factory()->withAllEvents()->create([
        'tenant_id' => $this->tenant->id,
        'url' => 'https://n8n.example.com/webhook/test',
        'secret' => 'test-secret-key',
    ]);

    $payload = [
        'event' => WebhookEvent::MessageReceived->value,
        'timestamp' => now()->toIso8601String(),
        'tenant_id' => $this->tenant->id,
        'data' => ['test' => 'data'],
    ];

    $job = new DispatchIntegrationEvent($integration, $payload);
    $job->handle();

    Http::assertSent(function ($request) use ($payload) {
        $expectedSignature = hash_hmac('sha256', json_encode($payload), 'test-secret-key');

        return $request->url() === 'https://n8n.example.com/webhook/test'
            && $request->hasHeader('X-Webhook-Signature', $expectedSignature)
            && $request->hasHeader('Content-Type', 'application/json');
    });

    expect($integration->fresh()->failure_count)->toBe(0);
    expect($integration->fresh()->last_triggered_at)->not->toBeNull();
});

test('DispatchIntegrationEvent increments failure count on HTTP error', function () {
    Http::fake([
        '*' => Http::response(['error' => 'fail'], 500),
    ]);

    $integration = TenantIntegration::factory()->create([
        'tenant_id' => $this->tenant->id,
        'failure_count' => 0,
    ]);

    $payload = ['event' => 'test', 'data' => []];

    $job = new DispatchIntegrationEvent($integration, $payload);

    try {
        $job->handle();
    } catch (\Throwable) {
        // Expected — job calls $this->fail()
    }

    expect($integration->fresh()->failure_count)->toBe(1);
});

test('circuit breaker suspends integration after 5 failures', function () {
    Http::fake([
        '*' => Http::response(['error' => 'fail'], 500),
    ]);

    $integration = TenantIntegration::factory()->create([
        'tenant_id' => $this->tenant->id,
        'failure_count' => 4,
    ]);

    $payload = ['event' => 'test', 'data' => []];

    $job = new DispatchIntegrationEvent($integration, $payload);
    $job->handle();

    $integration->refresh();
    expect($integration->failure_count)->toBe(5);
    expect($integration->status)->toBe(IntegrationStatus::Suspended);
});

test('successful webhook resets failure count', function () {
    Http::fake([
        '*' => Http::response(['ok' => true], 200),
    ]);

    $integration = TenantIntegration::factory()->create([
        'tenant_id' => $this->tenant->id,
        'failure_count' => 3,
    ]);

    $payload = ['event' => 'test', 'data' => []];

    $job = new DispatchIntegrationEvent($integration, $payload);
    $job->handle();

    expect($integration->fresh()->failure_count)->toBe(0);
});

test('listener dispatches jobs for active integrations subscribed to event', function () {
    Queue::fake();
    Feature::define('webhook-integrations', fn () => true);

    $activeIntegration = TenantIntegration::factory()->withAllEvents()->create([
        'tenant_id' => $this->tenant->id,
        'status' => IntegrationStatus::Active,
    ]);

    $suspendedIntegration = TenantIntegration::factory()->suspended()->withAllEvents()->create([
        'tenant_id' => $this->tenant->id,
    ]);

    $unsubscribedIntegration = TenantIntegration::factory()->create([
        'tenant_id' => $this->tenant->id,
        'events' => [WebhookEvent::LeadCaptured->value],
        'status' => IntegrationStatus::Active,
    ]);

    $message = Message::factory()->create([
        'conversation_id' => $this->conversation->id,
        'tenant_id' => $this->tenant->id,
    ]);

    $listener = app(DispatchTenantIntegrationEvents::class);
    $listener->handle(new MessageReceived($message));

    Queue::assertPushed(DispatchIntegrationEvent::class, 1);
    Queue::assertPushed(DispatchIntegrationEvent::class, function ($job) use ($activeIntegration) {
        return $job->integration->id === $activeIntegration->id;
    });
});

test('webhook payload does not include signature when no secret configured', function () {
    Http::fake([
        '*' => Http::response(['ok' => true], 200),
    ]);

    $integration = TenantIntegration::factory()->create([
        'tenant_id' => $this->tenant->id,
        'secret' => null,
    ]);

    $payload = ['event' => 'test', 'data' => []];

    $job = new DispatchIntegrationEvent($integration, $payload);
    $job->handle();

    Http::assertSent(function ($request) {
        return ! $request->hasHeader('X-Webhook-Signature');
    });
});
