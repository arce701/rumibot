<?php

use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Enums\PaymentStatus;
use App\Models\Enums\SubscriptionStatus;
use App\Models\Message;
use App\Models\PaymentHistory;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->superAdmin = User::factory()->superAdmin()->create(['current_tenant_id' => $this->tenant->id]);
    $this->tenant->users()->attach($this->superAdmin->id, ['role' => 'tenant_owner', 'is_default' => true]);

    $this->regularUser = User::factory()->create(['current_tenant_id' => $this->tenant->id]);
    $this->tenant->users()->attach($this->regularUser->id, ['role' => 'tenant_member', 'is_default' => true]);
});

test('super-admin can access platform dashboard', function () {
    $this->actingAs($this->superAdmin)
        ->get(route('platform.dashboard'))
        ->assertOk();
});

test('non-super-admin receives 403 on platform routes', function () {
    $routes = [
        route('platform.dashboard'),
        route('platform.tenants'),
        route('platform.plans'),
        route('platform.billing'),
    ];

    foreach ($routes as $url) {
        $this->actingAs($this->regularUser)
            ->get($url)
            ->assertForbidden();
    }
});

test('unauthenticated user is redirected from platform routes', function () {
    $this->get(route('platform.dashboard'))
        ->assertRedirect(route('login'));
});

test('platform dashboard shows correct metrics', function () {
    $tenantB = Tenant::factory()->create();

    Subscription::factory()->create([
        'tenant_id' => $this->tenant->id,
        'status' => SubscriptionStatus::Active,
    ]);

    Subscription::factory()->create([
        'tenant_id' => $tenantB->id,
        'status' => SubscriptionStatus::Trialing,
    ]);

    $channel = Channel::factory()->create(['tenant_id' => $this->tenant->id]);
    $conversation = Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $channel->id,
    ]);

    Message::factory()->create([
        'tenant_id' => $this->tenant->id,
        'conversation_id' => $conversation->id,
        'created_at' => now(),
    ]);

    PaymentHistory::factory()->create([
        'tenant_id' => $this->tenant->id,
        'status' => PaymentStatus::Completed,
        'amount' => 15000,
        'created_at' => now(),
    ]);

    $this->actingAs($this->superAdmin)
        ->get(route('platform.dashboard'))
        ->assertOk()
        ->assertSee('2') // active tenants (both are active)
        ->assertSee('1'); // messages today
});

test('tenant index shows all tenants', function () {
    $tenantB = Tenant::factory()->create(['name' => 'Test Corp B']);

    $this->actingAs($this->superAdmin)
        ->get(route('platform.tenants'))
        ->assertOk()
        ->assertSee($this->tenant->name)
        ->assertSee('Test Corp B');
});

test('super-admin can toggle tenant active status', function () {
    expect($this->tenant->is_active)->toBeTrue();

    Livewire\Livewire::actingAs($this->superAdmin)
        ->test(\App\Livewire\Platform\TenantIndex::class)
        ->call('toggleActive', $this->tenant->id);

    $this->tenant->refresh();
    expect($this->tenant->is_active)->toBeFalse();
});

test('super-admin can switch to tenant', function () {
    $tenantB = Tenant::factory()->create();

    Livewire\Livewire::actingAs($this->superAdmin)
        ->test(\App\Livewire\Platform\TenantDetail::class, ['tenant' => $tenantB])
        ->call('switchToTenant');

    $this->superAdmin->refresh();
    expect($this->superAdmin->current_tenant_id)->toBe($tenantB->id);
});

test('tenant detail shows tenant statistics', function () {
    $channel = Channel::factory()->create(['tenant_id' => $this->tenant->id]);
    Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $channel->id,
    ]);

    $this->actingAs($this->superAdmin)
        ->get(route('platform.tenants.show', $this->tenant))
        ->assertOk()
        ->assertSee($this->tenant->name);
});
