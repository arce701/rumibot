<?php

namespace App\Livewire\Billing;

use App\Models\Enums\PaymentStatus;
use App\Models\PaymentHistory as PaymentHistoryModel;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
#[Title('Payment History')]
class PaymentHistory extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $statusFilter = '';

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $this->authorize('billing.view');

        $tenant = auth()->user()->currentTenant;

        $query = PaymentHistoryModel::where('tenant_id', $tenant->id)
            ->with('subscription.plan')
            ->latest();

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        return view('livewire.billing.payment-history', [
            'payments' => $query->paginate(15),
            'paymentStatuses' => PaymentStatus::cases(),
        ]);
    }
}
