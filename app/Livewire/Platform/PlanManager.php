<?php

namespace App\Livewire\Platform;

use App\Models\Enums\BillingInterval;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\PlanPrice;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts::app')]
#[Title('Plan Manager')]
class PlanManager extends Component
{
    public bool $showPlanModal = false;

    public ?int $editingPlanId = null;

    #[Validate('required|string|max:255')]
    public string $planName = '';

    #[Validate('required|string|max:255')]
    public string $planSlug = '';

    #[Validate('nullable|string|max:1000')]
    public string $planDescription = '';

    #[Validate('required|integer|min:0')]
    public int $planSortOrder = 0;

    public bool $planIsActive = true;

    /** @var array<int, array{billing_interval: string, amount: string}> */
    public array $planPrices = [];

    /** @var array<int, array{feature_slug: string, value: string}> */
    public array $planFeatures = [];

    public function createPlan(): void
    {
        $this->reset(['editingPlanId', 'planName', 'planSlug', 'planDescription', 'planSortOrder', 'planIsActive', 'planPrices', 'planFeatures']);
        $this->planIsActive = true;
        $this->showPlanModal = true;
    }

    public function editPlan(int $planId): void
    {
        $plan = Plan::with(['prices', 'features'])->findOrFail($planId);

        $this->editingPlanId = $plan->id;
        $this->planName = $plan->name;
        $this->planSlug = $plan->slug;
        $this->planDescription = $plan->description ?? '';
        $this->planSortOrder = $plan->sort_order;
        $this->planIsActive = $plan->is_active;

        $this->planPrices = $plan->prices->map(fn (PlanPrice $price) => [
            'billing_interval' => $price->billing_interval->value,
            'amount' => (string) $price->price_amount,
        ])->toArray();

        $this->planFeatures = $plan->features->map(fn (PlanFeature $feature) => [
            'feature_slug' => $feature->feature_slug,
            'value' => $feature->value,
        ])->toArray();

        $this->showPlanModal = true;
    }

    public function addPrice(): void
    {
        $this->planPrices[] = ['billing_interval' => BillingInterval::Quarterly->value, 'amount' => ''];
    }

    public function removePrice(int $index): void
    {
        unset($this->planPrices[$index]);
        $this->planPrices = array_values($this->planPrices);
    }

    public function addFeature(): void
    {
        $this->planFeatures[] = ['feature_slug' => '', 'value' => ''];
    }

    public function removeFeature(int $index): void
    {
        unset($this->planFeatures[$index]);
        $this->planFeatures = array_values($this->planFeatures);
    }

    public function savePlan(): void
    {
        $this->validate();

        $plan = Plan::updateOrCreate(
            ['id' => $this->editingPlanId],
            [
                'name' => $this->planName,
                'slug' => $this->planSlug,
                'description' => $this->planDescription ?: null,
                'sort_order' => $this->planSortOrder,
                'is_active' => $this->planIsActive,
            ],
        );

        $plan->prices()->delete();
        foreach ($this->planPrices as $price) {
            if (! empty($price['amount'])) {
                $plan->prices()->create([
                    'billing_interval' => $price['billing_interval'],
                    'currency' => 'PEN',
                    'price_amount' => (int) $price['amount'],
                ]);
            }
        }

        $plan->features()->delete();
        foreach ($this->planFeatures as $feature) {
            if (! empty($feature['feature_slug'])) {
                $plan->features()->create([
                    'feature_slug' => $feature['feature_slug'],
                    'value' => $feature['value'],
                ]);
            }
        }

        $this->showPlanModal = false;
        $this->reset(['editingPlanId', 'planName', 'planSlug', 'planDescription', 'planSortOrder', 'planIsActive', 'planPrices', 'planFeatures']);

        session()->flash('message', __('Plan saved successfully.'));
    }

    public function deletePlan(int $planId): void
    {
        Plan::findOrFail($planId)->delete();

        session()->flash('message', __('Plan deleted successfully.'));
    }

    public function render(): View
    {
        $plans = Plan::query()
            ->withCount('subscriptions')
            ->with(['prices', 'features'])
            ->orderBy('sort_order')
            ->get();

        return view('livewire.platform.plan-manager', [
            'plans' => $plans,
            'billingIntervals' => BillingInterval::cases(),
        ]);
    }
}
