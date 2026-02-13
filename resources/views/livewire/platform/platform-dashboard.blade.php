<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Platform Dashboard') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Global platform metrics') }}</flux:text>
    </div>

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text size="sm" class="text-zinc-500">{{ __('Active Tenants') }}</flux:text>
            <flux:heading size="xl" class="mt-2">{{ $activeTenants }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text size="sm" class="text-zinc-500">{{ __('Monthly Revenue') }}</flux:text>
            <flux:heading size="xl" class="mt-2">{{ number_format($monthlyRevenue / 100, 2) }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text size="sm" class="text-zinc-500">{{ __('Messages Today') }}</flux:text>
            <flux:heading size="xl" class="mt-2">{{ $messagesToday }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text size="sm" class="text-zinc-500">{{ __('Active Subscriptions') }}</flux:text>
            <flux:heading size="xl" class="mt-2">{{ $activeSubscriptions }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text size="sm" class="text-zinc-500">{{ __('Trialing') }}</flux:text>
            <flux:heading size="xl" class="mt-2">{{ $trialingTenants }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text size="sm" class="text-zinc-500">{{ __('Past Due') }}</flux:text>
            <flux:heading size="xl" class="mt-2 {{ $pastDueTenants > 0 ? 'text-red-500' : '' }}">{{ $pastDueTenants }}</flux:heading>
        </div>
    </div>
</div>
