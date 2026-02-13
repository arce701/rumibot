<?php

namespace App\Livewire\Platform;

use App\Models\Tenant;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::app')]
#[Title('Tenant Detail')]
class TenantDetail extends Component
{
    public Tenant $tenant;

    public function mount(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function switchToTenant(): void
    {
        auth()->user()->switchToTenant($this->tenant);

        $this->redirect(route('dashboard'), navigate: true);
    }

    public function toggleActive(): void
    {
        $this->tenant->update(['is_active' => ! $this->tenant->is_active]);

        session()->flash('message', $this->tenant->is_active
            ? __('Tenant activated successfully.')
            : __('Tenant deactivated successfully.'));
    }

    public function render(): View
    {
        $this->tenant->loadCount(['conversations', 'leads', 'channels', 'users']);

        $messagesCount = (int) $this->tenant->conversations()
            ->withoutGlobalScopes()
            ->withCount('messages')
            ->get()
            ->sum('messages_count');

        $activeSubscription = $this->tenant->activeSubscription();
        $activeSubscription?->load('plan', 'planPrice');

        $users = $this->tenant->users()->withPivot('role', 'created_at')->get();

        return view('livewire.platform.tenant-detail', [
            'messagesCount' => $messagesCount,
            'activeSubscription' => $activeSubscription,
            'users' => $users,
        ]);
    }
}
