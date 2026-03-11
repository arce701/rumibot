@use('Illuminate\Support\Facades\Storage')

<div>
    <div class="mb-6 flex items-center gap-4">
        <flux:button :href="route('conversations')" variant="ghost" icon="arrow-left" wire:navigate />
        <div>
            <flux:heading size="xl">{{ $conversation->contact_name ?? $conversation->contact_phone }}</flux:heading>
            <flux:text size="sm" class="text-zinc-500">
                {{ phone_flag($conversation->contact_phone) }} {{ format_phone($conversation->contact_phone) }}
                @if ($conversation->contact_country)
                    · {{ \App\Support\PhoneHelper::countryNameFromIso($conversation->contact_country) }}
                @endif
            </flux:text>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="max-h-[600px] space-y-4 overflow-y-auto p-6">
                    @forelse ($messages as $message)
                        <div wire:key="msg-{{ $message->id }}" class="flex {{ $message->role === 'user' ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[75%] rounded-lg px-4 py-3 {{ $message->role === 'user' ? 'bg-blue-600 text-white' : 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100' }}">
                                @if (($message->metadata['media_type'] ?? null) === 'image' && ($message->metadata['media_path'] ?? null))
                                    <img
                                        src="{{ Storage::disk('s3')->temporaryUrl($message->metadata['media_path'], now()->addHour()) }}"
                                        alt="{{ $message->metadata['media_filename'] ?? '' }}"
                                        class="mb-2 max-w-xs rounded"
                                    />
                                @elseif (($message->metadata['media_type'] ?? null) === 'document' && ($message->metadata['media_path'] ?? null))
                                    <a
                                        href="{{ Storage::disk('s3')->temporaryUrl($message->metadata['media_path'], now()->addHour()) }}"
                                        target="_blank"
                                        class="mb-2 flex items-center gap-2 rounded border px-3 py-2 text-sm {{ $message->role === 'user' ? 'border-blue-400 text-white hover:bg-blue-700' : 'border-zinc-300 text-zinc-700 hover:bg-zinc-200 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700' }}"
                                    >
                                        <flux:icon.document class="size-5" />
                                        <span>{{ $message->metadata['media_filename'] ?? __('Download document') }}</span>
                                    </a>
                                @endif
                                @if ($message->content)
                                    <p class="whitespace-pre-wrap text-sm">{{ $message->content }}</p>
                                @endif
                                <flux:text size="xs" class="mt-1 {{ $message->role === 'user' ? 'text-blue-200' : 'text-zinc-400' }}">
                                    {{ $message->created_at?->format('M d, H:i') }}
                                </flux:text>
                                @if ($message->role === 'assistant' && ($message->metadata['provider'] ?? null) === 'human')
                                    <flux:badge size="sm" color="amber" class="mt-1">{{ __('Operator') }}</flux:badge>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center">
                            <flux:text>{{ __('No messages yet.') }}</flux:text>
                        </div>
                    @endforelse
                </div>
            </div>

            @if (in_array($conversation->status, [\App\Models\Enums\ConversationStatus::Active, \App\Models\Enums\ConversationStatus::Escalated]))
                <form wire:submit="sendReply" class="mt-4 space-y-2" x-data>
                    @if ($attachment)
                        <div class="flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">
                            <flux:icon.paper-clip class="size-4 text-zinc-500" />
                            <flux:text size="sm" class="flex-1 truncate">
                                {{ $attachment->getClientOriginalName() }} ({{ number_format($attachment->getSize() / 1024, 0) }} KB)
                            </flux:text>
                            <flux:button size="xs" variant="ghost" icon="x-mark" wire:click="removeAttachment" type="button">
                                {{ __('Remove attachment') }}
                            </flux:button>
                        </div>
                    @endif

                    <div class="flex gap-2">
                        <flux:textarea
                            wire:model="replyText"
                            :placeholder="__('Type a reply...')"
                            rows="2"
                            class="flex-1"
                        />
                        <div class="flex flex-col gap-1">
                            <input
                                type="file"
                                wire:model="attachment"
                                class="hidden"
                                x-ref="fileInput"
                                accept="image/jpeg,image/jpg,image/png,image/webp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv"
                            />
                            <flux:button
                                type="button"
                                variant="ghost"
                                icon="paper-clip"
                                x-on:click="$refs.fileInput.click()"
                                wire:loading.attr="disabled"
                                wire:target="attachment"
                                :title="__('Attach file')"
                            />
                            <flux:button type="submit" variant="primary" icon="paper-airplane" wire:loading.attr="disabled">
                                {{ __('Send') }}
                            </flux:button>
                        </div>
                    </div>

                    <div wire:loading wire:target="attachment">
                        <flux:text size="xs" class="text-zinc-500">{{ __('Uploading...') }}</flux:text>
                    </div>
                </form>
            @endif
        </div>

        <div class="space-y-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="base" class="mb-4">{{ __('Info') }}</flux:heading>
                <dl class="space-y-3">
                    @if ($conversation->contact_country)
                        <div>
                            <flux:text size="sm" class="text-zinc-500">{{ __('Country') }}</flux:text>
                            <flux:text>{{ \App\Support\PhoneHelper::flagFromIso($conversation->contact_country) }} {{ \App\Support\PhoneHelper::countryNameFromIso($conversation->contact_country) }}</flux:text>
                        </div>
                    @endif
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
                        <flux:text size="sm" class="text-zinc-500">{{ __('AI Status') }}</flux:text>
                        <div>
                            @if ($conversation->isAiPaused())
                                <flux:badge color="yellow">{{ __('AI Paused') }}</flux:badge>
                                <flux:text size="xs" class="mt-1 text-zinc-500">
                                    {{ __('Until :time', ['time' => $conversation->ai_paused_until->format('M d, H:i')]) }}
                                </flux:text>
                                <flux:button wire:click="resumeAi" size="xs" variant="ghost" class="mt-1">
                                    {{ __('Resume AI') }}
                                </flux:button>
                            @else
                                <flux:badge color="green">{{ __('Active') }}</flux:badge>
                            @endif
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
                            <flux:badge>{{ $lead->status->label() }}</flux:badge>
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
