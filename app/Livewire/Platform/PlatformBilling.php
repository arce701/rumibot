<?php

namespace App\Livewire\Platform;

use App\Models\Enums\PaymentStatus;
use App\Models\Enums\SubscriptionStatus;
use App\Models\PaymentHistory;
use App\Models\Subscription;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
#[Title('Platform Billing')]
class PlatformBilling extends Component
{
    use WithPagination;

    public function render(): View
    {
        $monthlyRevenue = PaymentHistory::withoutGlobalScopes()
            ->where('status', PaymentStatus::Completed)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $quarterlyRevenue = PaymentHistory::withoutGlobalScopes()
            ->where('status', PaymentStatus::Completed)
            ->where('created_at', '>=', now()->startOfQuarter())
            ->sum('amount');

        $yearlyRevenue = PaymentHistory::withoutGlobalScopes()
            ->where('status', PaymentStatus::Completed)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $activeSubscriptions = Subscription::withoutGlobalScopes()
            ->with(['tenant', 'plan', 'planPrice'])
            ->where('status', SubscriptionStatus::Active)
            ->latest()
            ->paginate(20);

        $recentPayments = PaymentHistory::withoutGlobalScopes()
            ->with(['subscription.plan', 'subscription.tenant'])
            ->latest()
            ->take(20)
            ->get();

        $pastDueSubscriptions = Subscription::withoutGlobalScopes()
            ->with(['tenant', 'plan'])
            ->where('status', SubscriptionStatus::PastDue)
            ->latest()
            ->get();

        return view('livewire.platform.platform-billing', [
            'monthlyRevenue' => $monthlyRevenue,
            'quarterlyRevenue' => $quarterlyRevenue,
            'yearlyRevenue' => $yearlyRevenue,
            'activeSubscriptions' => $activeSubscriptions,
            'recentPayments' => $recentPayments,
            'pastDueSubscriptions' => $pastDueSubscriptions,
        ]);
    }
}
