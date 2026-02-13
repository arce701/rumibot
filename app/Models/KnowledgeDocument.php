<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class KnowledgeDocument extends Model
{
    /** @use HasFactory<\Database\Factories\KnowledgeDocumentFactory> */
    use BelongsToTenant, HasFactory, HasUuids, LogsActivity, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'title',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'status',
        'error_message',
        'total_chunks',
        'channel_scope',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => DocumentStatus::class,
            'file_size' => 'integer',
            'total_chunks' => 'integer',
            'channel_scope' => 'array',
            'metadata' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'status', 'channel_scope', 'total_chunks'])
            ->logOnlyDirty();
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(KnowledgeChunk::class, 'document_id');
    }

    public function isProcessable(): bool
    {
        return in_array($this->status, [DocumentStatus::Pending, DocumentStatus::Failed]);
    }
}
