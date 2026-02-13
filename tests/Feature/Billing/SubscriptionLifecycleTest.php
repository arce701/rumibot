<?php

use App\Models\Enums\PaymentProviderType;
use App\Models\Enums\PaymentStatus;
use App\Models\Enums\SubscriptionStatus;
use App\Models\PaymentHistory;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\Billing\SubscriptionManager;
use App\Services\Tenant\TenantContext;

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    $this->tenant = Tenant::factory()->create();
    app(TenantContext::class)->set($this->tenant);

    $this->plan = Plan::factory()->create(['name' => 'Test Plan']);
    $this->planPrice = PlanPrice::factory()->create([
        'plan_id' => $this->plan->id,
        'price_amount' => 15000,
    ]);

    $this->manager = app(SubscriptionManager::class);
});

test('creating subscription saves correct data', function () {
    $subscription = $this->manager->createSubscription(
        $this->tenant,
        $this->plan,
        $this->planPrice,
        PaymentProviderType::Manual,
    );

    expect($subscription)
        ->tenant_id->toBe($this->tenant->id)
        ->plan_id->toBe($this->plan->id)
        ->plan_price_id->toBe($this->planPrice->id)
        ->status->toBe(SubscriptionStatus::Active)
        ->payment_provider->toBe(PaymentProviderType::Manual);

    expect($subscription->current_period_starts_at)->not->toBeNull();
    expect($subscription->current_period_ends_at)->not->toBeNull();
});

test('creating subscription records payment history', function () {
    $subscription = $this->manager->createSubscription(
        $this->tenant,
        $this->plan,
        $this->planPrice,
        PaymentProviderType::Manual,
    );

    $payment = PaymentHistory::withoutGlobalScopes()
        ->where('subscription_id', $subscription->id)
        ->first();

    expect($payment)
        ->not->toBeNull()
        ->tenant_id->toBe($this->tenant->id)
        ->status->toBe(PaymentStatus::Completed)
        ->amount->toBe(15000)
        ->currency->toBe('PEN');
});

test('changing plan updates subscription', function () {
    $subscription = $this->manager->createSubscription(
        $this->tenant,
        $this->plan,
        $this->planPrice,
        PaymentProviderType::Manual,
    );

    $newPlan = Plan::factory()->create(['name' => 'New Plan']);
    $newPrice = PlanPrice::factory()->create([
        'plan_id' => $newPlan->id,
        'price_amount' => 45000,
    ]);

    $updated = $this->manager->changePlan($subscription, $newPlan, $newPrice);

    expect($updated->plan_id)->toBe($newPlan->id);
    expect($updated->plan_price_id)->toBe($newPrice->id);
});

test('canceling subscription sets canceled_at and grace period', function () {
    $subscription = $this->manager->createSubscription(
        $this->tenant,
        $this->plan,
        $this->planPrice,
        PaymentProviderType::Manual,
    );

    $this->manager->cancelSubscription($subscription);

    $subscription->refresh();

    expect($subscription->status)->toBe(SubscriptionStatus::Canceled);
    expect($subscription->canceled_at)->not->toBeNull();
    expect($subscription->grace_period_ends_at)->not->toBeNull();
});

test('renewing subscription resets period and status', function () {
    $subscription = $this->manager->createSubscription(
        $this->tenant,
        $this->plan,
        $this->planPrice,
        PaymentProviderType::Manual,
    );

    $this->manager->cancelSubscription($subscription);
    $subscription->refresh();

    $this->manager->renewSubscription($subscription);
    $subscription->refresh();

    expect($subscription->status)->toBe(SubscriptionStatus::Active);
    expect($subscription->canceled_at)->toBeNull();
    expect($subscription->grace_period_ends_at)->toBeNull();
});

test('renewing subscription creates payment history', function () {
    $subscription = $this->manager->createSubscription(
        $this->tenant,
        $this->plan,
        $this->planPrice,
        PaymentProviderType::Manual,
    );

    $this->manager->renewSubscription($subscription);

    $payments = PaymentHistory::withoutGlobalScopes()
        ->where('subscription_id', $subscription->id)
        ->get();

    expect($payments)->toHaveCount(2);
});

test('factory states work correctly', function () {
    $trialing = Subscription::factory()->trialing()->create(['tenant_id' => $this->tenant->id]);
    expect($trialing->status)->toBe(SubscriptionStatus::Trialing);
    expect($trialing->isTrialing())->toBeTrue();

    $canceled = Subscription::factory()->canceled()->create(['tenant_id' => $this->tenant->id]);
    expect($canceled->status)->toBe(SubscriptionStatus::Canceled);
    expect($canceled->isCanceled())->toBeTrue();

    $expired = Subscription::factory()->expired()->create(['tenant_id' => $this->tenant->id]);
    expect($expired->status)->toBe(SubscriptionStatus::Expired);
});

test('isInGracePeriod returns correct value', function () {
    $subscription = Subscription::factory()->canceled()->create([
        'tenant_id' => $this->tenant->id,
        'grace_period_ends_at' => now()->addDays(3),
    ]);

    expect($subscription->isInGracePeriod())->toBeTrue();

    $expiredGrace = Subscription::factory()->canceled()->create([
        'tenant_id' => $this->tenant->id,
        'grace_period_ends_at' => now()->subDay(),
    ]);

    expect($expiredGrace->isInGracePeriod())->toBeFalse();
});
