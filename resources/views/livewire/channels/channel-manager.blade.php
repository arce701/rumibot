<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">{{ __('Channels') }}</flux:heading>

        @can('channels.create')
            <flux:button wire:click="$toggle('showForm')" variant="primary" icon="plus">
                {{ __('New Channel') }}
            </flux:button>
        @endcan
    </div>

    @if (session('message'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
            {{ session('message') }}
        </div>
    @endif

    <div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-6 dark:border-blue-800 dark:bg-blue-900/20">
        <flux:heading size="lg" class="mb-2">{{ __('Meta Webhook Configuration') }}</flux:heading>
        <flux:text size="sm" class="mb-4 text-zinc-600 dark:text-zinc-400">
            {{ __('Configure this URL and verify token in your Meta App Dashboard under WhatsApp > Configuration.') }}
        </flux:text>
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <flux:text size="sm" class="mb-1 font-medium">{{ __('Webhook URL') }}</flux:text>
                <div class="flex items-center gap-2">
                    <code class="break-all rounded bg-white px-3 py-2 text-sm dark:bg-zinc-800">{{ $webhookUrl }}</code>
                </div>
            </div>
            <div>
                <flux:text size="sm" class="mb-1 font-medium">{{ __('Verify Token') }}</flux:text>
                <div class="flex items-center gap-2">
                    <code class="break-all rounded bg-white px-3 py-2 text-sm dark:bg-zinc-800">{{ $webhookVerifyToken }}</code>
                </div>
            </div>
        </div>
    </div>

    @if ($showForm)
        <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">
                {{ $editingChannelId ? __('Edit Channel') : __('Create Channel') }}
            </flux:heading>

            <form wire:submit="{{ $editingChannelId ? 'update' : 'create' }}" class="space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <flux:input wire:model="name" :label="__('Name')" :placeholder="__('e.g., Sales WhatsApp')" required />

                    <flux:select wire:model="type" :label="__('Type')">
                        @foreach ($channelTypes as $ct)
                            <flux:select.option :value="$ct->value">{{ $ct->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <flux:input wire:model="providerApiKey" :label="__('Access Token')" type="password"
                        :placeholder="$editingChannelId ? __('Leave blank to keep current') : ''" />

                    <flux:input wire:model="providerPhoneNumberId" :label="__('Phone Number ID')" />
                </div>

                <flux:switch wire:model="isActive" :label="__('Active')" />

                <div class="flex gap-2">
                    <flux:button type="submit" variant="primary">
                        {{ $editingChannelId ? __('Update') : __('Create') }}
                    </flux:button>
                    <flux:button wire:click="resetForm" variant="ghost">{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    @endif

    @if ($channels->isEmpty())
        <div class="rounded-xl border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text>{{ __('No channels configured yet.') }}</flux:text>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Name') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Type') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Phone Number ID') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                        <th class="px-6 py-3 text-end text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                    @foreach ($channels as $channel)
                        <tr wire:key="channel-{{ $channel->id }}">
                            <td class="whitespace-nowrap px-6 py-4">
                                <div>
                                    <flux:text class="font-medium">{{ $channel->name }}</flux:text>
                                    <flux:text size="sm" class="text-zinc-500">{{ $channel->conversations_count }} {{ __('conversations') }}</flux:text>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:badge :color="$channel->type->value === 'sales' ? 'blue' : 'green'">
                                    {{ $channel->type->label() }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text size="sm" class="font-mono text-zinc-500">{{ $channel->provider_phone_number_id }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:badge :color="$channel->is_active ? 'green' : 'zinc'">
                                    {{ $channel->is_active ? __('Active') : __('Inactive') }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-end">
                                <div class="flex justify-end gap-2">
                                    @can('channels.update')
                                        <flux:button wire:click="edit('{{ $channel->id }}')" size="sm" variant="ghost" icon="pencil" />
                                        <flux:button wire:click="toggleActive('{{ $channel->id }}')" size="sm" variant="ghost"
                                            :icon="$channel->is_active ? 'pause' : 'play'" />
                                    @endcan
                                    @can('channels.delete')
                                        <flux:button wire:click="deleteChannel('{{ $channel->id }}')"
                                            wire:confirm="{{ __('Are you sure you want to delete this channel?') }}"
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
</div>
