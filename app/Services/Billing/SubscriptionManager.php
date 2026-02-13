<?php

namespace App\Services\Billing;

use App\Models\Enums\PaymentProviderType;
use App\Models\Enums\PaymentStatus;
use App\Models\Enums\SubscriptionStatus;
use App\Models\PaymentHistory;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\Billing\Contracts\PaymentProvider;

class SubscriptionManager
{
    public function createSubscription(
        Tenant $tenant,
        Plan $plan,
        PlanPrice $planPrice,
        PaymentProviderType $providerType,
    ): Subscription {
        $provider = $this->resolveProvider($providerType);
        $result = $provider->createSubscription($tenant, $planPrice);

        $subscription = Subscription::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'plan_price_id' => $planPrice->id,
            'status' => $result->success ? SubscriptionStatus::Active : SubscriptionStatus::PastDue,
            'payment_provider' => $providerType,
            'external_subscription_id' => $result->externalSubscriptionId,
            'external_customer_id' => $provider->createCustomer($tenant),
            'current_period_starts_at' => now(),
            'current_period_ends_at' => $this->calculatePeriodEnd($planPrice),
        ]);

        PaymentHistory::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'payment_provider' => $providerType,
            'status' => $result->success ? PaymentStatus::Completed : PaymentStatus::Pending,
            'amount' => $planPrice->price_amount,
            'currency' => $planPrice->currency,
            'description' => "Subscription to {$plan->name}",
        ]);

        return $subscription;
    }

    public function changePlan(Subscription $subscription, Plan $plan, PlanPrice $planPrice): Subscription
    {
        $provider = $this->resolveProvider($subscription->payment_provider);

        if ($subscription->external_subscription_id) {
            $provider->cancelSubscription($subscription->external_subscription_id);
        }

        $result = $provider->createSubscription($subscription->tenant, $planPrice);

        $subscription->update([
            'plan_id' => $plan->id,
            'plan_price_id' => $planPrice->id,
            'external_subscription_id' => $result->externalSubscriptionId,
            'current_period_starts_at' => now(),
            'current_period_ends_at' => $this->calculatePeriodEnd($planPrice),
        ]);

        return $subscription->fresh();
    }

    public function cancelSubscription(Subscription $subscription): void
    {
        $provider = $this->resolveProvider($subscription->payment_provider);

        if ($subscription->external_subscription_id) {
            $provider->cancelSubscription($subscription->external_subscription_id);
        }

        $gracePeriodDays = config('rumibot.billing.grace_period_days', 3);

        $subscription->update([
            'status' => SubscriptionStatus::Canceled,
            'canceled_at' => now(),
            'grace_period_ends_at' => now()->addDays($gracePeriodDays),
        ]);
    }

    public function renewSubscription(Subscription $subscription): void
    {
        $subscription->update([
            'status' => SubscriptionStatus::Active,
            'current_period_starts_at' => now(),
            'current_period_ends_at' => $this->calculatePeriodEnd($subscription->planPrice),
            'canceled_at' => null,
            'grace_period_ends_at' => null,
        ]);

        PaymentHistory::withoutGlobalScopes()->create([
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'payment_provider' => $subscription->payment_provider,
            'status' => PaymentStatus::Completed,
            'amount' => $subscription->planPrice->price_amount,
            'currency' => $subscription->planPrice->currency,
            'description' => "Renewal of {$subscription->plan->name}",
        ]);
    }

    private function resolveProvider(PaymentProviderType $type): PaymentProvider
    {
        return match ($type) {
            PaymentProviderType::MercadoPago => app(MercadoPagoProvider::class),
            PaymentProviderType::Manual => new ManualPaymentProvider,
            default => new ManualPaymentProvider,
        };
    }

    private function calculatePeriodEnd(PlanPrice $planPrice): \DateTimeInterface
    {
        return match ($planPrice->billing_interval) {
            \App\Models\Enums\BillingInterval::Quarterly => now()->addMonths(3),
            \App\Models\Enums\BillingInterval::SemiAnnual => now()->addMonths(6),
            \App\Models\Enums\BillingInterval::Annual => now()->addYear(),
        };
    }
}
