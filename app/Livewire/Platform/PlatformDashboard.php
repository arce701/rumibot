<?php

namespace App\Livewire\Platform;

use App\Models\Enums\PaymentStatus;
use App\Models\Enums\SubscriptionStatus;
use App\Models\Message;
use App\Models\PaymentHistory;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::app')]
#[Title('Platform Dashboard')]
class PlatformDashboard extends Component
{
    public function render(): View
    {
        $activeTenants = Tenant::where('is_active', true)->count();

        $monthlyRevenue = PaymentHistory::withoutGlobalScopes()
            ->where('status', PaymentStatus::Completed)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $messagesToday = Message::withoutGlobalScopes()
            ->whereDate('created_at', today())
            ->count();

        $activeSubscriptions = Subscription::withoutGlobalScopes()
            ->where('status', SubscriptionStatus::Active)
            ->count();

        $trialingTenants = Subscription::withoutGlobalScopes()
            ->where('status', SubscriptionStatus::Trialing)
            ->count();

        $pastDueTenants = Subscription::withoutGlobalScopes()
            ->where('status', SubscriptionStatus::PastDue)
            ->count();

        return view('livewire.platform.platform-dashboard', [
            'activeTenants' => $activeTenants,
            'monthlyRevenue' => $monthlyRevenue,
            'messagesToday' => $messagesToday,
            'activeSubscriptions' => $activeSubscriptions,
            'trialingTenants' => $trialingTenants,
            'pastDueTenants' => $pastDueTenants,
        ]);
    }
}
