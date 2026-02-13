<?php

use App\Models\Tenant;
use App\Models\User;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();

    $this->superAdmin = User::factory()->superAdmin()->create(['current_tenant_id' => $this->tenant->id]);
    $this->tenant->users()->attach($this->superAdmin->id, ['role' => 'tenant_owner', 'is_default' => true]);

    $this->regularUser = User::factory()->create(['current_tenant_id' => $this->tenant->id]);
    $this->tenant->users()->attach($this->regularUser->id, ['role' => 'tenant_member', 'is_default' => true]);
});

test('super-admin can access pulse dashboard', function () {
    $this->actingAs($this->superAdmin)
        ->get('/pulse')
        ->assertOk();
});

test('regular user gets 403 on pulse dashboard', function () {
    $this->actingAs($this->regularUser)
        ->get('/pulse')
        ->assertForbidden();
});

test('unauthenticated user cannot access pulse dashboard', function () {
    $this->get('/pulse')
        ->assertForbidden();
});

test('pulse config has expected recorders enabled', function () {
    $recorders = array_keys(config('pulse.recorders'));

    expect($recorders)->toContain(\Laravel\Pulse\Recorders\SlowQueries::class);
    expect($recorders)->toContain(\Laravel\Pulse\Recorders\SlowJobs::class);
    expect($recorders)->toContain(\Laravel\Pulse\Recorders\Exceptions::class);
    expect($recorders)->toContain(\Laravel\Pulse\Recorders\Queues::class);
    expect($recorders)->toContain(\Laravel\Pulse\Recorders\CacheInteractions::class);
});
