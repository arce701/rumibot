<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Escalations') }}</flux:heading>
    </div>

    @if (session('message'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
            {{ session('message') }}
        </div>
    @endif

    @if ($escalations->isEmpty())
        <div class="rounded-xl border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text>{{ __('No escalations found.') }}</flux:text>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Contact') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Reason') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Note') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Assigned To') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Date') }}</th>
                        <th class="px-6 py-3 text-end text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                    @foreach ($escalations as $escalation)
                        <tr wire:key="esc-{{ $escalation->id }}">
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text class="font-medium">{{ $escalation->conversation?->contact_name ?? __('Unknown') }}</flux:text>
                                <flux:text size="sm" class="text-zinc-500">{{ $escalation->conversation?->contact_phone }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:badge>{{ $escalation->reason }}</flux:badge>
                            </td>
                            <td class="max-w-xs truncate px-6 py-4">
                                <flux:text size="sm">{{ $escalation->note ?? '-' }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @can('escalations.assign')
                                    <flux:select wire:change="assign('{{ $escalation->id }}', $event.target.value)" class="w-40">
                                        <flux:select.option value="">{{ __('Unassigned') }}</flux:select.option>
                                        @foreach ($teamMembers as $member)
                                            <flux:select.option :value="$member->id" :selected="$escalation->assigned_to_user_id == $member->id">
                                                {{ $member->name }}
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>
                                @else
                                    <flux:text>{{ $escalation->assignedTo?->name ?? __('Unassigned') }}</flux:text>
                                @endcan
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:badge :color="$escalation->isResolved() ? 'green' : 'yellow'">
                                    {{ $escalation->isResolved() ? __('Resolved') : __('Pending') }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text size="sm">{{ $escalation->created_at->format('M d, Y') }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-end">
                                @if (! $escalation->isResolved())
                                    @can('escalations.resolve')
                                        <flux:button wire:click="startResolve('{{ $escalation->id }}')" size="sm" variant="primary" icon="check">
                                            {{ __('Resolve') }}
                                        </flux:button>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $escalations->links() }}
        </div>
    @endif

    <flux:modal wire:model="resolvingEscalationId" class="max-w-lg">
        <flux:heading size="lg">{{ __('Resolve Escalation') }}</flux:heading>

        <div class="mt-4">
            <flux:textarea wire:model="resolutionNote" :label="__('Resolution Note')" rows="4" required />
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <flux:button wire:click="cancelResolve" variant="ghost">{{ __('Cancel') }}</flux:button>
            <flux:button wire:click="resolve" variant="primary">{{ __('Resolve') }}</flux:button>
        </div>
    </flux:modal>
</div>
