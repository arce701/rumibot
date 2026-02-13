<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Activity Log') }}</flux:heading>
    </div>

    <div class="mb-4">
        <flux:select wire:model.live="subjectTypeFilter" class="w-full sm:w-64">
            <flux:select.option value="">{{ __('All Models') }}</flux:select.option>
            @foreach ($subjectTypes as $type)
                <flux:select.option :value="$type">{{ class_basename($type) }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    @if ($activities->isEmpty())
        <div class="rounded-xl border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text>{{ __('No activity found.') }}</flux:text>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Date') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('User') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Action') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Model') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Changes') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                    @foreach ($activities as $activity)
                        <tr wire:key="activity-{{ $activity->id }}">
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text size="sm">{{ $activity->created_at->format('M d, Y H:i') }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text>{{ $activity->causer?->name ?? __('System') }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:badge>{{ $activity->description }}</flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text size="sm">{{ class_basename($activity->subject_type) }}</flux:text>
                            </td>
                            <td class="px-6 py-4">
                                @if ($activity->properties->has('old'))
                                    <div class="max-w-xs space-y-1">
                                        @foreach ($activity->properties['attributes'] ?? [] as $key => $value)
                                            @if (isset($activity->properties['old'][$key]) && $activity->properties['old'][$key] !== $value)
                                                <flux:text size="sm">
                                                    <span class="font-medium">{{ $key }}:</span>
                                                    <span class="text-red-500 line-through">{{ is_array($activity->properties['old'][$key]) ? json_encode($activity->properties['old'][$key]) : $activity->properties['old'][$key] }}</span>
                                                    →
                                                    <span class="text-green-500">{{ is_array($value) ? json_encode($value) : $value }}</span>
                                                </flux:text>
                                            @endif
                                        @endforeach
                                    </div>
                                @elseif ($activity->properties->has('attributes'))
                                    <div class="max-w-xs">
                                        @foreach ($activity->properties['attributes'] ?? [] as $key => $value)
                                            <flux:text size="sm">
                                                <span class="font-medium">{{ $key }}:</span>
                                                {{ is_array($value) ? json_encode($value) : $value }}
                                            </flux:text>
                                        @endforeach
                                    </div>
                                @else
                                    <flux:text size="sm" class="text-zinc-400">-</flux:text>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $activities->links() }}
        </div>
    @endif
</div>
