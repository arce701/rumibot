<?php

namespace App\Livewire\Conversations;

use App\Exports\ConversationsExport;
use App\Models\Conversation;
use App\Models\Enums\ConversationStatus;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Laravel\Pennant\Feature;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

#[Layout('layouts::app')]
#[Title('Conversations')]
class ConversationList extends Component
{
    use AuthorizesRequests, WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $channelFilter = '';

    #[Url]
    public string $statusFilter = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedChannelFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function exportConversations(): ?BinaryFileResponse
    {
        if (! Feature::active('data-export')) {
            session()->flash('error', __('Export not available on your plan.'));

            return null;
        }

        return Excel::download(
            new ConversationsExport($this->channelFilter, $this->statusFilter, $this->search),
            'conversations-'.now()->format('Y-m-d').'.xlsx',
        );
    }

    public function render(): View
    {
        $this->authorize('conversations.view');

        $tenant = auth()->user()->currentTenant;

        $conversations = Conversation::query()
            ->with('channel')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('contact_phone', 'like', "%{$this->search}%")
                        ->orWhere('contact_name', 'like', "%{$this->search}%");
                });
            })
            ->when($this->channelFilter, fn ($query) => $query->where('channel_id', $this->channelFilter))
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->latest('last_message_at')
            ->paginate(20);

        $channels = $tenant->channels()->get();

        return view('livewire.conversations.conversation-list', [
            'conversations' => $conversations,
            'channels' => $channels,
            'statuses' => ConversationStatus::cases(),
        ]);
    }
}
