<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Conversations') }}</flux:heading>
    </div>

    @if (session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-4 flex flex-col gap-4 sm:flex-row">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search by phone or name...')" />
        </div>

        <flux:select wire:model.live="channelFilter" class="w-full sm:w-48">
            <flux:select.option value="">{{ __('All Channels') }}</flux:select.option>
            @foreach ($channels as $channel)
                <flux:select.option :value="$channel->id">{{ $channel->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="statusFilter" class="w-full sm:w-48">
            <flux:select.option value="">{{ __('All Statuses') }}</flux:select.option>
            @foreach ($statuses as $status)
                <flux:select.option :value="$status->value">{{ $status->label() }}</flux:select.option>
            @endforeach
        </flux:select>

        @feature('data-export')
            <flux:button wire:click="exportConversations" variant="ghost" icon="arrow-down-tray">{{ __('Export') }}</flux:button>
        @endfeature
    </div>

    @if ($conversations->isEmpty())
        <div class="rounded-xl border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text>{{ __('No conversations found.') }}</flux:text>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Contact') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Channel') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Msgs') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Tokens') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Last Activity') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                    @foreach ($conversations as $conversation)
                        <tr wire:key="convo-{{ $conversation->id }}" class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800"
                            onclick="window.location='{{ route('conversations.show', $conversation) }}'">
                            <td class="whitespace-nowrap px-6 py-4">
                                <div>
                                    <flux:text class="font-medium">{{ $conversation->contact_name ?? __('Unknown') }}</flux:text>
                                    <flux:text size="sm" class="text-zinc-500">{{ $conversation->contact_phone }}</flux:text>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text>{{ $conversation->channel?->name }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @switch($conversation->status->value)
                                    @case('active')
                                        <flux:badge color="green">{{ __('Active') }}</flux:badge>
                                        @break
                                    @case('closed')
                                        <flux:badge color="zinc">{{ __('Closed') }}</flux:badge>
                                        @break
                                    @case('escalated')
                                        <flux:badge color="red">{{ __('Escalated') }}</flux:badge>
                                        @break
                                @endswitch
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text>{{ $conversation->messages_count }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text size="sm">{{ number_format($conversation->total_input_tokens + $conversation->total_output_tokens) }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text size="sm">{{ $conversation->last_message_at?->diffForHumans() ?? '-' }}</flux:text>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $conversations->links() }}
        </div>
    @endif
</div>
