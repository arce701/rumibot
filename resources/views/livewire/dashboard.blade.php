<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Dashboard') }}</flux:heading>
        <flux:text class="mt-1">{{ $tenantName }}</flux:text>
    </div>

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text size="sm" class="text-zinc-500">{{ __('Active Conversations') }}</flux:text>
            <flux:heading size="xl" class="mt-2">{{ $activeConversations }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text size="sm" class="text-zinc-500">{{ __('Messages Today') }}</flux:text>
            <flux:heading size="xl" class="mt-2">{{ $messagesToday }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text size="sm" class="text-zinc-500">{{ __('New Leads (Week)') }}</flux:text>
            <flux:heading size="xl" class="mt-2">{{ $newLeadsThisWeek }}</flux:heading>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text size="sm" class="text-zinc-500">{{ __('Pending Escalations') }}</flux:text>
            <flux:heading size="xl" class="mt-2">{{ $pendingEscalations }}</flux:heading>
        </div>
    </div>
</div>
