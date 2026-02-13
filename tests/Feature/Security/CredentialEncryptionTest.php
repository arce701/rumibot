<?php

use App\Jobs\DispatchIntegrationEvent;
use App\Models\Enums\WebhookEvent;
use App\Models\Tenant;
use App\Models\TenantIntegration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
});

test('tenant integration secret is encrypted at rest', function () {
    $integration = TenantIntegration::factory()->create([
        'tenant_id' => $this->tenant->id,
        'secret' => 'my-super-secret-key',
    ]);

    $rawSecret = DB::table('tenant_integrations')
        ->where('id', $integration->id)
        ->value('secret');

    expect($rawSecret)->not->toBe('my-super-secret-key');
    expect(Crypt::decryptString($rawSecret))->toBe('my-super-secret-key');
});

test('tenant integration secret is decrypted when accessed via model', function () {
    $integration = TenantIntegration::factory()->create([
        'tenant_id' => $this->tenant->id,
        'secret' => 'my-super-secret-key',
    ]);

    $fresh = TenantIntegration::withoutGlobalScopes()->find($integration->id);

    expect($fresh->secret)->toBe('my-super-secret-key');
});

test('HMAC signature uses decrypted secret', function () {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    $integration = TenantIntegration::factory()->withAllEvents()->create([
        'tenant_id' => $this->tenant->id,
        'secret' => 'hmac-test-secret',
    ]);

    $payload = [
        'event' => WebhookEvent::MessageReceived->value,
        'timestamp' => now()->toIso8601String(),
        'tenant_id' => $this->tenant->id,
        'data' => ['test' => true],
    ];

    $job = new DispatchIntegrationEvent($integration, $payload);
    $job->handle();

    Http::assertSent(function ($request) use ($payload) {
        $expectedSignature = hash_hmac('sha256', json_encode($payload), 'hmac-test-secret');

        return $request->hasHeader('X-Webhook-Signature', $expectedSignature);
    });
});

test('integration without secret has no encryption issue', function () {
    $integration = TenantIntegration::factory()->create([
        'tenant_id' => $this->tenant->id,
        'secret' => null,
    ]);

    $fresh = TenantIntegration::withoutGlobalScopes()->find($integration->id);

    expect($fresh->secret)->toBeNull();
});
