<?php

namespace App\Livewire;

use App\Models\Conversation;
use App\Models\Enums\ConversationStatus;
use App\Models\Escalation;
use App\Models\Lead;
use App\Models\Message;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render(): View
    {
        $tenant = auth()->user()->currentTenant;

        $activeConversations = Conversation::where('status', ConversationStatus::Active)->count();

        $messagesToday = Message::whereDate('created_at', today())->count();

        $newLeadsThisWeek = Lead::where('created_at', '>=', now()->subWeek())->count();

        $pendingEscalations = Escalation::whereNull('resolved_at')->count();

        return view('livewire.dashboard', [
            'activeConversations' => $activeConversations,
            'messagesToday' => $messagesToday,
            'newLeadsThisWeek' => $newLeadsThisWeek,
            'pendingEscalations' => $pendingEscalations,
            'tenantName' => $tenant?->name ?? 'Dashboard',
        ]);
    }
}
