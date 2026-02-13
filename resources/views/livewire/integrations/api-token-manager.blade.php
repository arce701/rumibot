<div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
    <flux:heading size="lg" class="mb-4">{{ __('API Tokens') }}</flux:heading>

    @if ($plainTextToken)
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
            <flux:text class="mb-1 font-medium text-amber-800 dark:text-amber-400">{{ __('Copy your new API token. It won\'t be shown again.') }}</flux:text>
            <code class="block break-all rounded bg-amber-100 p-2 text-sm dark:bg-amber-900/40">{{ $plainTextToken }}</code>
        </div>
    @endif

    <form wire:submit="createToken" class="mb-6 flex gap-2">
        <flux:input wire:model="tokenName" :placeholder="__('Token name (e.g., n8n production)')" class="flex-1" />
        <flux:button type="submit" variant="primary">{{ __('Create Token') }}</flux:button>
    </form>

    @if ($tokens->isEmpty())
        <flux:text class="text-center">{{ __('No API tokens created yet.') }}</flux:text>
    @else
        <div class="space-y-2">
            @foreach ($tokens as $token)
                <div wire:key="token-{{ $token->id }}" class="flex items-center justify-between rounded-lg border border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    <div>
                        <flux:text class="font-medium">{{ $token->name }}</flux:text>
                        <flux:text size="sm" class="text-zinc-500">{{ __('Created') }} {{ $token->created_at->diffForHumans() }}</flux:text>
                    </div>
                    <flux:button wire:click="revokeToken({{ $token->id }})"
                        wire:confirm="{{ __('Are you sure you want to revoke this token?') }}"
                        size="sm" variant="ghost" icon="trash" class="text-red-600 hover:text-red-700 dark:text-red-400" />
                </div>
            @endforeach
        </div>
    @endif
</div>
