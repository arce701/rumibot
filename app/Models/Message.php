<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    /** @use HasFactory<\Database\Factories\MessageFactory> */
    use BelongsToTenant, HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'conversation_id',
        'tenant_id',
        'role',
        'content',
        'tokens_input',
        'tokens_output',
        'model_used',
        'response_time_ms',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'tokens_input' => 'integer',
            'tokens_output' => 'integer',
            'response_time_ms' => 'integer',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
