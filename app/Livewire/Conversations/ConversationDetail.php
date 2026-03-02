<?php

namespace App\Livewire\Conversations;

use App\Events\ConversationClosed;
use App\Jobs\SendWhatsAppMessage;
use App\Models\Conversation;
use App\Models\Enums\ConversationStatus;
use App\Models\Message;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts::app')]
class ConversationDetail extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public Conversation $conversation;

    public string $replyText = '';

    public $attachment = null;

    public function mount(Conversation $conversation): void
    {
        $this->authorize('conversations.view');
        $this->conversation = $conversation;
    }

    public function sendReply(): void
    {
        $this->authorize('conversations.view');

        $rules = [
            'replyText' => $this->attachment ? ['nullable', 'string', 'max:4096'] : ['required', 'string', 'max:4096'],
            'attachment' => ['nullable', 'file', 'max:102400'],
        ];

        $this->validate($rules);

        if ($this->attachment) {
            $mime = $this->attachment->getMimeType();
            $isImage = str_starts_with($mime, 'image/');

            if ($isImage) {
                $this->validate([
                    'attachment' => ['file', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
                ]);
            } else {
                $this->validate([
                    'attachment' => ['file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv', 'max:102400'],
                ]);
            }
        }

        $metadata = [
            'provider' => 'human',
            'sent_by' => Auth::user()->name,
        ];

        $mediaType = null;
        $mediaPath = null;
        $mediaFilename = null;

        if ($this->attachment) {
            $tenantId = $this->conversation->tenant_id;
            $mediaPath = $this->attachment->store("tenants/{$tenantId}/attachments", 's3');
            $mediaFilename = $this->attachment->getClientOriginalName();
            $mime = $this->attachment->getMimeType();
            $mediaType = str_starts_with($mime, 'image/') ? 'image' : 'document';

            $metadata['media_type'] = $mediaType;
            $metadata['media_path'] = $mediaPath;
            $metadata['media_filename'] = $mediaFilename;
            $metadata['media_size'] = $this->attachment->getSize();
        }

        $message = Message::create([
            'conversation_id' => $this->conversation->id,
            'tenant_id' => $this->conversation->tenant_id,
            'role' => 'assistant',
            'content' => $this->replyText,
            'metadata' => $metadata,
        ]);

        $this->conversation->increment('messages_count');
        $this->conversation->update([
            'last_message_at' => now(),
            'ai_paused_until' => now()->addHours(24),
        ]);

        SendWhatsAppMessage::dispatch(
            $this->conversation,
            $this->replyText,
            $message->id,
            $mediaType,
            $mediaPath,
            $mediaFilename,
        );

        $this->replyText = '';
        $this->attachment = null;

        session()->flash('message', __('Reply sent. AI paused for 24 hours.'));
    }

    public function removeAttachment(): void
    {
        $this->attachment = null;
    }

    public function resumeAi(): void
    {
        $this->authorize('conversations.view');

        $this->conversation->update(['ai_paused_until' => null]);

        session()->flash('message', __('AI resumed.'));
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
