<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Agent Playground') }}</flux:heading>
        <flux:text class="mt-1 text-zinc-500">{{ __('Test your AI agent with the configured prompts and knowledge base.') }}</flux:text>
    </div>

    {{-- Top bar: Channel selector + info badges + clear button --}}
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <flux:select wire:model.live="selectedChannelId" wire:change="selectChannel($event.target.value)" class="w-64">
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
                <svg :class="showTools && 'rotate-180'" class="h-4 w-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
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
                <svg :class="showDocs && 'rotate-180'" class="h-4 w-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
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
         @chat-updated.window="scrollToBottom()"
    >
        <div x-ref="chatContainer" class="max-h-[500px] space-y-4 overflow-y-auto p-6" wire:poll.visible.30s>
            @forelse ($chatMessages as $index => $message)
                <div wire:key="msg-{{ $index }}" class="flex {{ $message['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[75%] rounded-lg px-4 py-3 {{ $message['role'] === 'user' ? 'bg-blue-600 text-white' : 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100' }}">
                        <p class="whitespace-pre-wrap text-sm">{{ $message['content'] }}</p>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center py-16 text-center">
                    <svg class="mb-4 h-16 w-16 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
                    </svg>
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
            <form wire:submit="sendMessage" class="flex gap-2"
                  x-data
                  @keydown.enter.prevent="if (!$event.shiftKey) { $wire.sendMessage(); }"
            >
                <textarea wire:model="messageText"
                    rows="1"
                    :disabled="$wire.isLoading"
                    class="flex-1 resize-none rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                    placeholder="{{ __('Type a message...') }}"
                ></textarea>
                <flux:button type="submit" variant="primary" icon="paper-airplane" :disabled="$isLoading" />
            </form>
            <flux:text size="xs" class="mt-1 text-zinc-400">{{ __('Enter to send, Shift+Enter for new line') }}</flux:text>
        </div>
    </div>
</div>
