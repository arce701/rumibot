<?php

namespace App\Livewire\Integrations;

use App\Models\Enums\IntegrationProvider;
use App\Models\Enums\IntegrationStatus;
use App\Models\Enums\WebhookEvent;
use App\Models\TenantIntegration;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts::app')]
#[Title('Integrations')]
class IntegrationManager extends Component
{
    use AuthorizesRequests;

    #[Validate('required|string|max:100')]
    public string $name = '';

    #[Validate('required|string')]
    public string $provider = 'n8n';

    #[Validate('required|url|max:500')]
    public string $url = '';

    /** @var array<int, string> */
    public array $selectedEvents = [];

    public bool $showForm = false;

    public ?int $editingIntegrationId = null;

    public function create(): void
    {
        $this->authorize('integrations.manage');
        $this->validate();

        $tenant = auth()->user()->currentTenant;

        TenantIntegration::create([
            'tenant_id' => $tenant->id,
            'name' => $this->name,
            'provider' => $this->provider,
            'url' => $this->url,
            'secret' => Str::random(64),
            'events' => $this->selectedEvents,
            'status' => IntegrationStatus::Active,
        ]);

        $this->resetForm();
        session()->flash('message', __('Integration created successfully.'));
    }

    public function edit(int $id): void
    {
        $this->authorize('integrations.manage');

        $integration = TenantIntegration::findOrFail($id);

        $this->editingIntegrationId = $integration->id;
        $this->name = $integration->name;
        $this->provider = $integration->provider->value;
        $this->url = $integration->url;
        $this->selectedEvents = $integration->events ?? [];
        $this->showForm = true;
    }

    public function update(): void
    {
        $this->authorize('integrations.manage');
        $this->validate();

        $integration = TenantIntegration::findOrFail($this->editingIntegrationId);

        $integration->update([
            'name' => $this->name,
            'provider' => $this->provider,
            'url' => $this->url,
            'events' => $this->selectedEvents,
        ]);

        $this->resetForm();
        session()->flash('message', __('Integration updated successfully.'));
    }

    public function deleteIntegration(int $id): void
    {
        $this->authorize('integrations.manage');

        $integration = TenantIntegration::findOrFail($id);
        $integration->delete();

        session()->flash('message', __('Integration deleted successfully.'));
    }

    public function markAsPrimary(int $id): void
    {
        $this->authorize('integrations.manage');

        $tenant = auth()->user()->currentTenant;

        TenantIntegration::where('tenant_id', $tenant->id)
            ->where('is_primary', true)
            ->update(['is_primary' => false]);

        TenantIntegration::where('id', $id)->update(['is_primary' => true]);
    }

    public function suspend(int $id): void
    {
        $this->authorize('integrations.manage');

        TenantIntegration::where('id', $id)->update(['status' => IntegrationStatus::Suspended]);
    }

    public function reactivate(int $id): void
    {
        $this->authorize('integrations.manage');

        TenantIntegration::where('id', $id)->update([
            'status' => IntegrationStatus::Active,
            'failure_count' => 0,
        ]);
    }

    public function resetForm(): void
    {
        $this->reset(['name', 'provider', 'url', 'selectedEvents', 'showForm', 'editingIntegrationId']);
    }

    public function render(): View
    {
        $this->authorize('integrations.view');

        $tenant = auth()->user()->currentTenant;
        $integrations = TenantIntegration::where('tenant_id', $tenant->id)
            ->latest()
            ->get();

        return view('livewire.integrations.integration-manager', [
            'integrations' => $integrations,
            'webhookEvents' => WebhookEvent::cases(),
            'integrationProviders' => IntegrationProvider::cases(),
        ]);
    }
}
