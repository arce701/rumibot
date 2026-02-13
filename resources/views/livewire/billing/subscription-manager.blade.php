<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">{{ __('Billing') }}</flux:heading>

        <div class="flex gap-2">
            <flux:button :href="route('billing.payments')" variant="ghost" icon="clock" wire:navigate>
                {{ __('Payment History') }}
            </flux:button>
        </div>
    </div>

    @if (session('message'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
            {{ session('message') }}
        </div>
    @endif

    {{-- Current Plan --}}
    <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg" class="mb-4">{{ __('Current Plan') }}</flux:heading>

        @if ($subscription)
            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="xl">{{ $subscription->plan->name }}</flux:heading>
                    <div class="mt-1 flex items-center gap-2">
                        <flux:badge :color="match($subscription->status->value) {
                            'active' => 'green',
                            'trialing' => 'blue',
                            'canceled' => 'amber',
                            'past_due' => 'red',
                            default => 'zinc',
                        }">
                            {{ ucfirst($subscription->status->value) }}
                        </flux:badge>
                        <flux:text size="sm">
                            {{ $subscription->planPrice->billing_interval->value }} &middot;
                            S/ {{ number_format($subscription->planPrice->price_amount / 100, 2) }}
                        </flux:text>
                    </div>
                    @if ($subscription->current_period_ends_at)
                        <flux:text size="sm" class="mt-2 text-zinc-500">
                            {{ __('Current period ends') }}: {{ $subscription->current_period_ends_at->format('d M Y') }}
                        </flux:text>
                    @endif
                    @if ($subscription->isInGracePeriod())
                        <flux:text size="sm" class="mt-1 text-amber-600 dark:text-amber-400">
                            {{ __('Grace period ends') }}: {{ $subscription->grace_period_ends_at->format('d M Y') }}
                        </flux:text>
                    @endif
                </div>

                @can('billing.manage')
                    <div class="flex gap-2">
                        <flux:button wire:click="$set('showChangePlanModal', true)" variant="primary" size="sm">
                            {{ __('Change Plan') }}
                        </flux:button>
                        @if ($subscription->isActive() || $subscription->isTrialing())
                            <flux:button wire:click="$set('showCancelModal', true)" variant="ghost" size="sm" class="text-red-600 hover:text-red-700 dark:text-red-400">
                                {{ __('Cancel') }}
                            </flux:button>
                        @endif
                    </div>
                @endcan
            </div>
        @else
            <div class="text-center py-4">
                <flux:text>{{ __('No active subscription.') }}</flux:text>
                @can('billing.manage')
                    <flux:button wire:click="$set('showChangePlanModal', true)" variant="primary" class="mt-3">
                        {{ __('Choose a Plan') }}
                    </flux:button>
                @endcan
            </div>
        @endif
    </div>

    {{-- Usage Metrics --}}
    @if ($subscription && count($usageMetrics) > 0)
        <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Usage') }}</flux:heading>

            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($usageMetrics as $slug => $metric)
                    <div>
                        <div class="mb-1 flex items-center justify-between">
                            <flux:text size="sm" class="font-medium capitalize">{{ $metric['label'] }}</flux:text>
                            <flux:text size="sm" class="text-zinc-500">
                                {{ $metric['used'] }} / {{ $metric['limit'] === 'unlimited' ? __('Unlimited') : $metric['limit'] }}
                            </flux:text>
                        </div>
                        @if ($metric['limit'] !== 'unlimited')
                            <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                <div class="h-full rounded-full transition-all {{ $metric['percentage'] >= 90 ? 'bg-red-500' : ($metric['percentage'] >= 70 ? 'bg-amber-500' : 'bg-blue-500') }}"
                                     style="width: {{ $metric['percentage'] }}%"></div>
                            </div>
                        @else
                            <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                <div class="h-full w-full rounded-full bg-green-500"></div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Available Plans --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg" class="mb-4">{{ __('Available Plans') }}</flux:heading>

        <div class="grid gap-4 md:grid-cols-3">
            @foreach ($plans as $plan)
                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700 {{ $subscription && $subscription->plan_id === $plan->id ? 'ring-2 ring-blue-500' : '' }}">
                    <flux:heading size="lg">{{ $plan->name }}</flux:heading>
                    @if ($plan->description)
                        <flux:text size="sm" class="mt-1 text-zinc-500">{{ $plan->description }}</flux:text>
                    @endif

                    <div class="mt-3 space-y-1">
                        @foreach ($plan->prices as $price)
                            <flux:text size="sm">
                                <span class="font-semibold">S/ {{ number_format($price->price_amount / 100, 2) }}</span>
                                / {{ $price->billing_interval->value }}
                            </flux:text>
                        @endforeach
                    </div>

                    <div class="mt-3 space-y-1">
                        @foreach ($plan->features as $feature)
                            <flux:text size="sm">
                                {{ $feature->isUnlimited() ? __('Unlimited') : $feature->value }}
                                {{ str_replace('_', ' ', str_replace('max_', '', $feature->feature_slug)) }}
                            </flux:text>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Change Plan Modal --}}
    <flux:modal wire:model="showChangePlanModal">
        <flux:heading size="lg">{{ __('Change Plan') }}</flux:heading>

        <div class="mt-4 space-y-4">
            <flux:select wire:model="selectedPlanId" :label="__('Plan')">
                <flux:select.option value="">{{ __('Select a plan') }}</flux:select.option>
                @foreach ($plans as $plan)
                    <flux:select.option :value="$plan->id">{{ $plan->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="selectedInterval" :label="__('Billing Interval')">
                @foreach ($billingIntervals as $interval)
                    <flux:select.option :value="$interval->value">{{ ucfirst($interval->value) }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex justify-end gap-2">
                <flux:button wire:click="$set('showChangePlanModal', false)" variant="ghost">{{ __('Cancel') }}</flux:button>
                <flux:button wire:click="changePlan" variant="primary">{{ __('Confirm') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Cancel Subscription Modal --}}
    <flux:modal wire:model="showCancelModal">
        <flux:heading size="lg">{{ __('Cancel Subscription') }}</flux:heading>

        <flux:text class="mt-2">
            {{ __('Are you sure you want to cancel your subscription? You will have access until the grace period ends.') }}
        </flux:text>

        <div class="mt-4 flex justify-end gap-2">
            <flux:button wire:click="$set('showCancelModal', false)" variant="ghost">{{ __('Keep Subscription') }}</flux:button>
            <flux:button wire:click="cancelSubscription" variant="danger">{{ __('Cancel Subscription') }}</flux:button>
        </div>
    </flux:modal>
</div>
