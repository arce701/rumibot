<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Lead extends Model
{
    /** @use HasFactory<\Database\Factories\LeadFactory> */
    use BelongsToTenant, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'conversation_id',
        'full_name',
        'country',
        'phone',
        'email',
        'company_name',
        'interests',
        'qualification_score',
        'status',
        'notes',
        'converted_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => LeadStatus::class,
            'interests' => 'array',
            'qualification_score' => 'integer',
            'converted_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['full_name', 'email', 'company_name', 'status', 'qualification_score', 'notes'])
            ->logOnlyDirty();
    }
}
