<?php

use App\Models\Enums\AiProvider;
use App\Models\LlmCredential;
use App\Models\Tenant;
use App\Services\Tenant\TenantContext;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->otherTenant = Tenant::factory()->create();
});

test('llm credential is created with uuid primary key', function () {
    $credential = LlmCredential::factory()->create(['tenant_id' => $this->tenant->id]);

    expect($credential->id)
        ->toBeString()
        ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
});

test('llm credential belongs to tenant', function () {
    $credential = LlmCredential::factory()->create(['tenant_id' => $this->tenant->id]);

    expect($credential->tenant->id)->toBe($this->tenant->id);
});

test('llm credential casts provider to enum', function () {
    $credential = LlmCredential::factory()->provider(AiProvider::OpenAi)->create([
        'tenant_id' => $this->tenant->id,
    ]);

    expect($credential->provider)->toBe(AiProvider::OpenAi);
});

test('llm credential encrypts api_key at rest', function () {
    $credential = LlmCredential::factory()->create([
        'tenant_id' => $this->tenant->id,
        'api_key' => 'sk-test-secret-key-12345',
    ]);

    $rawValue = DB::table('llm_credentials')
        ->where('id', $credential->id)
        ->value('api_key');

    expect($rawValue)->not->toBe('sk-test-secret-key-12345');
    expect(Crypt::decryptString($rawValue))->toBe('sk-test-secret-key-12345');
});

test('llm credential decrypts api_key when accessed via model', function () {
    $credential = LlmCredential::factory()->create([
        'tenant_id' => $this->tenant->id,
        'api_key' => 'sk-test-secret-key-12345',
    ]);

    $fresh = LlmCredential::withoutGlobalScopes()->find($credential->id);

    expect($fresh->api_key)->toBe('sk-test-secret-key-12345');
});

test('llm credential can be soft deleted', function () {
    $credential = LlmCredential::factory()->create(['tenant_id' => $this->tenant->id]);
    $credential->delete();

    expect($credential->trashed())->toBeTrue();
    expect(LlmCredential::withoutGlobalScopes()->withTrashed()->where('id', $credential->id)->exists())->toBeTrue();
    expect(LlmCredential::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->where('id', $credential->id)->exists())->toBeFalse();
});

test('llm credential is scoped by tenant', function () {
    LlmCredential::factory()->create(['tenant_id' => $this->tenant->id]);
    LlmCredential::factory()->create(['tenant_id' => $this->otherTenant->id]);

    $context = app(TenantContext::class);
    $context->set($this->tenant);

    expect(LlmCredential::count())->toBe(1);
});

test('tenant has many llm credentials', function () {
    LlmCredential::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);

    expect($this->tenant->llmCredentials)->toHaveCount(3);
});

test('tenant can have a default llm credential', function () {
    $credential = LlmCredential::factory()->create(['tenant_id' => $this->tenant->id]);
    $this->tenant->update(['default_llm_credential_id' => $credential->id]);

    expect($this->tenant->fresh()->defaultLlmCredential->id)->toBe($credential->id);
});
