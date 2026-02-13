<?php

use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Enums\ConversationStatus;
use App\Models\Enums\LeadStatus;
use App\Models\Escalation;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    $this->tenant = Tenant::factory()->create();

    $this->owner = User::factory()->create(['current_tenant_id' => $this->tenant->id]);
    $this->tenant->users()->attach($this->owner->id, ['role' => 'tenant_owner', 'is_default' => true]);
    app()[PermissionRegistrar::class]->setPermissionsTeamId($this->tenant->id);
    $this->owner->assignRole('tenant_owner');

    app(App\Services\Tenant\TenantContext::class)->set($this->tenant);
});

test('create channel with all fields', function () {
    Livewire::actingAs($this->owner)
        ->test(\App\Livewire\Channels\ChannelManager::class)
        ->set('name', 'Test Sales Channel')
        ->set('slug', 'test-sales')
        ->set('type', 'sales')
        ->set('providerType', 'ycloud')
        ->set('providerApiKey', 'test-api-key-123')
        ->set('providerPhoneNumberId', '1234567890')
        ->set('providerBusinessAccountId', '9876543210')
        ->set('providerWebhookVerifyToken', 'verify-token')
        ->set('systemPromptOverride', 'Custom sales prompt')
        ->set('aiModelOverride', 'gpt-4o')
        ->set('aiTemperature', 0.7)
        ->set('isActive', true)
        ->set('showForm', true)
        ->call('create')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('channels', [
        'tenant_id' => $this->tenant->id,
        'name' => 'Test Sales Channel',
        'slug' => 'test-sales',
        'type' => 'sales',
        'provider_type' => 'ycloud',
        'provider_phone_number_id' => '1234567890',
        'system_prompt_override' => 'Custom sales prompt',
    ]);
});

test('edit channel', function () {
    $channel = Channel::factory()->create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Original Name',
    ]);

    Livewire::actingAs($this->owner)
        ->test(\App\Livewire\Channels\ChannelManager::class)
        ->call('edit', $channel->id)
        ->set('name', 'Updated Name')
        ->call('update')
        ->assertHasNoErrors();

    expect($channel->fresh()->name)->toBe('Updated Name');
});

test('soft delete channel', function () {
    $channel = Channel::factory()->create(['tenant_id' => $this->tenant->id]);

    Livewire::actingAs($this->owner)
        ->test(\App\Livewire\Channels\ChannelManager::class)
        ->call('deleteChannel', $channel->id);

    expect($channel->fresh()->trashed())->toBeTrue();
});

test('save tenant system prompt', function () {
    Livewire::actingAs($this->owner)
        ->test(\App\Livewire\Prompts\PromptEditor::class)
        ->set('systemPrompt', 'You are a helpful assistant for our company.')
        ->call('saveTenantPrompt')
        ->assertHasNoErrors();

    expect($this->tenant->fresh()->system_prompt)->toBe('You are a helpful assistant for our company.');
});

test('save channel system prompt override', function () {
    $channel = Channel::factory()->create([
        'tenant_id' => $this->tenant->id,
        'system_prompt_override' => null,
    ]);

    Livewire::actingAs($this->owner)
        ->test(\App\Livewire\Prompts\PromptEditor::class)
        ->set("channelPrompts.{$channel->id}", 'Channel specific prompt')
        ->call('saveChannelPrompt', $channel->id)
        ->assertHasNoErrors();

    expect($channel->fresh()->system_prompt_override)->toBe('Channel specific prompt');
});

test('conversations listed correctly with filters', function () {
    $channel = Channel::factory()->create(['tenant_id' => $this->tenant->id]);

    Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $channel->id,
        'contact_name' => 'Juan Perez',
        'status' => ConversationStatus::Active,
        'last_message_at' => now(),
    ]);

    Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $channel->id,
        'contact_name' => 'Maria Lopez',
        'status' => ConversationStatus::Closed,
        'last_message_at' => now()->subHour(),
    ]);

    Livewire::actingAs($this->owner)
        ->test(\App\Livewire\Conversations\ConversationList::class)
        ->assertSee('Juan Perez')
        ->assertSee('Maria Lopez')
        ->set('statusFilter', 'active')
        ->assertSee('Juan Perez')
        ->assertDontSee('Maria Lopez');
});

test('lead status update', function () {
    $lead = Lead::factory()->create([
        'tenant_id' => $this->tenant->id,
        'status' => LeadStatus::New,
    ]);

    Livewire::actingAs($this->owner)
        ->test(\App\Livewire\Leads\LeadsList::class)
        ->call('startEdit', $lead->id)
        ->set('editStatus', 'contacted')
        ->set('editNotes', 'Called and discussed')
        ->set('editScore', 75)
        ->call('updateLead')
        ->assertHasNoErrors();

    $lead->refresh();
    expect($lead->status)->toBe(LeadStatus::Contacted);
    expect($lead->notes)->toBe('Called and discussed');
    expect($lead->qualification_score)->toBe(75);
});

test('escalation resolved', function () {
    $channel = Channel::factory()->create(['tenant_id' => $this->tenant->id]);
    $conversation = Conversation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'channel_id' => $channel->id,
    ]);

    $escalation = Escalation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'conversation_id' => $conversation->id,
        'resolved_at' => null,
    ]);

    Livewire::actingAs($this->owner)
        ->test(\App\Livewire\Escalations\EscalationQueue::class)
        ->call('startResolve', $escalation->id)
        ->set('resolutionNote', 'Issue resolved by contacting customer')
        ->call('resolve')
        ->assertHasNoErrors();

    $escalation->refresh();
    expect($escalation->isResolved())->toBeTrue();
    expect($escalation->resolution_note)->toBe('Issue resolved by contacting customer');
});

test('invite team member with role', function () {
    Livewire::actingAs($this->owner)
        ->test(\App\Livewire\Team\TeamManager::class)
        ->set('showInviteForm', true)
        ->set('inviteEmail', 'newmember@example.com')
        ->set('inviteRole', 'tenant_member')
        ->call('invite')
        ->assertHasNoErrors();

    $newUser = User::where('email', 'newmember@example.com')->first();
    expect($newUser)->not->toBeNull();
    expect($this->tenant->users()->where('user_id', $newUser->id)->exists())->toBeTrue();
});

test('remove team member', function () {
    $member = User::factory()->create(['current_tenant_id' => $this->tenant->id]);
    $this->tenant->users()->attach($member->id, ['role' => 'tenant_member', 'is_default' => true]);
    app()[PermissionRegistrar::class]->setPermissionsTeamId($this->tenant->id);
    $member->assignRole('tenant_member');

    Livewire::actingAs($this->owner)
        ->test(\App\Livewire\Team\TeamManager::class)
        ->call('removeMember', $member->id);

    expect($this->tenant->users()->where('user_id', $member->id)->exists())->toBeFalse();
});

test('activity log shows changes', function () {
    $this->tenant->update(['name' => 'Updated Tenant Name']);

    Livewire::actingAs($this->owner)
        ->test(\App\Livewire\ActivityLog\ActivityLogViewer::class)
        ->assertSee('Tenant');
});

test('toggle channel active status', function () {
    $channel = Channel::factory()->create([
        'tenant_id' => $this->tenant->id,
        'is_active' => true,
    ]);

    Livewire::actingAs($this->owner)
        ->test(\App\Livewire\Channels\ChannelManager::class)
        ->call('toggleActive', $channel->id);

    expect($channel->fresh()->is_active)->toBeFalse();
});
