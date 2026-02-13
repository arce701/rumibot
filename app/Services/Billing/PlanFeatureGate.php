<?php

namespace App\Services\Billing;

use App\Models\PlanFeature;
use App\Models\SubscriptionUsage;
use App\Models\Tenant;

class PlanFeatureGate
{
    public function canAccess(Tenant $tenant, string $featureSlug): bool
    {
        if ($tenant->is_platform_owner) {
            return true;
        }

        $subscription = $tenant->activeSubscription();

        if (! $subscription || ! $subscription->hasAccessToFeatures()) {
            return false;
        }

        return PlanFeature::query()
            ->where('plan_id', $subscription->plan_id)
            ->where('feature_slug', $featureSlug)
            ->exists();
    }

    public function getLimit(Tenant $tenant, string $featureSlug): int|string
    {
        if ($tenant->is_platform_owner) {
            return 'unlimited';
        }

        $subscription = $tenant->activeSubscription();

        if (! $subscription) {
            return 0;
        }

        $feature = PlanFeature::query()
            ->where('plan_id', $subscription->plan_id)
            ->where('feature_slug', $featureSlug)
            ->first();

        if (! $feature) {
            return 0;
        }

        return $feature->isUnlimited() ? 'unlimited' : $feature->numericValue();
    }

    public function getCurrentUsage(Tenant $tenant, string $featureSlug): int
    {
        $subscription = $tenant->activeSubscription();

        if (! $subscription) {
            return 0;
        }

        return (int) SubscriptionUsage::withoutGlobalScopes()
            ->where('subscription_id', $subscription->id)
            ->where('feature_slug', $featureSlug)
            ->where('period_starts_at', '<=', now())
            ->where('period_ends_at', '>=', now())
            ->value('used') ?? 0;
    }

    public function incrementUsage(Tenant $tenant, string $featureSlug, int $amount = 1): void
    {
        $subscription = $tenant->activeSubscription();

        if (! $subscription) {
            return;
        }

        $usage = SubscriptionUsage::withoutGlobalScopes()->firstOrCreate(
            [
                'subscription_id' => $subscription->id,
                'feature_slug' => $featureSlug,
                'period_starts_at' => $subscription->current_period_starts_at,
            ],
            [
                'tenant_id' => $tenant->id,
                'period_ends_at' => $subscription->current_period_ends_at,
                'used' => 0,
            ],
        );

        $usage->increment('used', $amount);
    }

    public function hasReachedLimit(Tenant $tenant, string $featureSlug): bool
    {
        $limit = $this->getLimit($tenant, $featureSlug);

        if ($limit === 'unlimited') {
            return false;
        }

        if ($limit === 0) {
            return true;
        }

        return $this->getCurrentUsage($tenant, $featureSlug) >= $limit;
    }
}
