<div>
    <div class="mb-6">
        <flux:heading size="xl">{{ __('AI Configuration') }}</flux:heading>
        <flux:text class="mt-1 text-zinc-500">{{ __('Manage your AI provider credentials and model settings.') }}</flux:text>
    </div>

    @if (session('message'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
            {{ session('message') }}
        </div>
    @endif

    {{-- Section 1: LLM Credentials --}}
    <div class="mb-8">
        <div class="mb-4 flex items-center justify-between">
            <flux:heading size="lg">{{ __('LLM Credentials') }}</flux:heading>

            @can('ai-config.update')
                <flux:button wire:click="$toggle('showForm')" variant="primary" icon="plus">
                    {{ __('New Credential') }}
                </flux:button>
            @endcan
        </div>

        @if ($showForm)
            <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="base" class="mb-4">
                    {{ $editingCredentialId ? __('Edit Credential') : __('Create Credential') }}
                </flux:heading>

                <form wire:submit="{{ $editingCredentialId ? 'updateCredential' : 'createCredential' }}" class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <flux:input wire:model="credentialName" :label="__('Name')" :placeholder="__('e.g., Production OpenAI')" required />
                        <flux:select wire:model="credentialProvider" :label="__('Provider')">
                            <flux:select.option value="">{{ __('Select a provider') }}</flux:select.option>
                            @foreach ($providers as $provider)
                                <flux:select.option :value="$provider->value">{{ $provider->label() }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    <flux:input wire:model="credentialApiKey" :label="__('API Key')" type="password"
                        :placeholder="$editingCredentialId ? __('Leave blank to keep current') : ''" :required="!$editingCredentialId" />

                    <div class="flex gap-2">
                        <flux:button type="submit" variant="primary">
                            {{ $editingCredentialId ? __('Update') : __('Create') }}
                        </flux:button>
                        <flux:button wire:click="resetCredentialForm" variant="ghost">{{ __('Cancel') }}</flux:button>
                    </div>
                </form>
            </div>
        @endif

        @if ($credentials->isEmpty())
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 text-center dark:border-amber-800 dark:bg-amber-900/20">
                <flux:icon.exclamation-triangle class="mx-auto mb-2 size-8 text-amber-500" />
                <flux:heading size="base">{{ __('No AI credentials configured') }}</flux:heading>
                <flux:text size="sm" class="mt-1 text-amber-700 dark:text-amber-400">
                    {{ __('You need to add an AI provider API key before the agent can respond to messages.') }}
                </flux:text>
            </div>
        @else
            <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Name') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Provider') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                            <th class="px-6 py-3 text-end text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                        @foreach ($credentials as $credential)
                            <tr wire:key="cred-{{ $credential->id }}">
                                <td class="whitespace-nowrap px-6 py-4">
                                    <flux:text class="font-medium">{{ $credential->name }}</flux:text>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <flux:badge>{{ $credential->provider->label() }}</flux:badge>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    @if ($credential->id === auth()->user()->currentTenant->default_llm_credential_id)
                                        <flux:badge color="green">{{ __('Default') }}</flux:badge>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-end">
                                    <div class="flex justify-end gap-2">
                                        @can('ai-config.update')
                                            @if ($credential->id !== auth()->user()->currentTenant->default_llm_credential_id)
                                                <flux:button wire:click="setDefaultCredential('{{ $credential->id }}')" size="sm" variant="ghost" icon="star">
                                                    {{ __('Set Default') }}
                                                </flux:button>
                                            @endif
                                            <flux:button wire:click="editCredential('{{ $credential->id }}')" size="sm" variant="ghost" icon="pencil" />
                                            <flux:button wire:click="deleteCredential('{{ $credential->id }}')"
                                                wire:confirm="{{ __('Are you sure you want to delete this credential?') }}"
                                                size="sm" variant="ghost" icon="trash" class="text-red-600 hover:text-red-700 dark:text-red-400" />
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Section 2: Model Configuration --}}
    <div>
        <flux:heading size="lg" class="mb-4">{{ __('Model Configuration') }}</flux:heading>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            @if ($credentials->isEmpty())
                <div class="py-4 text-center">
                    <flux:text class="text-zinc-500">{{ __('Add an LLM credential above to configure your model settings.') }}</flux:text>
                </div>
            @else
                <form wire:submit="saveModelSettings" class="space-y-6">
                    <div class="grid gap-4 md:grid-cols-2">
                        <flux:select wire:model.live="selectedCredentialId" :label="__('LLM Credential')">
                            <flux:select.option value="">{{ __('Select a credential') }}</flux:select.option>
                            @foreach ($credentials as $credential)
                                <flux:select.option :value="$credential->id">{{ $credential->name }} ({{ $credential->provider->label() }})</flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:select wire:model="selectedModel" :label="__('Model')">
                            <flux:select.option value="">{{ __('Select a model') }}</flux:select.option>
                            @foreach ($this->availableModels as $model)
                                <flux:select.option :value="$model">{{ $model }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('Temperature') }}: {{ number_format($aiTemperature, 2) }}
                        </label>
                        <input type="range" wire:model.live="aiTemperature" min="0" max="2" step="0.01"
                            class="h-2 w-full cursor-pointer appearance-none rounded-lg bg-zinc-200 accent-blue-600 dark:bg-zinc-700" />
                        <div class="mt-1 flex justify-between text-xs text-zinc-500">
                            <span>0.00 ({{ __('Precise') }})</span>
                            <span>2.00 ({{ __('Creative') }})</span>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('Max Tokens') }}: {{ number_format($aiMaxTokens) }}
                        </label>
                        <input type="range" wire:model.live="aiMaxTokens" min="100" max="8192" step="50"
                            class="h-2 w-full cursor-pointer appearance-none rounded-lg bg-zinc-200 accent-blue-600 dark:bg-zinc-700" />
                        <div class="mt-1 flex justify-between text-xs text-zinc-500">
                            <span>100</span>
                            <span>8192</span>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('Context Window') }}: {{ $aiContextWindow }} {{ __('context messages') }}
                        </label>
                        <input type="range" wire:model.live="aiContextWindow" min="1" max="100" step="1"
                            class="h-2 w-full cursor-pointer appearance-none rounded-lg bg-zinc-200 accent-blue-600 dark:bg-zinc-700" />
                        <div class="mt-1 flex justify-between text-xs text-zinc-500">
                            <span>1</span>
                            <span>100</span>
                        </div>
                    </div>

                    <flux:switch wire:model="aiStreaming" :label="__('Streaming')" :description="__('Enable streaming responses (experimental).')" />

                    @can('ai-config.update')
                        <flux:button type="submit" variant="primary">{{ __('Save Configuration') }}</flux:button>
                    @endcan
                </form>
            @endif
        </div>
    </div>
</div>
