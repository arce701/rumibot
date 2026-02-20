<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">{{ __('Integrations') }}</flux:heading>

        @can('integrations.manage')
            <flux:button wire:click="$toggle('showForm')" variant="primary" icon="plus">
                {{ __('New Integration') }}
            </flux:button>
        @endcan
    </div>

    @if (session('message'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
            {{ session('message') }}
        </div>
    @endif

    @if ($showForm)
        <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">
                {{ $editingIntegrationId ? __('Edit Integration') : __('Create Integration') }}
            </flux:heading>

            <form wire:submit="{{ $editingIntegrationId ? 'update' : 'create' }}" class="space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <flux:input wire:model="name" :label="__('Name')" required />

                    <flux:select wire:model="provider" :label="__('Provider')">
                        @foreach ($integrationProviders as $p)
                            <flux:select.option :value="$p->value">{{ $p->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:input wire:model="url" :label="__('Webhook URL')" type="url" required />

                <div>
                    <flux:text class="mb-2 font-medium">{{ __('Events') }}</flux:text>
                    <div class="grid gap-2 md:grid-cols-2">
                        @foreach ($webhookEvents as $event)
                            <flux:checkbox
                                wire:model="selectedEvents"
                                :value="$event->value"
                                :label="$event->value"
                            />
                        @endforeach
                    </div>
                </div>

                <div class="flex gap-2">
                    <flux:button type="submit" variant="primary">
                        {{ $editingIntegrationId ? __('Update') : __('Create') }}
                    </flux:button>
                    <flux:button wire:click="resetForm" variant="ghost">{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    @endif

    @if ($integrations->isEmpty())
        <div class="rounded-xl border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text>{{ __('No integrations configured yet.') }}</flux:text>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Name') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Provider') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Events') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Last Triggered') }}</th>
                        <th class="px-6 py-3 text-end text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                    @foreach ($integrations as $integration)
                        <tr wire:key="integration-{{ $integration->id }}">
                            <td class="whitespace-nowrap px-6 py-4">
                                <div>
                                    <flux:text class="font-medium">
                                        {{ $integration->name }}
                                        @if ($integration->is_primary)
                                            <flux:badge color="amber" size="sm">{{ __('Primary') }}</flux:badge>
                                        @endif
                                    </flux:text>
                                    <flux:text size="sm" class="truncate text-zinc-500" style="max-width: 200px;">{{ $integration->url }}</flux:text>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:badge color="blue">{{ $integration->provider->label() }}</flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:badge :color="$integration->status === \App\Models\Enums\IntegrationStatus::Active ? 'green' : 'red'">
                                    {{ $integration->status->label() }}
                                </flux:badge>
                                @if ($integration->failure_count > 0)
                                    <flux:text size="sm" class="text-red-500">{{ $integration->failure_count }} {{ __('failures') }}</flux:text>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <flux:text size="sm">{{ count($integration->events ?? []) }} {{ __('events') }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text size="sm">{{ $integration->last_triggered_at?->diffForHumans() ?? '—' }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-end">
                                <div class="flex justify-end gap-2">
                                    @can('integrations.manage')
                                        <flux:button wire:click="edit({{ $integration->id }})" size="sm" variant="ghost" icon="pencil" />

                                        @if ($integration->status === \App\Models\Enums\IntegrationStatus::Active)
                                            <flux:button wire:click="suspend({{ $integration->id }})" size="sm" variant="ghost" icon="pause" />
                                        @else
                                            <flux:button wire:click="reactivate({{ $integration->id }})" size="sm" variant="ghost" icon="play" />
                                        @endif

                                        @unless ($integration->is_primary)
                                            <flux:button wire:click="markAsPrimary({{ $integration->id }})" size="sm" variant="ghost" icon="star" />
                                        @endunless

                                        <flux:button wire:click="deleteIntegration({{ $integration->id }})"
                                            wire:confirm="{{ __('Are you sure you want to delete this integration?') }}"
                                            size="sm" variant="ghost" icon="trash" class="text-red-600 hover:text-red-700 dark:text-red-400" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="mt-8">
        <livewire:integrations.api-token-manager />
    </div>
</div>
