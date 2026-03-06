<?php

namespace App\Exports;

use App\Models\Lead;
use App\Support\PhoneHelper;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LeadsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        private string $statusFilter = '',
        private string $search = '',
    ) {}

    public function query(): Builder
    {
        return Lead::query()
            ->with('conversation.channel')
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $q) {
                    $q->where('full_name', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhere('company_name', 'like', "%{$this->search}%");
                });
            })
            ->when($this->statusFilter, fn (Builder $query) => $query->where('status', $this->statusFilter))
            ->latest();
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Name',
            'Phone',
            'Email',
            'Company',
            'Status',
            'Score',
            'Channel',
            'Created At',
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public function map(mixed $row): array
    {
        return [
            $row->full_name,
            PhoneHelper::format($row->phone),
            $row->email,
            $row->company_name,
            $row->status->value,
            $row->qualification_score,
            $row->conversation?->channel?->name,
            $row->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
