<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Prompts') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Configure the system prompt for your AI assistant.') }}</flux:text>
    </div>

    @if (session('message'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
            {{ session('message') }}
        </div>
    @endif

    <div class="space-y-6">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Global System Prompt') }}</flux:heading>
            <flux:textarea wire:model="systemPrompt" rows="8" :placeholder="__('Enter the system prompt for your AI assistant...')" />
            @can('prompts.update')
                <flux:button wire:click="saveTenantPrompt" variant="primary" class="mt-4">
                    {{ __('Save') }}
                </flux:button>
            @endcan
        </div>

        @if ($channels->isNotEmpty())
            <flux:heading size="lg">{{ __('Channel-Specific Prompts') }}</flux:heading>

            @foreach ($channels as $channel)
                <div wire:key="prompt-{{ $channel->id }}" class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-4 flex items-center gap-2">
                        <flux:heading size="base">{{ $channel->name }}</flux:heading>
                        <flux:badge :color="$channel->type->value === 'sales' ? 'blue' : 'green'" size="sm">
                            {{ ucfirst($channel->type->value) }}
                        </flux:badge>
                    </div>

                    <flux:textarea wire:model="channelPrompts.{{ $channel->id }}" rows="5"
                        :placeholder="__('Override the system prompt for this channel (leave empty to use global)...')" />

                    @can('prompts.update')
                        <flux:button wire:click="saveChannelPrompt('{{ $channel->id }}')" variant="primary" class="mt-4" size="sm">
                            {{ __('Save') }}
                        </flux:button>
                    @endcan
                </div>
            @endforeach
        @endif
    </div>
</div>
