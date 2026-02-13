<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ $tenant->name }}</flux:heading>
            <flux:text class="mt-1">{{ $tenant->slug }}</flux:text>
        </div>
        <div class="flex items-center gap-2">
            <flux:button wire:click="switchToTenant" variant="primary" icon="arrow-right-start-on-rectangle">
                {{ __('Enter as Tenant') }}
            </flux:button>
            <flux:button wire:click="toggleActive" :variant="$tenant->is_active ? 'danger' : 'filled'">
                {{ $tenant->is_active ? __('Deactivate') : __('Activate') }}
            </flux:button>
            <flux:button :href="route('platform.tenants')" variant="ghost" icon="arrow-left" wire:navigate>
                {{ __('Back') }}
            </flux:button>
        </div>
    </div>

    @if (session('message'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
            {{ session('message') }}
        </div>
    @endif

    <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg" class="mb-4">{{ __('Tenant Information') }}</flux:heading>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <flux:text size="sm" class="text-zinc-500">{{ __('AI Provider') }}</flux:text>
                <flux:text class="mt-1">{{ $tenant->default_ai_provider ?? '-' }}</flux:text>
            </div>
            <div>
                <flux:text size="sm" class="text-zinc-500">{{ __('AI Model') }}</flux:text>
                <flux:text class="mt-1">{{ $tenant->default_ai_model ?? '-' }}</flux:text>
            </div>
            <div>
                <flux:text size="sm" class="text-zinc-500">{{ __('Timezone') }}</flux:text>
                <flux:text class="mt-1">{{ $tenant->timezone }}</flux:text>
            </div>
            <div>
                <flux:text size="sm" class="text-zinc-500">{{ __('Locale') }}</flux:text>
                <flux:text class="mt-1">{{ $tenant->locale }}</flux:text>
            </div>
            <div>
                <flux:text size="sm" class="text-zinc-500">{{ __('Status') }}</flux:text>
                <div class="mt-1">
                    @if ($tenant->is_active)
                        <flux:badge color="green">{{ __('Active') }}</flux:badge>
                    @else
                        <flux:badge color="red">{{ __('Inactive') }}</flux:badge>
                    @endif
                </div>
            </div>
            <div>
                <flux:text size="sm" class="text-zinc-500">{{ __('Created') }}</flux:text>
                <flux:text class="mt-1">{{ $tenant->created_at->format('M d, Y') }}</flux:text>
            </div>
        </div>
    </div>

    <div class="mb-6 grid gap-4 md:grid-cols-2 lg:grid-cols-5">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text size="sm" class="text-zinc-500">{{ __('Conversations') }}</flux:text>
            <flux:heading size="xl" class="mt-2">{{ $tenant->conversations_count }}</flux:heading>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text size="sm" class="text-zinc-500">{{ __('Total Messages') }}</flux:text>
            <flux:heading size="xl" class="mt-2">{{ $messagesCount }}</flux:heading>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text size="sm" class="text-zinc-500">{{ __('Leads') }}</flux:text>
            <flux:heading size="xl" class="mt-2">{{ $tenant->leads_count }}</flux:heading>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text size="sm" class="text-zinc-500">{{ __('Channels') }}</flux:text>
            <flux:heading size="xl" class="mt-2">{{ $tenant->channels_count }}</flux:heading>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text size="sm" class="text-zinc-500">{{ __('Users') }}</flux:text>
            <flux:heading size="xl" class="mt-2">{{ $tenant->users_count }}</flux:heading>
        </div>
    </div>

    @if ($activeSubscription)
        <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Active Subscription') }}</flux:heading>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <flux:text size="sm" class="text-zinc-500">{{ __('Plan') }}</flux:text>
                    <flux:text class="mt-1">{{ $activeSubscription->plan->name }}</flux:text>
                </div>
                <div>
                    <flux:text size="sm" class="text-zinc-500">{{ __('Status') }}</flux:text>
                    <div class="mt-1">
                        <flux:badge :color="$activeSubscription->status->value === 'active' ? 'green' : 'yellow'">
                            {{ ucfirst($activeSubscription->status->value) }}
                        </flux:badge>
                    </div>
                </div>
                <div>
                    <flux:text size="sm" class="text-zinc-500">{{ __('Period Start') }}</flux:text>
                    <flux:text class="mt-1">{{ $activeSubscription->current_period_starts_at?->format('M d, Y') ?? '-' }}</flux:text>
                </div>
                <div>
                    <flux:text size="sm" class="text-zinc-500">{{ __('Period End') }}</flux:text>
                    <flux:text class="mt-1">{{ $activeSubscription->current_period_ends_at?->format('M d, Y') ?? '-' }}</flux:text>
                </div>
            </div>
        </div>
    @endif

    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg" class="mb-4">{{ __('Users') }}</flux:heading>

        @if ($users->isEmpty())
            <flux:text>{{ __('No users found.') }}</flux:text>
        @else
            <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Name') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Email') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Role') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Joined') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                        @foreach ($users as $user)
                            <tr wire:key="user-{{ $user->id }}">
                                <td class="whitespace-nowrap px-6 py-4">
                                    <flux:text class="font-medium">{{ $user->name }}</flux:text>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <flux:text size="sm">{{ $user->email }}</flux:text>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <flux:badge>{{ $user->pivot->role }}</flux:badge>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <flux:text size="sm">{{ $user->pivot->created_at?->format('M d, Y') ?? '-' }}</flux:text>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
