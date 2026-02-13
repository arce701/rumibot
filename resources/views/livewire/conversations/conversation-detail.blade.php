<div>
    <div class="mb-6 flex items-center gap-4">
        <flux:button :href="route('conversations')" variant="ghost" icon="arrow-left" wire:navigate />
        <div>
            <flux:heading size="xl">{{ $conversation->contact_name ?? $conversation->contact_phone }}</flux:heading>
            <flux:text size="sm" class="text-zinc-500">{{ $conversation->contact_phone }}</flux:text>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="max-h-[600px] space-y-4 overflow-y-auto p-6">
                    @forelse ($messages as $message)
                        <div wire:key="msg-{{ $message->id }}" class="flex {{ $message->role === 'user' ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[75%] rounded-lg px-4 py-3 {{ $message->role === 'user' ? 'bg-blue-600 text-white' : 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100' }}">
                                <p class="whitespace-pre-wrap text-sm">{{ $message->content }}</p>
                                <flux:text size="xs" class="mt-1 {{ $message->role === 'user' ? 'text-blue-200' : 'text-zinc-400' }}">
                                    {{ $message->created_at?->format('M d, H:i') }}
                                </flux:text>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center">
                            <flux:text>{{ __('No messages yet.') }}</flux:text>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="base" class="mb-4">{{ __('Info') }}</flux:heading>
                <dl class="space-y-3">
                    <div>
                        <flux:text size="sm" class="text-zinc-500">{{ __('Channel') }}</flux:text>
                        <flux:text>{{ $channel?->name ?? '-' }}</flux:text>
                    </div>
                    <div>
                        <flux:text size="sm" class="text-zinc-500">{{ __('Status') }}</flux:text>
                        <div>
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
                        </div>
                    </div>
                    <div>
                        <flux:text size="sm" class="text-zinc-500">{{ __('Total Tokens') }}</flux:text>
                        <flux:text>{{ number_format($totalTokens) }}</flux:text>
                    </div>
                </dl>
            </div>

            @if ($lead)
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:heading size="base" class="mb-4">{{ __('Lead') }}</flux:heading>
                    <dl class="space-y-3">
                        <div>
                            <flux:text size="sm" class="text-zinc-500">{{ __('Name') }}</flux:text>
                            <flux:text>{{ $lead->full_name }}</flux:text>
                        </div>
                        @if ($lead->email)
                            <div>
                                <flux:text size="sm" class="text-zinc-500">{{ __('Email') }}</flux:text>
                                <flux:text>{{ $lead->email }}</flux:text>
                            </div>
                        @endif
                        <div>
                            <flux:text size="sm" class="text-zinc-500">{{ __('Status') }}</flux:text>
                            <flux:badge>{{ ucfirst($lead->status->value) }}</flux:badge>
                        </div>
                    </dl>
                </div>
            @endif

            @if ($escalation)
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:heading size="base" class="mb-4">{{ __('Escalation') }}</flux:heading>
                    <dl class="space-y-3">
                        <div>
                            <flux:text size="sm" class="text-zinc-500">{{ __('Reason') }}</flux:text>
                            <flux:text>{{ $escalation->reason }}</flux:text>
                        </div>
                        <div>
                            <flux:text size="sm" class="text-zinc-500">{{ __('Resolved') }}</flux:text>
                            <flux:badge :color="$escalation->isResolved() ? 'green' : 'yellow'">
                                {{ $escalation->isResolved() ? __('Yes') : __('No') }}
                            </flux:badge>
                        </div>
                    </dl>
                </div>
            @endif
        </div>
    </div>
</div>
