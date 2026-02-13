<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">{{ __('Team') }}</flux:heading>

        @can('team.invite')
            <flux:button wire:click="$toggle('showInviteForm')" variant="primary" icon="plus">
                {{ __('Invite Member') }}
            </flux:button>
        @endcan
    </div>

    @if (session('message'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
            {{ session('message') }}
        </div>
    @endif

    @if ($showInviteForm)
        <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg" class="mb-4">{{ __('Invite New Member') }}</flux:heading>

            <form wire:submit="invite" class="space-y-4">
                <flux:input wire:model="inviteEmail" :label="__('Email')" type="email" required />

                <flux:select wire:model="inviteRole" :label="__('Role')">
                    <flux:select.option value="tenant_admin">{{ __('Admin') }}</flux:select.option>
                    <flux:select.option value="tenant_member">{{ __('Member') }}</flux:select.option>
                </flux:select>

                <div class="flex gap-2">
                    <flux:button type="submit" variant="primary">{{ __('Invite') }}</flux:button>
                    <flux:button wire:click="$toggle('showInviteForm')" variant="ghost">{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    @endif

    @if ($members->isEmpty())
        <div class="rounded-xl border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text>{{ __('No team members.') }}</flux:text>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Name') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Email') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Role') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Member Since') }}</th>
                        <th class="px-6 py-3 text-end text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                    @foreach ($members as $member)
                        <tr wire:key="member-{{ $member->id }}">
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text class="font-medium">{{ $member->name }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text>{{ $member->email }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @switch($member->pivot->role)
                                    @case('tenant_owner')
                                        <flux:badge color="purple">{{ __('Owner') }}</flux:badge>
                                        @break
                                    @case('tenant_admin')
                                        <flux:badge color="blue">{{ __('Admin') }}</flux:badge>
                                        @break
                                    @case('tenant_member')
                                        <flux:badge color="zinc">{{ __('Member') }}</flux:badge>
                                        @break
                                @endswitch
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text size="sm">{{ \Carbon\Carbon::parse($member->pivot->created_at)->format('M d, Y') }}</flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-end">
                                @if ($member->pivot->role !== 'tenant_owner' && $member->id !== auth()->id())
                                    <div class="flex justify-end gap-2">
                                        @can('team.invite')
                                            <flux:select wire:change="changeRole({{ $member->id }}, $event.target.value)" class="w-32">
                                                <flux:select.option value="tenant_admin" :selected="$member->pivot->role === 'tenant_admin'">{{ __('Admin') }}</flux:select.option>
                                                <flux:select.option value="tenant_member" :selected="$member->pivot->role === 'tenant_member'">{{ __('Member') }}</flux:select.option>
                                            </flux:select>
                                        @endcan

                                        @can('team.remove')
                                            <flux:button wire:click="removeMember({{ $member->id }})"
                                                wire:confirm="{{ __('Are you sure you want to remove this member?') }}"
                                                size="sm" variant="ghost" icon="trash" class="text-red-600 hover:text-red-700 dark:text-red-400" />
                                        @endcan
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
