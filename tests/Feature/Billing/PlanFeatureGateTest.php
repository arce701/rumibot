<?php

use App\Models\Enums\PaymentProviderType;
use App\Models\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\SubscriptionUsage;
use App\Models\Tenant;
use App\Services\Billing\PlanFeatureGate;
use App\Services\Tenant\TenantContext;

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    $this->gate = app(PlanFeatureGate::class);

    $this->plan = Plan::factory()->create();
    $this->planPrice = PlanPrice::factory()->create(['plan_id' => $this->plan->id]);

    PlanFeature::factory()->create([
        'plan_id' => $this->plan->id,
        'feature_slug' => 'max_channels',
        'value' => '3',
    ]);
    PlanFeature::factory()->create([
        'plan_id' => $this->plan->id,
        'feature_slug' => 'max_messages',
        'value' => 'unlimited',
    ]);

    $this->tenant = Tenant::factory()->create();
    app(TenantContext::class)->set($this->tenant);
});

test('platform owner bypasses all checks', function () {
    $platformOwner = Tenant::factory()->platformOwner()->create();

    expect($this->gate->canAccess($platformOwner, 'max_channels'))->toBeTrue();
    expect($this->gate->canAccess($platformOwner, 'nonexistent_feature'))->toBeTrue();
    expect($this->gate->getLimit($platformOwner, 'max_channels'))->toBe('unlimited');
});

test('tenant without subscription cannot access features', function () {
    expect($this->gate->canAccess($this->tenant, 'max_channels'))->toBeFalse();
});

test('tenant with active subscription can access plan features', function () {
    Subscription::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id,
        'plan_id' => $this->plan->id,
        'plan_price_id' => $this->planPrice->id,
        'status' => SubscriptionStatus::Active,
        'payment_provider' => PaymentProviderType::Manual,
        'current_period_starts_at' => now(),
        'current_period_ends_at' => now()->addMonths(3),
    ]);

    expect($this->gate->canAccess($this->tenant, 'max_channels'))->toBeTrue();
});

test('undefined feature returns false', function () {
    Subscription::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id,
        'plan_id' => $this->plan->id,
        'plan_price_id' => $this->planPrice->id,
        'status' => SubscriptionStatus::Active,
        'payment_provider' => PaymentProviderType::Manual,
        'current_period_starts_at' => now(),
        'current_period_ends_at' => now()->addMonths(3),
    ]);

    expect($this->gate->canAccess($this->tenant, 'nonexistent_feature'))->toBeFalse();
});

test('getLimit returns numeric value', function () {
    Subscription::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id,
        'plan_id' => $this->plan->id,
        'plan_price_id' => $this->planPrice->id,
        'status' => SubscriptionStatus::Active,
        'payment_provider' => PaymentProviderType::Manual,
        'current_period_starts_at' => now(),
        'current_period_ends_at' => now()->addMonths(3),
    ]);

    expect($this->gate->getLimit($this->tenant, 'max_channels'))->toBe(3);
});

test('getLimit returns unlimited for unlimited features', function () {
    Subscription::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id,
        'plan_id' => $this->plan->id,
        'plan_price_id' => $this->planPrice->id,
        'status' => SubscriptionStatus::Active,
        'payment_provider' => PaymentProviderType::Manual,
        'current_period_starts_at' => now(),
        'current_period_ends_at' => now()->addMonths(3),
    ]);

    expect($this->gate->getLimit($this->tenant, 'max_messages'))->toBe('unlimited');
});

test('getLimit returns zero for missing feature', function () {
    Subscription::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id,
        'plan_id' => $this->plan->id,
        'plan_price_id' => $this->planPrice->id,
        'status' => SubscriptionStatus::Active,
        'payment_provider' => PaymentProviderType::Manual,
        'current_period_starts_at' => now(),
        'current_period_ends_at' => now()->addMonths(3),
    ]);

    expect($this->gate->getLimit($this->tenant, 'nonexistent'))->toBe(0);
});

test('incrementUsage tracks usage correctly', function () {
    $subscription = Subscription::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id,
        'plan_id' => $this->plan->id,
        'plan_price_id' => $this->planPrice->id,
        'status' => SubscriptionStatus::Active,
        'payment_provider' => PaymentProviderType::Manual,
        'current_period_starts_at' => now(),
        'current_period_ends_at' => now()->addMonths(3),
    ]);

    $this->gate->incrementUsage($this->tenant, 'max_channels');
    $this->gate->incrementUsage($this->tenant, 'max_channels');

    expect($this->gate->getCurrentUsage($this->tenant, 'max_channels'))->toBe(2);
});

test('hasReachedLimit returns true when limit reached', function () {
    $subscription = Subscription::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id,
        'plan_id' => $this->plan->id,
        'plan_price_id' => $this->planPrice->id,
        'status' => SubscriptionStatus::Active,
        'payment_provider' => PaymentProviderType::Manual,
        'current_period_starts_at' => now(),
        'current_period_ends_at' => now()->addMonths(3),
    ]);

    $this->gate->incrementUsage($this->tenant, 'max_channels', 3);

    expect($this->gate->hasReachedLimit($this->tenant, 'max_channels'))->toBeTrue();
});

test('hasReachedLimit returns false for unlimited features', function () {
    Subscription::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id,
        'plan_id' => $this->plan->id,
        'plan_price_id' => $this->planPrice->id,
        'status' => SubscriptionStatus::Active,
        'payment_provider' => PaymentProviderType::Manual,
        'current_period_starts_at' => now(),
        'current_period_ends_at' => now()->addMonths(3),
    ]);

    expect($this->gate->hasReachedLimit($this->tenant, 'max_messages'))->toBeFalse();
});

test('canceled subscription in grace period maintains access', function () {
    Subscription::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id,
        'plan_id' => $this->plan->id,
        'plan_price_id' => $this->planPrice->id,
        'status' => SubscriptionStatus::Canceled,
        'payment_provider' => PaymentProviderType::Manual,
        'current_period_starts_at' => now(),
        'current_period_ends_at' => now()->addMonths(3),
        'canceled_at' => now(),
        'grace_period_ends_at' => now()->addDays(3),
    ]);

    expect($this->gate->canAccess($this->tenant, 'max_channels'))->toBeTrue();
});

test('expired subscription denies access', function () {
    Subscription::withoutGlobalScopes()->create([
        'tenant_id' => $this->tenant->id,
        'plan_id' => $this->plan->id,
        'plan_price_id' => $this->planPrice->id,
        'status' => SubscriptionStatus::Expired,
        'payment_provider' => PaymentProviderType::Manual,
        'current_period_starts_at' => now()->subMonths(3),
        'current_period_ends_at' => now()->subDay(),
    ]);

    expect($this->gate->canAccess($this->tenant, 'max_channels'))->toBeFalse();
});
