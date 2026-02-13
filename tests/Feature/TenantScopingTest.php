<?php

use App\Models\Tenant;
use App\Models\User;
use App\Services\Tenant\TenantContext;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->tenantA = Tenant::factory()->create(['name' => 'Tenant A', 'slug' => 'tenant-a']);
    $this->tenantB = Tenant::factory()->create(['name' => 'Tenant B', 'slug' => 'tenant-b']);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
});

test('tenant is created with uuid primary key', function () {
    expect($this->tenantA->id)
        ->toBeString()
        ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
});

test('tenant can be soft deleted', function () {
    $this->tenantA->delete();

    expect($this->tenantA->trashed())->toBeTrue();
    expect(Tenant::count())->toBe(1);
    expect(Tenant::withTrashed()->count())->toBe(2);
});

test('tenant factory creates valid tenant', function () {
    expect($this->tenantA)
        ->name->toBe('Tenant A')
        ->slug->toBe('tenant-a')
        ->is_active->toBeTrue()
        ->is_platform_owner->toBeFalse();
});

test('tenant factory platform owner state works', function () {
    $tenant = Tenant::factory()->platformOwner()->create();

    expect($tenant->is_platform_owner)->toBeTrue();
});

test('tenant context singleton can be set and retrieved', function () {
    $context = app(TenantContext::class);

    expect($context->hasTenant())->toBeFalse();

    $context->set($this->tenantA);

    expect($context->hasTenant())->toBeTrue();
    expect($context->get()->id)->toBe($this->tenantA->id);
    expect($context->getTenantId())->toBe($this->tenantA->id);
});

test('tenant context can be cleared', function () {
    $context = app(TenantContext::class);
    $context->set($this->tenantA);
    $context->clear();

    expect($context->hasTenant())->toBeFalse();
    expect($context->get())->toBeNull();
});

test('user can belong to multiple tenants', function () {
    $user = User::factory()->create();

    $this->tenantA->users()->attach($user->id, ['role' => 'tenant_owner', 'is_default' => true]);
    $this->tenantB->users()->attach($user->id, ['role' => 'tenant_member', 'is_default' => false]);

    expect($user->tenants)->toHaveCount(2);
    expect($user->tenants->pluck('id')->toArray())->toContain($this->tenantA->id, $this->tenantB->id);
});

test('user can retrieve default tenant', function () {
    $user = User::factory()->create();

    $this->tenantA->users()->attach($user->id, ['role' => 'tenant_owner', 'is_default' => true]);
    $this->tenantB->users()->attach($user->id, ['role' => 'tenant_member', 'is_default' => false]);

    expect($user->defaultTenant()->id)->toBe($this->tenantA->id);
});

test('user super admin check works', function () {
    $regular = User::factory()->create();
    $admin = User::factory()->superAdmin()->create();

    expect($regular->isSuperAdmin())->toBeFalse();
    expect($admin->isSuperAdmin())->toBeTrue();
});

test('user can be soft deleted', function () {
    $user = User::factory()->create();
    $user->delete();

    expect($user->trashed())->toBeTrue();
    expect(User::withTrashed()->where('id', $user->id)->exists())->toBeTrue();
});

test('user current tenant relationship works', function () {
    $user = User::factory()->create(['current_tenant_id' => $this->tenantA->id]);

    expect($user->currentTenant->id)->toBe($this->tenantA->id);
});

test('roles can be assigned per tenant using teams', function () {
    $user = User::factory()->create();

    app()[PermissionRegistrar::class]->setPermissionsTeamId($this->tenantA->id);
    $user->assignRole('tenant_owner');

    app()[PermissionRegistrar::class]->setPermissionsTeamId($this->tenantB->id);
    $user->unsetRelation('roles');
    $user->assignRole('tenant_member');

    app()[PermissionRegistrar::class]->setPermissionsTeamId($this->tenantA->id);
    $user->unsetRelation('roles');
    expect($user->hasRole('tenant_owner'))->toBeTrue();
    expect($user->hasRole('tenant_member'))->toBeFalse();

    app()[PermissionRegistrar::class]->setPermissionsTeamId($this->tenantB->id);
    $user->unsetRelation('roles');
    expect($user->hasRole('tenant_member'))->toBeTrue();
    expect($user->hasRole('tenant_owner'))->toBeFalse();
});

test('super admin bypasses gate checks', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin);

    expect($admin->can('any-random-ability'))->toBeTrue();
});

test('regular user does not bypass gate checks', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    expect($user->can('any-random-ability'))->toBeFalse();
});

test('tenant context sets permissions team id', function () {
    $context = app(TenantContext::class);
    $context->set($this->tenantA);

    expect(app(PermissionRegistrar::class)->getPermissionsTeamId())->toBe($this->tenantA->id);
});

test('set current tenant middleware sets context from authenticated user', function () {
    $user = User::factory()->create(['current_tenant_id' => $this->tenantA->id]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful();

    expect(app(TenantContext::class)->getTenantId())->toBe($this->tenantA->id);
});

test('ensure tenant context middleware blocks without tenant', function () {
    $user = User::factory()->create(['current_tenant_id' => null]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertForbidden();
});

test('ensure super admin middleware blocks non-admin', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/test-super-admin')
        ->assertForbidden();
})->skip(fn () => true, 'Super admin routes will be created later');

test('set locale middleware applies user locale', function () {
    $user = User::factory()->create([
        'locale' => 'pt_BR',
        'current_tenant_id' => $this->tenantA->id,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'));

    expect(app()->getLocale())->toBe('pt_BR');
});

test('available locales are configured', function () {
    expect(config('app.available_locales'))->toBe(['es', 'en', 'pt_BR']);
});

test('all enums have correct values', function () {
    expect(\App\Models\Enums\ChannelType::Sales->value)->toBe('sales');
    expect(\App\Models\Enums\ChannelType::Support->value)->toBe('support');
    expect(\App\Models\Enums\ConversationStatus::Active->value)->toBe('active');
    expect(\App\Models\Enums\DocumentStatus::Ready->value)->toBe('ready');
    expect(\App\Models\Enums\LeadStatus::New->value)->toBe('new');
    expect(\App\Models\Enums\IntegrationStatus::Active->value)->toBe('active');
    expect(\App\Models\Enums\BillingInterval::Quarterly->value)->toBe('quarterly');
    expect(\App\Models\Enums\SubscriptionStatus::Active->value)->toBe('active');
    expect(\App\Models\Enums\PaymentProviderType::MercadoPago->value)->toBe('mercadopago');
    expect(\App\Models\Enums\PaymentStatus::Completed->value)->toBe('completed');
});

test('activity log records tenant creation', function () {
    $tenant = Tenant::factory()->create(['name' => 'Activity Test Tenant']);

    $activity = \Spatie\Activitylog\Models\Activity::query()
        ->where('subject_type', Tenant::class)
        ->where('subject_id', $tenant->id)
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->description)->toBe('created');
});
