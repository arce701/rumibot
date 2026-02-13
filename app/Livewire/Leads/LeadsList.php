<?php

namespace App\Livewire\Leads;

use App\Exports\LeadsExport;
use App\Models\Enums\LeadStatus;
use App\Models\Lead;
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
#[Title('Leads')]
class LeadsList extends Component
{
    use AuthorizesRequests, WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    public ?string $editingLeadId = null;

    public string $editStatus = '';

    public string $editNotes = '';

    public ?int $editScore = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function startEdit(string $leadId): void
    {
        $this->authorize('leads.update');

        $lead = Lead::findOrFail($leadId);
        $this->editingLeadId = $lead->id;
        $this->editStatus = $lead->status->value;
        $this->editNotes = $lead->notes ?? '';
        $this->editScore = $lead->qualification_score;
    }

    public function updateLead(): void
    {
        $this->authorize('leads.update');

        $lead = Lead::findOrFail($this->editingLeadId);

        $lead->update([
            'status' => $this->editStatus,
            'notes' => $this->editNotes ?: null,
            'qualification_score' => $this->editScore,
            'converted_at' => $this->editStatus === LeadStatus::Converted->value ? now() : $lead->converted_at,
        ]);

        $this->reset(['editingLeadId', 'editStatus', 'editNotes', 'editScore']);

        session()->flash('message', __('Lead updated successfully.'));
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingLeadId', 'editStatus', 'editNotes', 'editScore']);
    }

    public function exportLeads(): ?BinaryFileResponse
    {
        if (! Feature::active('data-export')) {
            session()->flash('error', __('Export not available on your plan.'));

            return null;
        }

        return Excel::download(
            new LeadsExport($this->statusFilter, $this->search),
            'leads-'.now()->format('Y-m-d').'.xlsx',
        );
    }

    public function render(): View
    {
        $this->authorize('leads.view');

        $leads = Lead::query()
            ->with('conversation.channel')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('full_name', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhere('company_name', 'like', "%{$this->search}%");
                });
            })
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->latest()
            ->paginate(20);

        return view('livewire.leads.leads-list', [
            'leads' => $leads,
            'statuses' => LeadStatus::cases(),
        ]);
    }
}
