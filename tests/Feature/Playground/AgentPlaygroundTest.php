<?php

use App\Ai\Agents\PlaygroundChatAgent;
use App\Livewire\Playground\AgentPlayground;
use App\Models\Channel;
use App\Models\Enums\DocumentStatus;
use App\Models\KnowledgeDocument;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    $this->tenant = Tenant::factory()->create();
    $this->channel = Channel::factory()->sales()->create(['tenant_id' => $this->tenant->id]);

    $this->owner = User::factory()->create(['current_tenant_id' => $this->tenant->id]);
    $this->tenant->users()->attach($this->owner->id, ['role' => 'tenant_owner', 'is_default' => true]);
    app()[PermissionRegistrar::class]->setPermissionsTeamId($this->tenant->id);
    $this->owner->assignRole('tenant_owner');

    $this->member = User::factory()->create(['current_tenant_id' => $this->tenant->id]);
    $this->tenant->users()->attach($this->member->id, ['role' => 'tenant_member', 'is_default' => true]);
    $this->member->assignRole('tenant_member');
});

test('guest is redirected to login', function () {
    $this->get(route('playground'))
        ->assertRedirect(route('login'));
});

test('tenant owner can access playground', function () {
    $this->actingAs($this->owner)
        ->get(route('playground'))
        ->assertOk();
});

test('tenant member with prompts.view can access playground', function () {
    $this->actingAs($this->member)
        ->get(route('playground'))
        ->assertOk();
});

test('playground auto-selects first channel on mount', function () {
    Livewire::actingAs($this->owner)
        ->test(AgentPlayground::class)
        ->assertSet('selectedChannelId', $this->channel->id);
});

test('selecting channel clears chat messages', function () {
    $secondChannel = Channel::factory()->create(['tenant_id' => $this->tenant->id]);

    Livewire::actingAs($this->owner)
        ->test(AgentPlayground::class)
        ->set('chatMessages', [['role' => 'user', 'content' => 'test']])
        ->call('selectChannel', $secondChannel->id)
        ->assertSet('chatMessages', [])
        ->assertSet('selectedChannelId', $secondChannel->id);
});

test('clear chat resets messages', function () {
    Livewire::actingAs($this->owner)
        ->test(AgentPlayground::class)
        ->set('chatMessages', [
            ['role' => 'user', 'content' => 'test message'],
            ['role' => 'assistant', 'content' => 'test response'],
        ])
        ->call('clearChat')
        ->assertSet('chatMessages', [])
        ->assertSet('messageText', '');
});

test('send message adds user and assistant messages', function () {
    PlaygroundChatAgent::fake(['Respuesta de prueba del agente.']);

    Livewire::actingAs($this->owner)
        ->test(AgentPlayground::class)
        ->set('messageText', 'Hola, quiero info sobre iTrade')
        ->call('sendMessage')
        ->assertSet('isLoading', false);

    PlaygroundChatAgent::assertPrompted(fn ($prompt) => $prompt->prompt === 'Hola, quiero info sobre iTrade');
});

test('send message with empty text does nothing', function () {
    PlaygroundChatAgent::fake();

    Livewire::actingAs($this->owner)
        ->test(AgentPlayground::class)
        ->set('messageText', '')
        ->call('sendMessage')
        ->assertSet('chatMessages', []);

    PlaygroundChatAgent::assertNeverPrompted();
});

test('document count shows ready documents for selected channel', function () {
    KnowledgeDocument::factory()->create([
        'tenant_id' => $this->tenant->id,
        'status' => DocumentStatus::Ready,
        'channel_scope' => [],
    ]);

    KnowledgeDocument::factory()->create([
        'tenant_id' => $this->tenant->id,
        'status' => DocumentStatus::Ready,
        'channel_scope' => [$this->channel->id],
    ]);

    KnowledgeDocument::factory()->create([
        'tenant_id' => $this->tenant->id,
        'status' => DocumentStatus::Pending,
        'channel_scope' => [],
    ]);

    $component = Livewire::actingAs($this->owner)
        ->test(AgentPlayground::class);

    expect($component->get('documentCount'))->toBe(2);
});
