<div @if($hasProcessing) wire:poll.5s @endif>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">{{ __('Knowledge Base') }}</flux:heading>

        <flux:button wire:click="$toggle('showUploadForm')" variant="primary" icon="plus">
            {{ __('Upload Document') }}
        </flux:button>
    </div>

    @if (session('message'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
            {{ session('message') }}
        </div>
    @endif

    @if ($showUploadForm)
        <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Upload New Document') }}</flux:heading>

            <form wire:submit="upload" class="space-y-4">
                <flux:input wire:model="title" :label="__('Title')" :placeholder="__('Document title')" required />

                <flux:input wire:model="file" type="file" :label="__('File')" accept=".pdf,.txt,.md,.csv" required />

                @if ($channels->isNotEmpty())
                    <div>
                        <flux:label>{{ __('Channel Scope (optional)') }}</flux:label>
                        <flux:text size="sm" class="mb-2">{{ __('Leave empty to make available to all channels.') }}</flux:text>
                        @foreach ($channels as $channel)
                            <flux:checkbox
                                wire:model="channelScope"
                                :value="$channel->id"
                                :label="$channel->name"
                            />
                        @endforeach
                    </div>
                @endif

                <div class="flex gap-2">
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="upload">{{ __('Upload') }}</span>
                        <span wire:loading wire:target="upload">{{ __('Uploading...') }}</span>
                    </flux:button>
                    <flux:button wire:click="$toggle('showUploadForm')" variant="ghost">
                        {{ __('Cancel') }}
                    </flux:button>
                </div>
            </form>
        </div>
    @endif

    @if ($documents->isEmpty())
        <div class="rounded-xl border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text>{{ __('No documents uploaded yet. Upload your first document to build the knowledge base.') }}</flux:text>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Title') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Chunks') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Date') }}</th>
                        <th class="px-6 py-3 text-end text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                    @foreach ($documents as $document)
                        <tr>
                            <td class="whitespace-nowrap px-6 py-4">
                                <div>
                                    <flux:text class="font-medium">{{ $document->title }}</flux:text>
                                    <flux:text size="sm" class="text-zinc-500">{{ $document->file_name }}</flux:text>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @switch($document->status->value)
                                    @case('pending')
                                        <flux:badge color="zinc">{{ __('Pending') }}</flux:badge>
                                        @break
                                    @case('processing')
                                        <flux:badge color="yellow">{{ __('Processing') }}</flux:badge>
                                        @break
                                    @case('ready')
                                        <flux:badge color="green">{{ __('Ready') }}</flux:badge>
                                        @break
                                    @case('failed')
                                        <flux:badge color="red" :title="$document->error_message">{{ __('Failed') }}</flux:badge>
                                        @break
                                @endswitch
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text>{{ $document->total_chunks }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text size="sm">{{ $document->created_at->format('M d, Y') }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-end">
                                <div class="flex justify-end gap-2">
                                    @if ($document->isProcessable())
                                        <flux:button wire:click="reprocess('{{ $document->id }}')" size="sm" variant="ghost" icon="arrow-path">
                                            {{ __('Reprocess') }}
                                        </flux:button>
                                    @endif
                                    <flux:button wire:click="deleteDocument('{{ $document->id }}')" wire:confirm="{{ __('Are you sure you want to delete this document?') }}" size="sm" variant="ghost" icon="trash" class="text-red-600 hover:text-red-700 dark:text-red-400">
                                        {{ __('Delete') }}
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
