<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Tenants') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Manage all platform tenants') }}</flux:text>
    </div>

    @if (session('message'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
            {{ session('message') }}
        </div>
    @endif

    <div class="mb-4 flex flex-col gap-4 sm:flex-row">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search tenants...')" />
        </div>

        <flux:select wire:model.live="activeFilter" class="w-full sm:w-48">
            <flux:select.option value="">{{ __('All') }}</flux:select.option>
            <flux:select.option value="1">{{ __('Active') }}</flux:select.option>
            <flux:select.option value="0">{{ __('Inactive') }}</flux:select.option>
        </flux:select>
    </div>

    @if ($tenants->isEmpty())
        <div class="rounded-xl border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text>{{ __('No tenants found.') }}</flux:text>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Name') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Slug') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Plan') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Users') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Channels') }}</th>
                        <th class="px-6 py-3 text-end text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                    @foreach ($tenants as $tenant)
                        <tr wire:key="tenant-{{ $tenant->id }}">
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text class="font-medium">{{ $tenant->name }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text size="sm" class="text-zinc-500">{{ $tenant->slug }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @php
                                    $activeSub = $tenant->subscriptions->first(fn ($s) => in_array($s->status->value, ['active', 'trialing', 'canceled']));
                                @endphp
                                <flux:text size="sm">{{ $activeSub?->plan?->name ?? '-' }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @if ($tenant->is_active)
                                    <flux:badge color="green">{{ __('Active') }}</flux:badge>
                                @else
                                    <flux:badge color="red">{{ __('Inactive') }}</flux:badge>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text>{{ $tenant->users_count }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text>{{ $tenant->channels_count }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-end">
                                <div class="flex items-center justify-end gap-1">
                                    <flux:button :href="route('platform.tenants.show', $tenant)" size="sm" variant="ghost" icon="eye" wire:navigate />
                                    <flux:button wire:click="toggleActive('{{ $tenant->id }}')" size="sm" variant="ghost" :icon="$tenant->is_active ? 'x-circle' : 'check-circle'" />
                                    <flux:button wire:click="deleteTenant('{{ $tenant->id }}')" wire:confirm="{{ __('Are you sure you want to delete this tenant?') }}" size="sm" variant="ghost" icon="trash" class="text-red-500 hover:text-red-700" />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $tenants->links() }}
        </div>
    @endif
</div>
