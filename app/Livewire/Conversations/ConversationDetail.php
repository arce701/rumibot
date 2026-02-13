<?php

namespace App\Livewire\Conversations;

use App\Events\ConversationClosed;
use App\Models\Conversation;
use App\Models\Enums\ConversationStatus;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class ConversationDetail extends Component
{
    use AuthorizesRequests;

    public Conversation $conversation;

    public function mount(Conversation $conversation): void
    {
        $this->authorize('conversations.view');
        $this->conversation = $conversation;
    }

    public function closeConversation(): void
    {
        $this->authorize('conversations.view');

        $this->conversation->update(['status' => ConversationStatus::Closed]);

        event(new ConversationClosed($this->conversation));

        session()->flash('message', __('Conversation closed successfully.'));
    }

    public function getTitle(): string
    {
        return $this->conversation->contact_name ?? $this->conversation->contact_phone;
    }

    public function render(): View
    {
        $this->conversation->load(['messages', 'channel']);

        $lead = $this->conversation->tenant_id
            ? \App\Models\Lead::where('conversation_id', $this->conversation->id)->first()
            : null;

        $escalation = \App\Models\Escalation::where('conversation_id', $this->conversation->id)->first();

        $totalTokens = $this->conversation->total_input_tokens + $this->conversation->total_output_tokens;

        return view('livewire.conversations.conversation-detail', [
            'messages' => $this->conversation->messages->sortBy('created_at'),
            'channel' => $this->conversation->channel,
            'lead' => $lead,
            'escalation' => $escalation,
            'totalTokens' => $totalTokens,
        ])->title($this->getTitle());
    }
}
