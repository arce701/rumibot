<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Platform Billing') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Revenue overview and subscription management') }}</flux:text>
    </div>

    <div class="mb-6 grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text size="sm" class="text-zinc-500">{{ __('Monthly Revenue') }}</flux:text>
            <flux:heading size="xl" class="mt-2">{{ number_format($monthlyRevenue / 100, 2) }}</flux:heading>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text size="sm" class="text-zinc-500">{{ __('Quarterly Revenue') }}</flux:text>
            <flux:heading size="xl" class="mt-2">{{ number_format($quarterlyRevenue / 100, 2) }}</flux:heading>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text size="sm" class="text-zinc-500">{{ __('Yearly Revenue') }}</flux:text>
            <flux:heading size="xl" class="mt-2">{{ number_format($yearlyRevenue / 100, 2) }}</flux:heading>
        </div>
    </div>

    @if ($pastDueSubscriptions->isNotEmpty())
        <div class="mb-6">
            <flux:heading size="lg" class="mb-4">{{ __('Past Due Tenants') }}</flux:heading>
            <div class="overflow-hidden rounded-xl border border-red-200 dark:border-red-800">
                <table class="min-w-full divide-y divide-red-200 dark:divide-red-800">
                    <thead class="bg-red-50 dark:bg-red-900/20">
                        <tr>
                            <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-red-500">{{ __('Tenant') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-red-500">{{ __('Plan') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-red-500">{{ __('Since') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-red-200 bg-white dark:divide-red-800 dark:bg-zinc-900">
                        @foreach ($pastDueSubscriptions as $subscription)
                            <tr wire:key="past-due-{{ $subscription->id }}">
                                <td class="whitespace-nowrap px-6 py-4">
                                    <flux:text class="font-medium">{{ $subscription->tenant?->name ?? '-' }}</flux:text>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <flux:text>{{ $subscription->plan?->name ?? '-' }}</flux:text>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <flux:text size="sm">{{ $subscription->current_period_ends_at?->format('M d, Y') ?? '-' }}</flux:text>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="mb-6">
        <flux:heading size="lg" class="mb-4">{{ __('Active Subscriptions') }}</flux:heading>
        @if ($activeSubscriptions->isEmpty())
            <div class="rounded-xl border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text>{{ __('No active subscriptions.') }}</flux:text>
            </div>
        @else
            <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Tenant') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Plan') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Amount') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Period End') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                        @foreach ($activeSubscriptions as $subscription)
                            <tr wire:key="sub-{{ $subscription->id }}">
                                <td class="whitespace-nowrap px-6 py-4">
                                    <flux:text class="font-medium">{{ $subscription->tenant?->name ?? '-' }}</flux:text>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <flux:text>{{ $subscription->plan?->name ?? '-' }}</flux:text>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <flux:badge color="green">{{ $subscription->status->label() }}</flux:badge>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <flux:text>{{ $subscription->planPrice ? number_format($subscription->planPrice->price_amount / 100, 2) . ' ' . $subscription->planPrice->currency : '-' }}</flux:text>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <flux:text size="sm">{{ $subscription->current_period_ends_at?->format('M d, Y') ?? '-' }}</flux:text>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $activeSubscriptions->links() }}
            </div>
        @endif
    </div>

    <div>
        <flux:heading size="lg" class="mb-4">{{ __('Recent Payments') }}</flux:heading>
        @if ($recentPayments->isEmpty())
            <div class="rounded-xl border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text>{{ __('No payments recorded.') }}</flux:text>
            </div>
        @else
            <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Tenant') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Plan') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Amount') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Date') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                        @foreach ($recentPayments as $payment)
                            <tr wire:key="payment-{{ $payment->id }}">
                                <td class="whitespace-nowrap px-6 py-4">
                                    <flux:text class="font-medium">{{ $payment->subscription?->tenant?->name ?? '-' }}</flux:text>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <flux:text>{{ $payment->subscription?->plan?->name ?? '-' }}</flux:text>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <flux:text>{{ number_format($payment->amount / 100, 2) }} {{ $payment->currency }}</flux:text>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    @switch($payment->status->value)
                                        @case('completed')
                                            <flux:badge color="green">{{ __('Completed') }}</flux:badge>
                                            @break
                                        @case('pending')
                                            <flux:badge color="yellow">{{ __('Pending') }}</flux:badge>
                                            @break
                                        @case('failed')
                                            <flux:badge color="red">{{ __('Failed') }}</flux:badge>
                                            @break
                                        @case('refunded')
                                            <flux:badge color="zinc">{{ __('Refunded') }}</flux:badge>
                                            @break
                                    @endswitch
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <flux:text size="sm">{{ $payment->created_at->format('M d, Y H:i') }}</flux:text>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
