<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">{{ __('Payment History') }}</flux:heading>

        <flux:button :href="route('billing')" variant="ghost" icon="arrow-left" wire:navigate>
            {{ __('Back to Billing') }}
        </flux:button>
    </div>

    <div class="mb-4">
        <flux:select wire:model.live="statusFilter" class="w-48">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach ($paymentStatuses as $status)
                <flux:select.option :value="$status->value">{{ ucfirst($status->value) }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    @if ($payments->isEmpty())
        <div class="rounded-xl border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text>{{ __('No payment records found.') }}</flux:text>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Date') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Description') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Amount') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Provider') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                    @foreach ($payments as $payment)
                        <tr wire:key="payment-{{ $payment->id }}">
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text size="sm">{{ $payment->created_at->format('d M Y H:i') }}</flux:text>
                            </td>
                            <td class="px-6 py-4">
                                <flux:text size="sm">{{ $payment->description ?? '—' }}</flux:text>
                                @if ($payment->subscription?->plan)
                                    <flux:text size="sm" class="text-zinc-500">{{ $payment->subscription->plan->name }}</flux:text>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text size="sm" class="font-medium">
                                    {{ $payment->currency }} {{ number_format($payment->amount / 100, 2) }}
                                </flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:badge :color="match($payment->status->value) {
                                    'completed' => 'green',
                                    'pending' => 'amber',
                                    'failed' => 'red',
                                    'refunded' => 'blue',
                                    default => 'zinc',
                                }">
                                    {{ ucfirst($payment->status->value) }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text size="sm">{{ ucfirst($payment->payment_provider->value) }}</flux:text>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $payments->links() }}
        </div>
    @endif
</div>
