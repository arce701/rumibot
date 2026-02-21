<?php

namespace App\Livewire\AiConfig;

use App\Models\Enums\AiProvider;
use App\Models\LlmCredential;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts::app')]
#[Title('AI Configuration')]
class AiConfigManager extends Component
{
    use AuthorizesRequests;

    public bool $showForm = false;

    public ?string $editingCredentialId = null;

    #[Validate('required|string|max:100')]
    public string $credentialName = '';

    #[Validate('required|string')]
    public string $credentialProvider = '';

    #[Validate('required_without:editingCredentialId|string|max:500')]
    public string $credentialApiKey = '';

    public ?string $selectedCredentialId = null;

    public ?string $selectedModel = null;

    public float $aiTemperature = 0.70;

    public int $aiMaxTokens = 500;

    public int $aiContextWindow = 50;

    public bool $aiStreaming = false;

    public function mount(): void
    {
        $this->authorize('ai-config.view');

        $tenant = auth()->user()->currentTenant;
        $this->selectedCredentialId = $tenant->default_llm_credential_id;
        $this->selectedModel = $tenant->default_ai_model;
        $this->aiTemperature = (float) ($tenant->ai_temperature ?? 0.70);
        $this->aiMaxTokens = $tenant->ai_max_tokens ?? 500;
        $this->aiContextWindow = $tenant->ai_context_window ?? 50;
        $this->aiStreaming = $tenant->ai_streaming ?? false;
    }

    public function createCredential(): void
    {
        $this->authorize('ai-config.update');

        $this->validate([
            'credentialName' => 'required|string|max:100',
            'credentialProvider' => 'required|string',
            'credentialApiKey' => 'required|string|max:500',
        ]);

        $credential = LlmCredential::create([
            'tenant_id' => auth()->user()->currentTenant->id,
            'name' => $this->credentialName,
            'provider' => $this->credentialProvider,
            'api_key' => $this->credentialApiKey,
        ]);

        $tenant = auth()->user()->currentTenant;
        if (! $tenant->default_llm_credential_id) {
            $tenant->update(['default_llm_credential_id' => $credential->id]);
            $this->selectedCredentialId = $credential->id;
        }

        $this->resetCredentialForm();
        session()->flash('message', __('Credential created successfully.'));
    }

    public function editCredential(string $credentialId): void
    {
        $this->authorize('ai-config.update');

        $credential = LlmCredential::findOrFail($credentialId);
        $this->editingCredentialId = $credential->id;
        $this->credentialName = $credential->name;
        $this->credentialProvider = $credential->provider->value;
        $this->credentialApiKey = '';
        $this->showForm = true;
    }

    public function updateCredential(): void
    {
        $this->authorize('ai-config.update');

        $rules = [
            'credentialName' => 'required|string|max:100',
            'credentialProvider' => 'required|string',
        ];

        if ($this->credentialApiKey !== '') {
            $rules['credentialApiKey'] = 'string|max:500';
        }

        $this->validate($rules);

        $credential = LlmCredential::findOrFail($this->editingCredentialId);

        $data = [
            'name' => $this->credentialName,
            'provider' => $this->credentialProvider,
        ];

        if ($this->credentialApiKey !== '') {
            $data['api_key'] = $this->credentialApiKey;
        }

        $credential->update($data);

        $this->resetCredentialForm();
        session()->flash('message', __('Credential updated successfully.'));
    }

    public function deleteCredential(string $credentialId): void
    {
        $this->authorize('ai-config.update');

        $credential = LlmCredential::findOrFail($credentialId);

        $tenant = auth()->user()->currentTenant;
        if ($tenant->default_llm_credential_id === $credential->id) {
            $tenant->update([
                'default_llm_credential_id' => null,
                'default_ai_model' => null,
            ]);
            $this->selectedCredentialId = null;
            $this->selectedModel = null;
        }

        $credential->delete();

        session()->flash('message', __('Credential deleted.'));
    }

    public function setDefaultCredential(string $credentialId): void
    {
        $this->authorize('ai-config.update');

        LlmCredential::findOrFail($credentialId);

        $tenant = auth()->user()->currentTenant;
        $tenant->update([
            'default_llm_credential_id' => $credentialId,
            'default_ai_model' => null,
        ]);
        $this->selectedCredentialId = $credentialId;
        $this->selectedModel = null;

        session()->flash('message', __('Default credential updated.'));
    }

    public function saveModelSettings(): void
    {
        $this->authorize('ai-config.update');

        $this->validate([
            'selectedCredentialId' => 'required|exists:llm_credentials,id',
            'selectedModel' => 'required|string',
            'aiTemperature' => 'required|numeric|min:0|max:2',
            'aiMaxTokens' => 'required|integer|min:100|max:8192',
            'aiContextWindow' => 'required|integer|min:1|max:100',
            'aiStreaming' => 'boolean',
        ]);

        $tenant = auth()->user()->currentTenant;

        $tenant->update([
            'default_llm_credential_id' => $this->selectedCredentialId,
            'default_ai_model' => $this->selectedModel,
            'ai_temperature' => $this->aiTemperature,
            'ai_max_tokens' => $this->aiMaxTokens,
            'ai_context_window' => $this->aiContextWindow,
            'ai_streaming' => $this->aiStreaming,
        ]);

        session()->flash('message', __('AI configuration saved.'));
    }

    /**
     * @return string[]
     */
    public function getAvailableModelsProperty(): array
    {
        if (! $this->selectedCredentialId) {
            return [];
        }

        $credential = LlmCredential::find($this->selectedCredentialId);

        return $credential ? $credential->provider->models() : [];
    }

    public function resetCredentialForm(): void
    {
        $this->editingCredentialId = null;
        $this->credentialName = '';
        $this->credentialProvider = '';
        $this->credentialApiKey = '';
        $this->showForm = false;
    }

    public function render(): View
    {
        $tenant = auth()->user()->currentTenant;
        $credentials = $tenant->llmCredentials()->latest()->get();

        return view('livewire.ai-config.ai-config-manager', [
            'credentials' => $credentials,
            'providers' => AiProvider::cases(),
        ]);
    }
}
