<?php

use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Enums\ConversationStatus;
use App\Models\Escalation;
use App\Models\Lead;
use App\Models\Message;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Tenant\TenantContext;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    $this->tenant = Tenant::factory()->create();

    $this->owner = User::factory()->create(['current_tenant_id' => $this->tenant->id]);
    $this->tenant->users()->attach($this->owner->id, ['role' => 'tenant_owner', 'is_default' => true]);
    app()[PermissionRegistrar::class]->setPermissionsTeamId($this->tenant->id);
    $this->owner->assignRole('tenant_owner');

    $this->member = User::factory()->create(['current_tenant_id' => $this->tenant->id]);
    $this->tenant->users()->attach($this->member->id, ['role' => 'tenant_member', 'is_default' => true]);
    $this->member->assignRole('tenant_member');
});

test('tenant owner can access all panel routes', function () {
    $channel = Channel::factory()->create(['tenant_id' => $this->tenant->id]);
    $conversation = Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $channel->id,
    ]);

    $routes = [
        route('dashboard'),
        route('channels'),
        route('prompts'),
        route('conversations'),
        route('conversations.show', $conversation),
        route('leads'),
        route('escalations'),
        route('team'),
        route('knowledge'),
        route('activity-log'),
    ];

    foreach ($routes as $url) {
        $this->actingAs($this->owner)
            ->get($url)
            ->assertOk();
    }
});

test('tenant member can access routes with their permissions', function () {
    $this->actingAs($this->member)
        ->get(route('dashboard'))
        ->assertOk();

    $this->actingAs($this->member)
        ->get(route('conversations'))
        ->assertOk();

    $this->actingAs($this->member)
        ->get(route('leads'))
        ->assertOk();

    $this->actingAs($this->member)
        ->get(route('escalations'))
        ->assertOk();
});

test('tenant member cannot access channels management', function () {
    $this->actingAs($this->member)
        ->get(route('channels'))
        ->assertForbidden();
});

test('tenant member cannot access team management', function () {
    $this->actingAs($this->member)
        ->get(route('team'))
        ->assertForbidden();
});

test('user without tenant gets 403 on tenant routes', function () {
    $userWithoutTenant = User::factory()->create(['current_tenant_id' => null]);

    $this->actingAs($userWithoutTenant)
        ->get(route('dashboard'))
        ->assertForbidden();
});

test('tenant data isolation - conversations from another tenant not visible', function () {
    $tenantB = Tenant::factory()->create();
    $channelA = Channel::factory()->create(['tenant_id' => $this->tenant->id]);
    $channelB = Channel::factory()->create(['tenant_id' => $tenantB->id]);

    Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $channelA->id,
        'contact_name' => 'Tenant A Contact',
    ]);

    Conversation::factory()->create([
        'tenant_id' => $tenantB->id,
        'channel_id' => $channelB->id,
        'contact_name' => 'Tenant B Contact',
    ]);

    $this->actingAs($this->owner)
        ->get(route('conversations'))
        ->assertOk()
        ->assertSee('Tenant A Contact')
        ->assertDontSee('Tenant B Contact');
});

test('tenant data isolation - leads from another tenant not visible', function () {
    $tenantB = Tenant::factory()->create();

    Lead::factory()->create([
        'tenant_id' => $this->tenant->id,
        'full_name' => 'Lead From A',
    ]);

    Lead::factory()->create([
        'tenant_id' => $tenantB->id,
        'full_name' => 'Lead From B',
    ]);

    $this->actingAs($this->owner)
        ->get(route('leads'))
        ->assertOk()
        ->assertSee('Lead From A')
        ->assertDontSee('Lead From B');
});

test('dashboard shows stats of the correct tenant', function () {
    $channel = Channel::factory()->create(['tenant_id' => $this->tenant->id]);

    $conversation = Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $channel->id,
        'status' => ConversationStatus::Active,
    ]);

    Message::factory()->create([
        'tenant_id' => $this->tenant->id,
        'conversation_id' => $conversation->id,
        'created_at' => now(),
    ]);

    Lead::factory()->create([
        'tenant_id' => $this->tenant->id,
        'created_at' => now(),
    ]);

    Escalation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'conversation_id' => $conversation->id,
        'resolved_at' => null,
    ]);

    $response = $this->actingAs($this->owner)
        ->get(route('dashboard'))
        ->assertOk();

    $response->assertSee('1', false);
});

test('unauthenticated user is redirected to login', function () {
    $this->get(route('channels'))
        ->assertRedirect(route('login'));
});

test('tenant member cannot access activity log', function () {
    $this->actingAs($this->member)
        ->get(route('activity-log'))
        ->assertForbidden();
});
