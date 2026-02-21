<?php

use App\Livewire\AiConfig\AiConfigManager;
use App\Models\Enums\AiProvider;
use App\Models\LlmCredential;
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

    $this->member = User::factory()->create(['current_tenant_id' => $this->tenant->id]);
    $this->tenant->users()->attach($this->member->id, ['role' => 'tenant_member', 'is_default' => true]);
    $this->member->assignRole('tenant_member');
});

test('guest is redirected to login', function () {
    $this->get(route('ai-config'))
        ->assertRedirect(route('login'));
});

test('tenant owner can access ai config page', function () {
    $this->actingAs($this->owner)
        ->get(route('ai-config'))
        ->assertOk();
});

test('tenant member cannot access ai config page', function () {
    $this->actingAs($this->member)
        ->get(route('ai-config'))
        ->assertForbidden();
});

test('owner can create a credential', function () {
    Livewire::actingAs($this->owner)
        ->test(AiConfigManager::class)
        ->set('credentialName', 'Test OpenAI Key')
        ->set('credentialProvider', 'openai')
        ->set('credentialApiKey', 'sk-test-12345')
        ->call('createCredential')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('llm_credentials', [
        'tenant_id' => $this->tenant->id,
        'name' => 'Test OpenAI Key',
        'provider' => 'openai',
    ]);
});

test('owner can update a credential', function () {
    $credential = LlmCredential::factory()->provider(AiProvider::OpenAi)->create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Old Name',
    ]);

    Livewire::actingAs($this->owner)
        ->test(AiConfigManager::class)
        ->call('editCredential', $credential->id)
        ->set('credentialName', 'New Name')
        ->call('updateCredential')
        ->assertHasNoErrors();

    expect($credential->fresh()->name)->toBe('New Name');
});

test('owner can delete a credential', function () {
    $credential = LlmCredential::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);

    Livewire::actingAs($this->owner)
        ->test(AiConfigManager::class)
        ->call('deleteCredential', $credential->id);

    expect($credential->fresh()->trashed())->toBeTrue();
});

test('deleting default credential clears tenant reference', function () {
    $credential = LlmCredential::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);

    $this->tenant->update(['default_llm_credential_id' => $credential->id]);

    Livewire::actingAs($this->owner)
        ->test(AiConfigManager::class)
        ->call('deleteCredential', $credential->id);

    expect($this->tenant->fresh()->default_llm_credential_id)->toBeNull();
});

test('owner can set default credential', function () {
    $credential = LlmCredential::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);

    Livewire::actingAs($this->owner)
        ->test(AiConfigManager::class)
        ->call('setDefaultCredential', $credential->id);

    expect($this->tenant->fresh()->default_llm_credential_id)->toBe($credential->id);
});

test('owner can save model settings', function () {
    $credential = LlmCredential::factory()->provider(AiProvider::OpenAi)->create([
        'tenant_id' => $this->tenant->id,
    ]);

    Livewire::actingAs($this->owner)
        ->test(AiConfigManager::class)
        ->set('selectedCredentialId', $credential->id)
        ->set('selectedModel', 'gpt-4o')
        ->set('aiTemperature', 0.85)
        ->set('aiMaxTokens', 1000)
        ->set('aiContextWindow', 30)
        ->set('aiStreaming', true)
        ->call('saveModelSettings')
        ->assertHasNoErrors();

    $this->tenant->refresh();
    expect($this->tenant->default_llm_credential_id)->toBe($credential->id);
    expect($this->tenant->default_ai_model)->toBe('gpt-4o');
    expect((float) $this->tenant->ai_temperature)->toBe(0.85);
    expect($this->tenant->ai_max_tokens)->toBe(1000);
    expect($this->tenant->ai_context_window)->toBe(30);
    expect($this->tenant->ai_streaming)->toBeTrue();
});

test('model settings validation rejects invalid values', function () {
    Livewire::actingAs($this->owner)
        ->test(AiConfigManager::class)
        ->set('aiTemperature', 3.0)
        ->set('aiMaxTokens', 50)
        ->set('aiContextWindow', 0)
        ->call('saveModelSettings')
        ->assertHasErrors(['aiTemperature', 'aiMaxTokens', 'aiContextWindow']);
});

test('credential creation validation requires all fields', function () {
    Livewire::actingAs($this->owner)
        ->test(AiConfigManager::class)
        ->set('credentialName', '')
        ->set('credentialProvider', '')
        ->set('credentialApiKey', '')
        ->call('createCredential')
        ->assertHasErrors(['credentialName', 'credentialProvider', 'credentialApiKey']);
});

test('available models update based on selected credential provider', function () {
    $credential = LlmCredential::factory()->provider(AiProvider::Anthropic)->create([
        'tenant_id' => $this->tenant->id,
    ]);

    $component = Livewire::actingAs($this->owner)
        ->test(AiConfigManager::class)
        ->set('selectedCredentialId', $credential->id);

    expect($component->get('availableModels'))->toBe(AiProvider::Anthropic->models());
});
