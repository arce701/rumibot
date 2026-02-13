<?php

use App\Models\Enums\BillingInterval;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\PlanPrice;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->superAdmin = User::factory()->superAdmin()->create(['current_tenant_id' => $this->tenant->id]);
    $this->tenant->users()->attach($this->superAdmin->id, ['role' => 'tenant_owner', 'is_default' => true]);
});

test('super-admin can access plan manager', function () {
    $this->actingAs($this->superAdmin)
        ->get(route('platform.plans'))
        ->assertOk();
});

test('super-admin can create a plan with prices and features', function () {
    Livewire::actingAs($this->superAdmin)
        ->test(\App\Livewire\Platform\PlanManager::class)
        ->call('createPlan')
        ->set('planName', 'Pro Plan')
        ->set('planSlug', 'pro-plan')
        ->set('planDescription', 'The best plan')
        ->set('planSortOrder', 1)
        ->set('planIsActive', true)
        ->set('planPrices', [
            ['billing_interval' => BillingInterval::Quarterly->value, 'amount' => '15000'],
            ['billing_interval' => BillingInterval::Annual->value, 'amount' => '45000'],
        ])
        ->set('planFeatures', [
            ['feature_slug' => 'max_channels', 'value' => '5'],
            ['feature_slug' => 'max_messages', 'value' => 'unlimited'],
        ])
        ->call('savePlan');

    $plan = Plan::where('slug', 'pro-plan')->first();
    expect($plan)->not->toBeNull()
        ->and($plan->name)->toBe('Pro Plan')
        ->and($plan->prices)->toHaveCount(2)
        ->and($plan->features)->toHaveCount(2);
});

test('super-admin can edit a plan', function () {
    $plan = Plan::factory()->create(['name' => 'Starter']);
    PlanPrice::factory()->create([
        'plan_id' => $plan->id,
        'billing_interval' => BillingInterval::Quarterly,
        'price_amount' => 10000,
    ]);

    Livewire::actingAs($this->superAdmin)
        ->test(\App\Livewire\Platform\PlanManager::class)
        ->call('editPlan', $plan->id)
        ->set('planName', 'Starter Updated')
        ->call('savePlan');

    $plan->refresh();
    expect($plan->name)->toBe('Starter Updated');
});

test('super-admin can deactivate a plan', function () {
    $plan = Plan::factory()->create(['is_active' => true]);

    Livewire::actingAs($this->superAdmin)
        ->test(\App\Livewire\Platform\PlanManager::class)
        ->call('editPlan', $plan->id)
        ->set('planIsActive', false)
        ->call('savePlan');

    $plan->refresh();
    expect($plan->is_active)->toBeFalse();
});

test('super-admin can soft delete a plan', function () {
    $plan = Plan::factory()->create();

    Livewire::actingAs($this->superAdmin)
        ->test(\App\Livewire\Platform\PlanManager::class)
        ->call('deletePlan', $plan->id);

    expect(Plan::find($plan->id))->toBeNull();
    expect(Plan::withTrashed()->find($plan->id))->not->toBeNull();
});

test('plan manager can add and remove prices', function () {
    Livewire::actingAs($this->superAdmin)
        ->test(\App\Livewire\Platform\PlanManager::class)
        ->call('createPlan')
        ->call('addPrice')
        ->assertSet('planPrices', [['billing_interval' => BillingInterval::Quarterly->value, 'amount' => '']])
        ->call('addPrice')
        ->assertCount('planPrices', 2)
        ->call('removePrice', 0)
        ->assertCount('planPrices', 1);
});

test('plan manager can add and remove features', function () {
    Livewire::actingAs($this->superAdmin)
        ->test(\App\Livewire\Platform\PlanManager::class)
        ->call('createPlan')
        ->call('addFeature')
        ->assertSet('planFeatures', [['feature_slug' => '', 'value' => '']])
        ->call('addFeature')
        ->assertCount('planFeatures', 2)
        ->call('removeFeature', 0)
        ->assertCount('planFeatures', 1);
});
