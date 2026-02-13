<?php

namespace App\Livewire\ActivityLog;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

#[Layout('layouts::app')]
#[Title('Activity Log')]
class ActivityLogViewer extends Component
{
    use AuthorizesRequests, WithPagination;

    #[Url]
    public string $subjectTypeFilter = '';

    public function updatedSubjectTypeFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $this->authorize('settings.view');

        $tenant = auth()->user()->currentTenant;

        $tenantModelTypes = [
            'App\\Models\\Tenant',
            'App\\Models\\Channel',
            'App\\Models\\Lead',
            'App\\Models\\KnowledgeDocument',
        ];

        $tenantUserIds = $tenant->users()->pluck('users.id');

        $activities = Activity::query()
            ->where(function ($query) use ($tenant, $tenantUserIds) {
                $query->where(function ($q) use ($tenant) {
                    $q->where('subject_type', 'App\\Models\\Tenant')
                        ->where('subject_id', $tenant->id);
                })->orWhere(function ($q) use ($tenantUserIds) {
                    $q->where('causer_type', 'App\\Models\\User')
                        ->whereIn('causer_id', $tenantUserIds);
                });
            })
            ->when($this->subjectTypeFilter, fn ($query) => $query->where('subject_type', $this->subjectTypeFilter))
            ->with('causer')
            ->latest()
            ->paginate(30);

        return view('livewire.activity-log.activity-log-viewer', [
            'activities' => $activities,
            'subjectTypes' => $tenantModelTypes,
        ]);
    }
}
