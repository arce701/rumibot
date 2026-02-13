<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Leads') }}</flux:heading>
    </div>

    @if (session('message'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
            {{ session('message') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-4 flex flex-col gap-4 sm:flex-row">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search leads...')" />
        </div>

        <flux:select wire:model.live="statusFilter" class="w-full sm:w-48">
            <flux:select.option value="">{{ __('All Statuses') }}</flux:select.option>
            @foreach ($statuses as $status)
                <flux:select.option :value="$status->value">{{ ucfirst($status->value) }}</flux:select.option>
            @endforeach
        </flux:select>

        @feature('data-export')
            <flux:button wire:click="exportLeads" variant="ghost" icon="arrow-down-tray">{{ __('Export') }}</flux:button>
        @endfeature
    </div>

    @if ($leads->isEmpty())
        <div class="rounded-xl border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text>{{ __('No leads found.') }}</flux:text>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Name') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Contact') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Company') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Score') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Date') }}</th>
                        <th class="px-6 py-3 text-end text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                    @foreach ($leads as $lead)
                        <tr wire:key="lead-{{ $lead->id }}">
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text class="font-medium">{{ $lead->full_name }}</flux:text>
                            </td>
                            <td class="px-6 py-4">
                                <flux:text size="sm">{{ $lead->phone }}</flux:text>
                                @if ($lead->email)
                                    <flux:text size="sm" class="text-zinc-500">{{ $lead->email }}</flux:text>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text>{{ $lead->company_name ?? '-' }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @switch($lead->status->value)
                                    @case('new')
                                        <flux:badge color="blue">{{ __('New') }}</flux:badge>
                                        @break
                                    @case('contacted')
                                        <flux:badge color="yellow">{{ __('Contacted') }}</flux:badge>
                                        @break
                                    @case('converted')
                                        <flux:badge color="green">{{ __('Converted') }}</flux:badge>
                                        @break
                                    @case('lost')
                                        <flux:badge color="red">{{ __('Lost') }}</flux:badge>
                                        @break
                                @endswitch
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text>{{ $lead->qualification_score ?? '-' }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text size="sm">{{ $lead->created_at->format('M d, Y') }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-end">
                                @can('leads.update')
                                    <flux:button wire:click="startEdit('{{ $lead->id }}')" size="sm" variant="ghost" icon="pencil" />
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $leads->links() }}
        </div>
    @endif

    <flux:modal wire:model="editingLeadId" class="max-w-lg">
        <flux:heading size="lg">{{ __('Edit Lead') }}</flux:heading>

        <div class="mt-4 space-y-4">
            <flux:select wire:model="editStatus" :label="__('Status')">
                @foreach ($statuses as $status)
                    <flux:select.option :value="$status->value">{{ ucfirst($status->value) }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="editScore" :label="__('Qualification Score')" type="number" min="0" max="100" />

            <flux:textarea wire:model="editNotes" :label="__('Notes')" rows="3" />
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <flux:button wire:click="cancelEdit" variant="ghost">{{ __('Cancel') }}</flux:button>
            <flux:button wire:click="updateLead" variant="primary">{{ __('Save') }}</flux:button>
        </div>
    </flux:modal>
</div>
