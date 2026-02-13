<?php

use App\Events\ConversationClosed;
use App\Jobs\SendWhatsAppMessage;
use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Enums\ConversationStatus;
use App\Models\Enums\LeadStatus;
use App\Models\Escalation;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    $this->tenant = Tenant::factory()->create();
    $this->channel = Channel::factory()->sales()->create(['tenant_id' => $this->tenant->id]);

    $this->user = User::factory()->create(['current_tenant_id' => $this->tenant->id]);
    $this->tenant->users()->attach($this->user->id, ['role' => 'tenant_owner', 'is_default' => true]);
    app()[PermissionRegistrar::class]->setPermissionsTeamId($this->tenant->id);
    $this->user->assignRole('tenant_owner');

    app(\App\Services\Tenant\TenantContext::class)->set($this->tenant);
});

test('unauthenticated request returns 401', function () {
    $this->getJson('/api/v1/conversations')->assertStatus(401);
});

test('list conversations returns paginated JSON', function () {
    Sanctum::actingAs($this->user);

    Conversation::factory()->count(3)->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $this->channel->id,
    ]);

    $response = $this->getJson('/api/v1/conversations');

    $response->assertOk();
    $response->assertJsonStructure(['data']);
    $response->assertJsonCount(3, 'data');
});

test('list conversations filters by status', function () {
    Sanctum::actingAs($this->user);

    Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $this->channel->id,
        'status' => ConversationStatus::Active,
    ]);

    Conversation::factory()->closed()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $this->channel->id,
    ]);

    $response = $this->getJson('/api/v1/conversations?filter[status]=active');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
});

test('list leads returns paginated JSON', function () {
    Sanctum::actingAs($this->user);

    Lead::factory()->count(2)->create([
        'tenant_id' => $this->tenant->id,
        'conversation_id' => Conversation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'channel_id' => $this->channel->id,
        ])->id,
    ]);

    $response = $this->getJson('/api/v1/leads');

    $response->assertOk();
    $response->assertJsonCount(2, 'data');
});

test('send message dispatches SendWhatsAppMessage job', function () {
    Queue::fake();
    Sanctum::actingAs($this->user);

    $conversation = Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $this->channel->id,
    ]);

    $response = $this->postJson('/api/v1/messages/send', [
        'conversation_id' => $conversation->id,
        'text' => 'Hello from API!',
    ]);

    $response->assertStatus(202);
    Queue::assertPushed(SendWhatsAppMessage::class, function ($job) use ($conversation) {
        return $job->conversation->id === $conversation->id
            && $job->text === 'Hello from API!';
    });
});

test('update lead via API', function () {
    Sanctum::actingAs($this->user);

    $conversation = Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $this->channel->id,
    ]);

    $lead = Lead::factory()->create([
        'tenant_id' => $this->tenant->id,
        'conversation_id' => $conversation->id,
        'status' => LeadStatus::New,
    ]);

    $response = $this->putJson("/api/v1/leads/{$lead->id}", [
        'status' => 'contacted',
        'notes' => 'Called via n8n automation',
        'qualification_score' => 80,
    ]);

    $response->assertOk();

    $lead->refresh();
    expect($lead->status)->toBe(LeadStatus::Contacted);
    expect($lead->notes)->toBe('Called via n8n automation');
    expect($lead->qualification_score)->toBe(80);
});

test('close conversation via API dispatches event', function () {
    Event::fake([ConversationClosed::class]);
    Sanctum::actingAs($this->user);

    $conversation = Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $this->channel->id,
        'status' => ConversationStatus::Active,
    ]);

    $response = $this->postJson("/api/v1/conversations/{$conversation->id}/close");

    $response->assertOk();
    expect($conversation->fresh()->status)->toBe(ConversationStatus::Closed);
    Event::assertDispatched(ConversationClosed::class);
});

test('add escalation note resolves escalation', function () {
    Sanctum::actingAs($this->user);

    $conversation = Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $this->channel->id,
    ]);

    $escalation = Escalation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'conversation_id' => $conversation->id,
    ]);

    $response = $this->postJson("/api/v1/escalations/{$escalation->id}/note", [
        'resolution_note' => 'Resolved via automation',
    ]);

    $response->assertOk();

    $escalation->refresh();
    expect($escalation->isResolved())->toBeTrue();
    expect($escalation->resolution_note)->toBe('Resolved via automation');
});

test('send message validation rejects missing fields', function () {
    Sanctum::actingAs($this->user);

    $response = $this->postJson('/api/v1/messages/send', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['conversation_id', 'text']);
});
