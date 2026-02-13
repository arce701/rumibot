<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    $this->tenantA = Tenant::factory()->create();
    $this->userA = User::factory()->create(['current_tenant_id' => $this->tenantA->id]);
    $this->tenantA->users()->attach($this->userA->id, ['role' => 'tenant_owner', 'is_default' => true]);
    app()[PermissionRegistrar::class]->setPermissionsTeamId($this->tenantA->id);
    $this->userA->assignRole('tenant_owner');

    $this->tokenA = $this->userA->createToken('test-token')->plainTextToken;
});

test('tenant-api rate limiter is defined', function () {
    expect(RateLimiter::limiter('tenant-api'))->not->toBeNull();
});

test('webhook-whatsapp rate limiter is defined', function () {
    expect(RateLimiter::limiter('webhook-whatsapp'))->not->toBeNull();
});

test('webhook-payments rate limiter is defined', function () {
    expect(RateLimiter::limiter('webhook-payments'))->not->toBeNull();
});

test('API v1 routes have throttle middleware', function () {
    $route = app('router')->getRoutes()->getByName('api.v1.messages.send');

    expect($route)->not->toBeNull();

    $middleware = $route->gatherMiddleware();

    expect($middleware)->toContain('throttle:tenant-api');
});

test('WhatsApp webhook routes have throttle middleware', function () {
    $route = app('router')->getRoutes()->getByName('webhooks.whatsapp.receive');

    expect($route)->not->toBeNull();

    $middleware = $route->gatherMiddleware();

    expect($middleware)->toContain('throttle:webhook-whatsapp');
});

test('payment webhook routes have throttle middleware', function () {
    $route = app('router')->getRoutes()->getByName('webhooks.payments.mercadopago');

    expect($route)->not->toBeNull();

    $middleware = $route->gatherMiddleware();

    expect($middleware)->toContain('throttle:webhook-payments');
});

test('API v1 returns 429 after exceeding rate limit', function () {
    $route = route('api.v1.leads.index');

    for ($i = 0; $i < 60; $i++) {
        $this->withHeader('Authorization', 'Bearer '.$this->tokenA)
            ->getJson($route);
    }

    $response = $this->withHeader('Authorization', 'Bearer '.$this->tokenA)
        ->getJson($route);

    expect($response->status())->toBe(429);
});
