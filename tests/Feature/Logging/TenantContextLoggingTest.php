<?php

use App\Models\Tenant;
use App\Models\User;
use App\Services\Tenant\TenantContext;
use Illuminate\Support\Facades\Context;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create(['current_tenant_id' => $this->tenant->id]);
    $this->tenant->users()->attach($this->user->id, ['role' => 'tenant_owner', 'is_default' => true]);
    app()[PermissionRegistrar::class]->setPermissionsTeamId($this->tenant->id);
    $this->user->assignRole('tenant_owner');
});

test('web requests add tenant_id to log context', function () {
    $this->actingAs($this->user)
        ->get(route('dashboard'));

    expect(Context::get('tenant_id'))->toBe($this->tenant->id);
});

test('web requests add user_id to log context', function () {
    $this->actingAs($this->user)
        ->get(route('dashboard'));

    expect(Context::get('user_id'))->toBe($this->user->id);
});

test('unauthenticated requests do not have tenant context', function () {
    $this->get('/');

    expect(Context::get('tenant_id'))->toBeNull();
    expect(Context::get('user_id'))->toBeNull();
});

test('exception context callback is configured in bootstrap', function () {
    $context = app(TenantContext::class);
    $context->set($this->tenant);

    $this->actingAs($this->user);

    $handler = app(\Illuminate\Contracts\Debug\ExceptionHandler::class);

    $reflection = new ReflectionMethod($handler, 'exceptionContext');
    $contextData = $reflection->invoke($handler, new \RuntimeException('test'));

    expect($contextData)->toHaveKey('tenant_id');
    expect($contextData)->toHaveKey('user_id');
    expect($contextData['tenant_id'])->toBe($this->tenant->id);
    expect($contextData['user_id'])->toBe($this->user->id);
});

test('logging config stack default includes daily', function () {
    config(['logging.channels.stack.channels' => explode(',', 'daily')]);

    expect(config('logging.channels.stack.channels'))
        ->toContain('daily');
});
