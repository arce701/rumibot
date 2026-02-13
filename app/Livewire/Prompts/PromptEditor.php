<?php

namespace App\Livewire\Prompts;

use App\Models\Channel;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::app')]
#[Title('Prompts')]
class PromptEditor extends Component
{
    use AuthorizesRequests;

    public string $systemPrompt = '';

    /** @var array<string, string> */
    public array $channelPrompts = [];

    public function mount(): void
    {
        $this->authorize('prompts.view');

        $tenant = auth()->user()->currentTenant;
        $this->systemPrompt = $tenant->system_prompt ?? '';

        foreach ($tenant->channels as $channel) {
            $this->channelPrompts[$channel->id] = $channel->system_prompt_override ?? '';
        }
    }

    public function saveTenantPrompt(): void
    {
        $this->authorize('prompts.update');

        $tenant = auth()->user()->currentTenant;
        $tenant->update(['system_prompt' => $this->systemPrompt]);

        session()->flash('message', __('System prompt saved.'));
    }

    public function saveChannelPrompt(string $channelId): void
    {
        $this->authorize('prompts.update');

        $channel = Channel::findOrFail($channelId);
        $channel->update([
            'system_prompt_override' => $this->channelPrompts[$channelId] ?: null,
        ]);

        session()->flash('message', __('Channel prompt saved.'));
    }

    public function render(): View
    {
        $tenant = auth()->user()->currentTenant;
        $channels = $tenant->channels()->get();

        return view('livewire.prompts.prompt-editor', [
            'channels' => $channels,
        ]);
    }
}
