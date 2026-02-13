<?php

use App\Events\ConversationClosed;
use App\Events\ConversationStarted;
use App\Events\LeadCaptured;
use App\Events\MessageReceived;
use App\Listeners\DispatchTenantIntegrationEvents;
use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Enums\ConversationStatus;
use App\Models\Enums\WebhookEvent;
use App\Models\Lead;
use App\Models\Message;
use App\Models\Tenant;
use App\Models\TenantIntegration;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    $this->tenant = Tenant::factory()->create();
    $this->channel = Channel::factory()->sales()->create(['tenant_id' => $this->tenant->id]);
    $this->conversation = Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $this->channel->id,
    ]);

    $this->owner = User::factory()->create(['current_tenant_id' => $this->tenant->id]);
    $this->tenant->users()->attach($this->owner->id, ['role' => 'tenant_owner', 'is_default' => true]);
    app()[PermissionRegistrar::class]->setPermissionsTeamId($this->tenant->id);
    $this->owner->assignRole('tenant_owner');

    app(\App\Services\Tenant\TenantContext::class)->set($this->tenant);
});

test('ConversationStarted event is dispatched when new conversation is created in ProcessIncomingMessage', function () {
    Event::fake([ConversationStarted::class, MessageReceived::class]);

    $inboundMessage = new \App\Services\WhatsApp\InboundMessage(
        messageId: 'msg-123',
        from: '+51999000111',
        to: '+51999000222',
        type: 'text',
        content: 'Hello',
        contactName: 'New Contact',
    );

    // Use a non-matching phone to force new conversation creation
    $job = new \App\Jobs\ProcessIncomingMessage($this->channel, $inboundMessage);

    try {
        $job->handle();
    } catch (\Throwable) {
        // AI response may fail in test, but events should fire before that
    }

    Event::assertDispatched(ConversationStarted::class);
    Event::assertDispatched(MessageReceived::class);
});

test('ConversationClosed event is dispatched from ConversationDetail', function () {
    Event::fake([ConversationClosed::class]);

    Livewire::actingAs($this->owner)
        ->test(\App\Livewire\Conversations\ConversationDetail::class, ['conversation' => $this->conversation])
        ->call('closeConversation');

    Event::assertDispatched(ConversationClosed::class, function (ConversationClosed $event) {
        return $event->conversation->id === $this->conversation->id;
    });

    expect($this->conversation->fresh()->status)->toBe(ConversationStatus::Closed);
});

test('DispatchTenantIntegrationEvents listener implements ShouldQueue', function () {
    expect(DispatchTenantIntegrationEvents::class)->toImplement(ShouldQueue::class);
});

test('integration CRUD via Livewire IntegrationManager', function () {
    Livewire::actingAs($this->owner)
        ->test(\App\Livewire\Integrations\IntegrationManager::class)
        ->set('name', 'Test n8n Integration')
        ->set('provider', 'n8n')
        ->set('url', 'https://n8n.example.com/webhook/test')
        ->set('selectedEvents', [WebhookEvent::MessageReceived->value, WebhookEvent::LeadCaptured->value])
        ->set('showForm', true)
        ->call('create')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tenant_integrations', [
        'tenant_id' => $this->tenant->id,
        'name' => 'Test n8n Integration',
        'provider' => 'n8n',
    ]);

    $integration = TenantIntegration::where('tenant_id', $this->tenant->id)->first();
    expect($integration->secret)->not->toBeNull();
    expect($integration->events)->toContain(WebhookEvent::MessageReceived->value);
});

test('mark integration as primary resets other primaries', function () {
    $first = TenantIntegration::factory()->primary()->create(['tenant_id' => $this->tenant->id]);
    $second = TenantIntegration::factory()->create(['tenant_id' => $this->tenant->id]);

    Livewire::actingAs($this->owner)
        ->test(\App\Livewire\Integrations\IntegrationManager::class)
        ->call('markAsPrimary', $second->id);

    expect($first->fresh()->is_primary)->toBeFalse();
    expect($second->fresh()->is_primary)->toBeTrue();
});

test('suspend and reactivate integration', function () {
    $integration = TenantIntegration::factory()->create(['tenant_id' => $this->tenant->id]);

    Livewire::actingAs($this->owner)
        ->test(\App\Livewire\Integrations\IntegrationManager::class)
        ->call('suspend', $integration->id);

    expect($integration->fresh()->status)->toBe(\App\Models\Enums\IntegrationStatus::Suspended);

    Livewire::actingAs($this->owner)
        ->test(\App\Livewire\Integrations\IntegrationManager::class)
        ->call('reactivate', $integration->id);

    expect($integration->fresh()->status)->toBe(\App\Models\Enums\IntegrationStatus::Active);
    expect($integration->fresh()->failure_count)->toBe(0);
});

test('delete integration via soft delete', function () {
    $integration = TenantIntegration::factory()->create(['tenant_id' => $this->tenant->id]);

    Livewire::actingAs($this->owner)
        ->test(\App\Livewire\Integrations\IntegrationManager::class)
        ->call('deleteIntegration', $integration->id);

    expect($integration->fresh()->trashed())->toBeTrue();
});
