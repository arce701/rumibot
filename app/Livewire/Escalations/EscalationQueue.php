<?php

namespace App\Livewire\Escalations;

use App\Models\Enums\ConversationStatus;
use App\Models\Escalation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
#[Title('Escalations')]
class EscalationQueue extends Component
{
    use AuthorizesRequests, WithPagination;

    public bool $showResolveModal = false;

    public ?string $resolvingEscalationId = null;

    #[Validate('required|string|max:1000')]
    public string $resolutionNote = '';

    public ?string $assignUserId = null;

    public function startResolve(string $escalationId): void
    {
        $this->authorize('escalations.resolve');

        $this->resolvingEscalationId = $escalationId;
        $this->resolutionNote = '';
        $this->showResolveModal = true;
    }

    public function resolve(): void
    {
        $this->authorize('escalations.resolve');
        $this->validate();

        $escalation = Escalation::findOrFail($this->resolvingEscalationId);
        $escalation->update([
            'resolved_at' => now(),
            'resolution_note' => $this->resolutionNote,
        ]);

        $escalation->conversation->update([
            'status' => ConversationStatus::Active,
        ]);

        $this->showResolveModal = false;
        $this->reset(['resolvingEscalationId', 'resolutionNote']);

        session()->flash('message', __('Escalation resolved.'));
    }

    public function assign(string $escalationId, string $userId): void
    {
        $this->authorize('escalations.assign');

        $escalation = Escalation::findOrFail($escalationId);
        $escalation->update(['assigned_to_user_id' => $userId]);
    }

    public function cancelResolve(): void
    {
        $this->showResolveModal = false;
        $this->reset(['resolvingEscalationId', 'resolutionNote']);
    }

    public function render(): View
    {
        $this->authorize('escalations.view');

        $tenant = auth()->user()->currentTenant;

        $escalations = Escalation::query()
            ->with(['conversation.channel', 'assignedTo'])
            ->latest()
            ->paginate(20);

        $teamMembers = $tenant->users()->get();

        return view('livewire.escalations.escalation-queue', [
            'escalations' => $escalations,
            'teamMembers' => $teamMembers,
        ]);
    }
}
