<?php

namespace App\Livewire\Platform;

use App\Models\Tenant;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
#[Title('Tenants')]
class TenantIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $activeFilter = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedActiveFilter(): void
    {
        $this->resetPage();
    }

    public function toggleActive(string $tenantId): void
    {
        $tenant = Tenant::findOrFail($tenantId);
        $tenant->update(['is_active' => ! $tenant->is_active]);

        session()->flash('message', $tenant->is_active
            ? __('Tenant activated successfully.')
            : __('Tenant deactivated successfully.'));
    }

    public function deleteTenant(string $tenantId): void
    {
        $tenant = Tenant::findOrFail($tenantId);
        $tenant->delete();

        session()->flash('message', __('Tenant deleted successfully.'));
    }

    public function render(): View
    {
        $tenants = Tenant::query()
            ->withCount(['users', 'channels'])
            ->with('subscriptions.plan')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('slug', 'like', "%{$this->search}%");
                });
            })
            ->when($this->activeFilter !== '', function ($query) {
                $query->where('is_active', $this->activeFilter === '1');
            })
            ->latest()
            ->paginate(20);

        return view('livewire.platform.tenant-index', [
            'tenants' => $tenants,
        ]);
    }
}
