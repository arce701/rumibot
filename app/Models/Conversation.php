<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Enums\ConversationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    /** @use HasFactory<\Database\Factories\ConversationFactory> */
    use BelongsToTenant, HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'channel_id',
        'contact_phone',
        'contact_name',
        'contact_country',
        'status',
        'current_intent',
        'metadata',
        'messages_count',
        'total_input_tokens',
        'total_output_tokens',
        'last_message_at',
        'ai_paused_until',
    ];

    protected function casts(): array
    {
        return [
            'status' => ConversationStatus::class,
            'metadata' => 'array',
            'messages_count' => 'integer',
            'total_input_tokens' => 'integer',
            'total_output_tokens' => 'integer',
            'last_message_at' => 'datetime',
            'ai_paused_until' => 'datetime',
        ];
    }

    public function isAiPaused(): bool
    {
        return $this->ai_paused_until !== null && $this->ai_paused_until->isFuture();
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
