<?php

namespace App\Livewire\Playground;

use App\Ai\Agents\PlaygroundChatAgent;
use App\Models\Channel;
use App\Models\Enums\DocumentStatus;
use App\Models\KnowledgeDocument;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::app')]
#[Title('Agent Playground')]
class AgentPlayground extends Component
{
    use AuthorizesRequests;

    public ?string $selectedChannelId = null;

    public string $messageText = '';

    /** @var array<int, array{role: string, content: string}> */
    public array $chatMessages = [];

    public bool $isLoading = false;

    public function mount(): void
    {
        $this->authorize('prompts.view');

        $tenant = auth()->user()->currentTenant;
        $firstChannel = $tenant->channels()->first();

        if ($firstChannel) {
            $this->selectedChannelId = $firstChannel->id;
        }
    }

    public function selectChannel(string $channelId): void
    {
        $this->selectedChannelId = $channelId;
        $this->chatMessages = [];
        $this->messageText = '';
    }

    public function sendMessage(): void
    {
        $this->authorize('prompts.view');

        if (trim($this->messageText) === '' || ! $this->selectedChannelId) {
            return;
        }

        $userMessage = trim($this->messageText);
        $this->chatMessages[] = ['role' => 'user', 'content' => $userMessage];
        $this->messageText = '';
        $this->isLoading = true;

        try {
            $tenant = auth()->user()->currentTenant;
            $channel = Channel::findOrFail($this->selectedChannelId);

            $agent = new PlaygroundChatAgent($tenant, $channel, $this->chatMessages);

            $credential = $tenant->defaultLlmCredential;
            if ($credential) {
                config()->set("ai.providers.{$credential->provider->value}.key", $credential->api_key);
            }

            $response = $agent->prompt(
                $userMessage,
                provider: $credential?->provider->value ?? config('rumibot.ai.default_provider'),
                model: $tenant->default_ai_model ?? config('rumibot.ai.default_model'),
            );

            $this->chatMessages[] = ['role' => 'assistant', 'content' => (string) $response];
        } catch (\Throwable $e) {
            $this->chatMessages[] = ['role' => 'assistant', 'content' => __('Error: Unable to generate a response. Please check your AI configuration.')];
        } finally {
            $this->isLoading = false;
        }
    }

    public function clearChat(): void
    {
        $this->chatMessages = [];
        $this->messageText = '';
    }

    public function getDocumentCountProperty(): int
    {
        if (! $this->selectedChannelId) {
            return 0;
        }

        return KnowledgeDocument::query()
            ->where('status', DocumentStatus::Ready)
            ->where(fn ($q) => $q
                ->whereJsonLength('channel_scope', 0)
                ->orWhereJsonContains('channel_scope', $this->selectedChannelId)
            )
            ->count();
    }

    public function getDocumentsProperty(): \Illuminate\Database\Eloquent\Collection
    {
        if (! $this->selectedChannelId) {
            return collect();
        }

        return KnowledgeDocument::query()
            ->where('status', DocumentStatus::Ready)
            ->where(fn ($q) => $q
                ->whereJsonLength('channel_scope', 0)
                ->orWhereJsonContains('channel_scope', $this->selectedChannelId)
            )
            ->get();
    }

    public function render(): View
    {
        $tenant = auth()->user()->currentTenant;
        $channels = $tenant->channels()->get();

        return view('livewire.playground.agent-playground', [
            'channels' => $channels,
        ]);
    }
}
