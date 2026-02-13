<?php

namespace App\Livewire\Channels;

use App\Models\Channel;
use App\Models\Enums\ChannelType;
use App\Models\Enums\WhatsAppProviderType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts::app')]
#[Title('Channels')]
class ChannelManager extends Component
{
    use AuthorizesRequests;

    #[Validate('required|string|max:100')]
    public string $name = '';

    #[Validate('required|string|max:100')]
    public string $slug = '';

    #[Validate('required|string')]
    public string $type = 'sales';

    #[Validate('required|string')]
    public string $providerType = 'ycloud';

    #[Validate('nullable|string|max:500')]
    public string $providerApiKey = '';

    #[Validate('nullable|string|max:100')]
    public string $providerPhoneNumberId = '';

    #[Validate('nullable|string|max:100')]
    public string $providerBusinessAccountId = '';

    #[Validate('nullable|string|max:100')]
    public string $providerWebhookVerifyToken = '';

    #[Validate('nullable|string')]
    public string $systemPromptOverride = '';

    #[Validate('nullable|string|max:100')]
    public string $aiModelOverride = '';

    #[Validate('nullable|numeric|min:0|max:2')]
    public ?float $aiTemperature = null;

    public bool $isActive = true;

    public bool $showForm = false;

    public ?string $editingChannelId = null;

    public function create(): void
    {
        $this->authorize('channels.create');
        $this->validate();

        $tenant = auth()->user()->currentTenant;

        Channel::create([
            'tenant_id' => $tenant->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type,
            'provider_type' => $this->providerType,
            'provider_api_key' => $this->providerApiKey ?: null,
            'provider_phone_number_id' => $this->providerPhoneNumberId ?: null,
            'provider_business_account_id' => $this->providerBusinessAccountId ?: null,
            'provider_webhook_verify_token' => $this->providerWebhookVerifyToken ?: null,
            'system_prompt_override' => $this->systemPromptOverride ?: null,
            'ai_model_override' => $this->aiModelOverride ?: null,
            'ai_temperature' => $this->aiTemperature,
            'is_active' => $this->isActive,
        ]);

        $this->resetForm();
        session()->flash('message', __('Channel created successfully.'));
    }

    public function edit(string $channelId): void
    {
        $this->authorize('channels.update');

        $channel = Channel::findOrFail($channelId);

        $this->editingChannelId = $channel->id;
        $this->name = $channel->name;
        $this->slug = $channel->slug;
        $this->type = $channel->type->value;
        $this->providerType = $channel->provider_type->value;
        $this->providerApiKey = '';
        $this->providerPhoneNumberId = $channel->provider_phone_number_id ?? '';
        $this->providerBusinessAccountId = $channel->provider_business_account_id ?? '';
        $this->providerWebhookVerifyToken = $channel->provider_webhook_verify_token ?? '';
        $this->systemPromptOverride = $channel->system_prompt_override ?? '';
        $this->aiModelOverride = $channel->ai_model_override ?? '';
        $this->aiTemperature = $channel->ai_temperature;
        $this->isActive = $channel->is_active;
        $this->showForm = true;
    }

    public function update(): void
    {
        $this->authorize('channels.update');
        $this->validate();

        $channel = Channel::findOrFail($this->editingChannelId);

        $data = [
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type,
            'provider_type' => $this->providerType,
            'provider_phone_number_id' => $this->providerPhoneNumberId ?: null,
            'provider_business_account_id' => $this->providerBusinessAccountId ?: null,
            'provider_webhook_verify_token' => $this->providerWebhookVerifyToken ?: null,
            'system_prompt_override' => $this->systemPromptOverride ?: null,
            'ai_model_override' => $this->aiModelOverride ?: null,
            'ai_temperature' => $this->aiTemperature,
            'is_active' => $this->isActive,
        ];

        if ($this->providerApiKey) {
            $data['provider_api_key'] = $this->providerApiKey;
        }

        $channel->update($data);

        $this->resetForm();
        session()->flash('message', __('Channel updated successfully.'));
    }

    public function toggleActive(string $channelId): void
    {
        $this->authorize('channels.update');

        $channel = Channel::findOrFail($channelId);
        $channel->update(['is_active' => ! $channel->is_active]);
    }

    public function deleteChannel(string $channelId): void
    {
        $this->authorize('channels.delete');

        $channel = Channel::findOrFail($channelId);
        $channel->delete();

        session()->flash('message', __('Channel deleted successfully.'));
    }

    public function resetForm(): void
    {
        $this->reset(['name', 'slug', 'type', 'providerType', 'providerApiKey', 'providerPhoneNumberId', 'providerBusinessAccountId', 'providerWebhookVerifyToken', 'systemPromptOverride', 'aiModelOverride', 'aiTemperature', 'isActive', 'showForm', 'editingChannelId']);
        $this->isActive = true;
    }

    public function render(): View
    {
        $this->authorize('channels.view');

        $tenant = auth()->user()->currentTenant;
        $channels = Channel::where('tenant_id', $tenant->id)
            ->withCount('conversations')
            ->latest()
            ->get();

        return view('livewire.channels.channel-manager', [
            'channels' => $channels,
            'channelTypes' => ChannelType::cases(),
            'providerTypes' => WhatsAppProviderType::cases(),
            'tenantId' => $tenant->id,
        ]);
    }
}
