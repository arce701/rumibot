<?php

namespace App\Livewire\Channels;

use App\Http\Controllers\WhatsAppWebhookController;
use App\Models\Channel;
use App\Models\Enums\ChannelType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
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

    #[Validate('required|string')]
    public string $type = 'sales';

    #[Validate('nullable|string|max:500')]
    public string $providerApiKey = '';

    #[Validate('nullable|string|max:100')]
    public string $providerPhoneNumberId = '';

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
            'slug' => $this->generateUniqueSlug($this->name),
            'type' => $this->type,
            'provider_api_key' => $this->providerApiKey ?: null,
            'provider_phone_number_id' => $this->providerPhoneNumberId ?: null,
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
        $this->type = $channel->type->value;
        $this->providerApiKey = '';
        $this->providerPhoneNumberId = $channel->provider_phone_number_id ?? '';
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
            'type' => $this->type,
            'provider_phone_number_id' => $this->providerPhoneNumberId ?: null,
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
        $this->reset([
            'name', 'type', 'providerApiKey',
            'providerPhoneNumberId',
            'isActive', 'showForm', 'editingChannelId',
        ]);
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
            'tenantId' => $tenant->id,
            'webhookUrl' => url("/api/webhooks/whatsapp/{$tenant->id}"),
            'webhookVerifyToken' => WhatsAppWebhookController::generateVerifyToken($tenant->id),
        ]);
    }

    private function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (Channel::where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
