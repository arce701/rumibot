<?php

use App\Models\Enums\PaymentProviderType;
use App\Models\Enums\PaymentStatus;
use App\Models\Enums\SubscriptionStatus;
use App\Models\PaymentHistory;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\Billing\MercadoPagoProvider;
use App\Services\Billing\WebhookPayload;
use App\Services\Tenant\TenantContext;

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    $this->tenant = Tenant::factory()->create();
    app(TenantContext::class)->set($this->tenant);

    $this->subscription = Subscription::factory()->create([
        'tenant_id' => $this->tenant->id,
        'external_subscription_id' => 'ext_sub_123',
        'payment_provider' => PaymentProviderType::MercadoPago,
    ]);
});

test('webhook processes completed payment correctly', function () {
    $mock = Mockery::mock(MercadoPagoProvider::class);
    $mock->shouldReceive('handleWebhook')->once()->andReturn(new WebhookPayload(
        eventType: 'payment',
        externalPaymentId: 'pay_123',
        externalSubscriptionId: 'ext_sub_123',
        status: 'completed',
        amount: 15000,
        currency: 'PEN',
        rawPayload: ['type' => 'payment'],
    ));

    $this->app->instance(MercadoPagoProvider::class, $mock);

    $response = $this->postJson(route('webhooks.payments.mercadopago'), [
        'type' => 'payment',
        'data' => ['id' => 'pay_123'],
    ]);

    $response->assertOk();
    $response->assertJson(['status' => 'processed']);
});

test('webhook updates subscription status', function () {
    $mock = Mockery::mock(MercadoPagoProvider::class);
    $mock->shouldReceive('handleWebhook')->once()->andReturn(new WebhookPayload(
        eventType: 'payment',
        externalPaymentId: 'pay_456',
        externalSubscriptionId: 'ext_sub_123',
        status: 'completed',
        amount: 15000,
        currency: 'PEN',
        rawPayload: [],
    ));

    $this->app->instance(MercadoPagoProvider::class, $mock);

    $this->subscription->update(['status' => SubscriptionStatus::PastDue]);

    $this->postJson(route('webhooks.payments.mercadopago'), []);

    $this->subscription->refresh();
    expect($this->subscription->status)->toBe(SubscriptionStatus::Active);
});

test('webhook records payment history', function () {
    $mock = Mockery::mock(MercadoPagoProvider::class);
    $mock->shouldReceive('handleWebhook')->once()->andReturn(new WebhookPayload(
        eventType: 'payment',
        externalPaymentId: 'pay_789',
        externalSubscriptionId: 'ext_sub_123',
        status: 'completed',
        amount: 15000,
        currency: 'PEN',
        rawPayload: [],
    ));

    $this->app->instance(MercadoPagoProvider::class, $mock);

    $this->postJson(route('webhooks.payments.mercadopago'), []);

    $payment = PaymentHistory::withoutGlobalScopes()
        ->where('external_payment_id', 'pay_789')
        ->first();

    expect($payment)->not->toBeNull();
    expect($payment->status)->toBe(PaymentStatus::Completed);
    expect($payment->amount)->toBe(15000);
});

test('webhook ignores missing subscription id', function () {
    $mock = Mockery::mock(MercadoPagoProvider::class);
    $mock->shouldReceive('handleWebhook')->once()->andReturn(new WebhookPayload(
        eventType: 'payment',
        externalPaymentId: null,
        externalSubscriptionId: null,
        status: null,
        amount: null,
        currency: null,
        rawPayload: [],
    ));

    $this->app->instance(MercadoPagoProvider::class, $mock);

    $response = $this->postJson(route('webhooks.payments.mercadopago'), []);

    $response->assertOk();
    $response->assertJson(['status' => 'ignored']);
});

test('webhook ignores unknown subscription id', function () {
    $mock = Mockery::mock(MercadoPagoProvider::class);
    $mock->shouldReceive('handleWebhook')->once()->andReturn(new WebhookPayload(
        eventType: 'payment',
        externalPaymentId: 'pay_unknown',
        externalSubscriptionId: 'unknown_sub_id',
        status: 'completed',
        amount: 15000,
        currency: 'PEN',
        rawPayload: [],
    ));

    $this->app->instance(MercadoPagoProvider::class, $mock);

    $response = $this->postJson(route('webhooks.payments.mercadopago'), []);

    $response->assertOk();
    $response->assertJson(['status' => 'ignored']);
});

test('webhook handles failed payment status', function () {
    $mock = Mockery::mock(MercadoPagoProvider::class);
    $mock->shouldReceive('handleWebhook')->once()->andReturn(new WebhookPayload(
        eventType: 'payment',
        externalPaymentId: 'pay_fail',
        externalSubscriptionId: 'ext_sub_123',
        status: 'failed',
        amount: 15000,
        currency: 'PEN',
        rawPayload: [],
    ));

    $this->app->instance(MercadoPagoProvider::class, $mock);

    $this->postJson(route('webhooks.payments.mercadopago'), []);

    $this->subscription->refresh();
    expect($this->subscription->status)->toBe(SubscriptionStatus::PastDue);

    $payment = PaymentHistory::withoutGlobalScopes()
        ->where('external_payment_id', 'pay_fail')
        ->first();

    expect($payment->status)->toBe(PaymentStatus::Failed);
});
