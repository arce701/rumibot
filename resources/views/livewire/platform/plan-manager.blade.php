<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Plans') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Manage subscription plans, pricing and features') }}</flux:text>
        </div>
        <flux:button wire:click="createPlan" variant="primary" icon="plus">
            {{ __('New Plan') }}
        </flux:button>
    </div>

    @if (session('message'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
            {{ session('message') }}
        </div>
    @endif

    @if ($plans->isEmpty())
        <div class="rounded-xl border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text>{{ __('No plans created yet.') }}</flux:text>
        </div>
    @else
        <div class="grid gap-4">
            @foreach ($plans as $plan)
                <div wire:key="plan-{{ $plan->id }}" class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <flux:heading size="lg">{{ $plan->name }}</flux:heading>
                                @if ($plan->is_active)
                                    <flux:badge color="green" size="sm">{{ __('Active') }}</flux:badge>
                                @else
                                    <flux:badge color="red" size="sm">{{ __('Inactive') }}</flux:badge>
                                @endif
                            </div>
                            <flux:text size="sm" class="mt-1 text-zinc-500">{{ $plan->slug }} &middot; {{ __('Order') }}: {{ $plan->sort_order }} &middot; {{ $plan->subscriptions_count }} {{ __('subscriptions') }}</flux:text>
                            @if ($plan->description)
                                <flux:text class="mt-2">{{ $plan->description }}</flux:text>
                            @endif
                        </div>
                        <div class="flex items-center gap-1">
                            <flux:button wire:click="editPlan({{ $plan->id }})" size="sm" variant="ghost" icon="pencil" />
                            <flux:button wire:click="deletePlan({{ $plan->id }})" wire:confirm="{{ __('Are you sure you want to delete this plan?') }}" size="sm" variant="ghost" icon="trash" class="text-red-500 hover:text-red-700" />
                        </div>
                    </div>

                    @if ($plan->prices->isNotEmpty())
                        <div class="mt-4">
                            <flux:text size="sm" class="mb-2 font-medium text-zinc-500">{{ __('Prices') }}</flux:text>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($plan->prices as $price)
                                    <flux:badge>{{ $price->billing_interval->label() }}: {{ number_format($price->price_amount / 100, 2) }} {{ $price->currency }}</flux:badge>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($plan->features->isNotEmpty())
                        <div class="mt-4">
                            <flux:text size="sm" class="mb-2 font-medium text-zinc-500">{{ __('Features') }}</flux:text>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($plan->features as $feature)
                                    <flux:badge variant="outline">{{ $feature->feature_slug }}: {{ $feature->value }}</flux:badge>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <flux:modal wire:model="showPlanModal" class="max-w-2xl">
        <flux:heading size="lg">{{ $editingPlanId ? __('Edit Plan') : __('Create Plan') }}</flux:heading>

        <div class="mt-4 space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="planName" :label="__('Name')" required />
                <flux:input wire:model="planSlug" :label="__('Slug')" required />
            </div>

            <flux:textarea wire:model="planDescription" :label="__('Description')" rows="2" />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="planSortOrder" :label="__('Sort Order')" type="number" min="0" />
                <div class="flex items-end pb-1">
                    <flux:checkbox wire:model="planIsActive" :label="__('Active')" />
                </div>
            </div>

            <div>
                <div class="mb-2 flex items-center justify-between">
                    <flux:text class="font-medium">{{ __('Prices') }}</flux:text>
                    <flux:button wire:click="addPrice" size="sm" variant="ghost" icon="plus">{{ __('Add Price') }}</flux:button>
                </div>
                @foreach ($planPrices as $index => $price)
                    <div wire:key="price-{{ $index }}" class="mb-2 flex items-end gap-2">
                        <flux:select wire:model="planPrices.{{ $index }}.billing_interval" :label="$index === 0 ? __('Interval') : ''" class="flex-1">
                            @foreach ($billingIntervals as $interval)
                                <flux:select.option :value="$interval->value">{{ $interval->label() }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:input wire:model="planPrices.{{ $index }}.amount" :label="$index === 0 ? __('Amount (cents)') : ''" type="number" class="flex-1" />
                        <flux:button wire:click="removePrice({{ $index }})" size="sm" variant="ghost" icon="x-mark" class="mb-1" />
                    </div>
                @endforeach
            </div>

            <div>
                <div class="mb-2 flex items-center justify-between">
                    <flux:text class="font-medium">{{ __('Features') }}</flux:text>
                    <flux:button wire:click="addFeature" size="sm" variant="ghost" icon="plus">{{ __('Add Feature') }}</flux:button>
                </div>
                @foreach ($planFeatures as $index => $feature)
                    <div wire:key="feature-{{ $index }}" class="mb-2 flex items-end gap-2">
                        <flux:input wire:model="planFeatures.{{ $index }}.feature_slug" :label="$index === 0 ? __('Feature Slug') : ''" :placeholder="__('e.g. max_channels')" class="flex-1" />
                        <flux:input wire:model="planFeatures.{{ $index }}.value" :label="$index === 0 ? __('Value') : ''" :placeholder="__('e.g. 5 or unlimited')" class="flex-1" />
                        <flux:button wire:click="removeFeature({{ $index }})" size="sm" variant="ghost" icon="x-mark" class="mb-1" />
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <flux:button wire:click="$set('showPlanModal', false)" variant="ghost">{{ __('Cancel') }}</flux:button>
            <flux:button wire:click="savePlan" variant="primary">{{ __('Save') }}</flux:button>
        </div>
    </flux:modal>
</div>
