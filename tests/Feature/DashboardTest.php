<?php

use App\Models\Tenant;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users with tenant can visit the dashboard', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['current_tenant_id' => $tenant->id]);
    $tenant->users()->attach($user->id, ['role' => 'tenant_owner', 'is_default' => true]);

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('authenticated users without tenant are forbidden', function () {
    $user = User::factory()->create(['current_tenant_id' => null]);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertForbidden();
});
