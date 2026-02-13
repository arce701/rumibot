<?php

namespace App\Livewire\Billing;

use App\Models\Enums\BillingInterval;
use App\Models\Enums\PaymentProviderType;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Services\Billing\PlanFeatureGate;
use App\Services\Billing\SubscriptionManager as BillingSubscriptionManager;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::app')]
#[Title('Billing')]
class SubscriptionManager extends Component
{
    use AuthorizesRequests;

    public bool $showChangePlanModal = false;

    public bool $showCancelModal = false;

    public ?int $selectedPlanId = null;

    public string $selectedInterval = 'quarterly';

    public function changePlan(): void
    {
        $this->authorize('billing.manage');

        $tenant = auth()->user()->currentTenant;
        $plan = Plan::findOrFail($this->selectedPlanId);
        $planPrice = PlanPrice::where('plan_id', $plan->id)
            ->where('billing_interval', $this->selectedInterval)
            ->firstOrFail();

        $manager = app(BillingSubscriptionManager::class);
        $subscription = $tenant->activeSubscription();

        if ($subscription) {
            $manager->changePlan($subscription, $plan, $planPrice);
        } else {
            $manager->createSubscription(
                $tenant,
                $plan,
                $planPrice,
                PaymentProviderType::from(config('rumibot.billing.default_provider')),
            );
        }

        $this->showChangePlanModal = false;
        $this->selectedPlanId = null;
        session()->flash('message', __('Plan updated successfully.'));
    }

    public function cancelSubscription(): void
    {
        $this->authorize('billing.manage');

        $tenant = auth()->user()->currentTenant;
        $subscription = $tenant->activeSubscription();

        if ($subscription) {
            app(BillingSubscriptionManager::class)->cancelSubscription($subscription);
        }

        $this->showCancelModal = false;
        session()->flash('message', __('Subscription canceled. You have access until the grace period ends.'));
    }

    public function render(): View
    {
        $this->authorize('billing.view');

        $tenant = auth()->user()->currentTenant;
        $subscription = $tenant->activeSubscription();
        $gate = app(PlanFeatureGate::class);

        $usageMetrics = [];
        if ($subscription) {
            $featureSlugs = ['max_channels', 'max_messages', 'max_documents', 'max_team_members'];
            foreach ($featureSlugs as $slug) {
                $limit = $gate->getLimit($tenant, $slug);
                $usageMetrics[$slug] = [
                    'label' => __(str_replace('max_', '', $slug)),
                    'used' => $gate->getCurrentUsage($tenant, $slug),
                    'limit' => $limit,
                    'percentage' => $limit === 'unlimited' ? 0 : ($limit > 0 ? min(100, round(($gate->getCurrentUsage($tenant, $slug) / $limit) * 100)) : 0),
                ];
            }
        }

        return view('livewire.billing.subscription-manager', [
            'subscription' => $subscription,
            'plans' => Plan::where('is_active', true)->orderBy('sort_order')->with(['prices', 'features'])->get(),
            'usageMetrics' => $usageMetrics,
            'billingIntervals' => BillingInterval::cases(),
        ]);
    }
}
