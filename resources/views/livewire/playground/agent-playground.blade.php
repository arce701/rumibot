<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Agent Playground') }}</flux:heading>
        <flux:text class="mt-1 text-zinc-500">{{ __('Test your AI agent with the configured prompts and knowledge base.') }}</flux:text>
    </div>

    {{-- Top bar: Channel selector + info badges + clear button --}}
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <flux:select wire:model.live="selectedChannelId" class="w-64">
            @foreach ($channels as $channel)
                <flux:select.option :value="$channel->id">{{ $channel->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:badge>1 {{ __('tool') }}</flux:badge>
        <flux:badge>{{ $this->documentCount }} {{ __('docs') }}</flux:badge>

        <div class="ml-auto">
            <flux:button wire:click="clearChat" variant="ghost" icon="trash" size="sm">
                {{ __('Clear') }}
            </flux:button>
        </div>
    </div>

    {{-- Collapsible sections --}}
    <div class="mb-4 space-y-2" x-data="{ showTools: false, showDocs: false }">
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700">
            <button @click="showTools = !showTools" class="flex w-full items-center justify-between px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300">
                <span>{{ __('Tools') }} (1)</span>
                <flux:icon.chevron-down ::class="showTools && 'rotate-180'" class="size-4 transition-transform" />
            </button>
            <div x-show="showTools" x-collapse class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                <div class="flex items-center gap-2">
                    <flux:badge color="blue">SimilaritySearch</flux:badge>
                    <flux:text size="sm" class="text-zinc-500">{{ __('Search the knowledge base for relevant information.') }}</flux:text>
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700">
            <button @click="showDocs = !showDocs" class="flex w-full items-center justify-between px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300">
                <span>{{ __('Documents') }} ({{ $this->documentCount }})</span>
                <flux:icon.chevron-down ::class="showDocs && 'rotate-180'" class="size-4 transition-transform" />
            </button>
            <div x-show="showDocs" x-collapse class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                @if ($this->documents->isEmpty())
                    <flux:text size="sm" class="text-zinc-500">{{ __('No documents available for this channel.') }}</flux:text>
                @else
                    <div class="space-y-1">
                        @foreach ($this->documents as $doc)
                            <div class="flex items-center gap-2">
                                <flux:badge color="green" size="sm">{{ __('Ready') }}</flux:badge>
                                <flux:text size="sm">{{ $doc->title }}</flux:text>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Chat area --}}
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900"
         x-data="{
            scrollToBottom() {
                this.$nextTick(() => {
                    const el = this.$refs.chatContainer;
                    if (el) el.scrollTop = el.scrollHeight;
                });
            }
         }"
         x-init="scrollToBottom()"
    >
        <div x-ref="chatContainer" class="max-h-[500px] space-y-4 overflow-y-auto p-6">
            @forelse ($chatMessages as $index => $message)
                <div wire:key="msg-{{ $index }}" class="flex {{ $message['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[75%] rounded-lg px-4 py-3 {{ $message['role'] === 'user' ? 'bg-blue-600 text-white' : 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100' }}">
                        <p class="whitespace-pre-wrap text-sm">{{ $message['content'] }}</p>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center py-16 text-center">
                    <flux:icon.beaker class="mb-4 size-16 text-zinc-300 dark:text-zinc-600" />
                    <flux:heading size="base">{{ __('Agent Playground') }}</flux:heading>
                    <flux:text class="mt-1 text-zinc-500">{{ __('Start testing your AI agent by sending a message.') }}</flux:text>
                </div>
            @endforelse

            @if ($isLoading)
                <div class="flex justify-start">
                    <div class="rounded-lg bg-zinc-100 px-4 py-3 dark:bg-zinc-800">
                        <div class="flex space-x-1">
                            <div class="h-2 w-2 animate-bounce rounded-full bg-zinc-400" style="animation-delay: 0ms"></div>
                            <div class="h-2 w-2 animate-bounce rounded-full bg-zinc-400" style="animation-delay: 150ms"></div>
                            <div class="h-2 w-2 animate-bounce rounded-full bg-zinc-400" style="animation-delay: 300ms"></div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Input area --}}
        <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">
            <form wire:submit="sendMessage" class="flex gap-2">
                <textarea wire:model.live="messageText"
                    rows="1"
                    wire:loading.attr="disabled"
                    wire:target="sendMessage"
                    class="flex-1 resize-none rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                    placeholder="{{ __('Type a message...') }}"
                    x-on:keydown.enter.prevent="if (!$event.shiftKey) { $wire.sendMessage(); }"
                ></textarea>
                <flux:button type="submit" variant="primary" icon="paper-airplane" />
            </form>
            <flux:text size="xs" class="mt-1 text-zinc-400">{{ __('Enter to send, Shift+Enter for new line') }}</flux:text>
        </div>
    </div>
</div>
