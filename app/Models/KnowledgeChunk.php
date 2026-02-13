<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeChunk extends Model
{
    /** @use HasFactory<\Database\Factories\KnowledgeChunkFactory> */
    use BelongsToTenant, HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'document_id',
        'tenant_id',
        'chunk_index',
        'content',
        'token_count',
        'embedding',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'chunk_index' => 'integer',
            'token_count' => 'integer',
            'embedding' => 'array',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(KnowledgeDocument::class, 'document_id');
    }
}
