<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Enums\ChannelType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Channel extends Model
{
    /** @use HasFactory<\Database\Factories\ChannelFactory> */
    use BelongsToTenant, HasFactory, HasUuids, LogsActivity, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'type',
        'provider_api_key',
        'provider_phone_number_id',
        'system_prompt_override',
        'ai_model_override',
        'is_active',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'type' => ChannelType::class,
            'provider_api_key' => 'encrypted',
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->logExcept(['provider_api_key']);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }
}
