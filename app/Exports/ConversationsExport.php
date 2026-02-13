<?php

namespace App\Exports;

use App\Models\Conversation;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ConversationsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        private string $channelFilter = '',
        private string $statusFilter = '',
        private string $search = '',
    ) {}

    public function query(): Builder
    {
        return Conversation::query()
            ->with('channel')
            ->when($this->search, function (Builder $query) {
                $query->where(function (Builder $q) {
                    $q->where('contact_phone', 'like', "%{$this->search}%")
                        ->orWhere('contact_name', 'like', "%{$this->search}%");
                });
            })
            ->when($this->channelFilter, fn (Builder $query) => $query->where('channel_id', $this->channelFilter))
            ->when($this->statusFilter, fn (Builder $query) => $query->where('status', $this->statusFilter))
            ->latest('last_message_at');
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Contact Name',
            'Phone',
            'Channel',
            'Status',
            'Messages Count',
            'Tokens',
            'Last Activity',
            'Created At',
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public function map(mixed $row): array
    {
        return [
            $row->contact_name,
            $row->contact_phone,
            $row->channel?->name,
            $row->status->value,
            $row->messages_count,
            $row->total_input_tokens + $row->total_output_tokens,
            $row->last_message_at?->format('Y-m-d H:i:s'),
            $row->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
