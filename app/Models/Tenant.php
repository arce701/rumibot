<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Channel> $channels
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Conversation> $conversations
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Lead> $leads
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Escalation> $escalations
 * @property-read \Illuminate\Database\Eloquent\Collection<int, KnowledgeDocument> $knowledgeDocuments
 */

class Tenant extends Model
{
    /** @use HasFactory<\Database\Factories\TenantFactory> */
    use HasFactory, HasUuids, LogsActivity, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'system_prompt',
        'default_ai_provider',
        'default_ai_model',
        'timezone',
        'locale',
        'is_active',
        'is_platform_owner',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_platform_owner' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role', 'is_default')
            ->withTimestamps();
    }

    public function channels(): HasMany
    {
        return $this->hasMany(Channel::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function escalations(): HasMany
    {
        return $this->hasMany(Escalation::class);
    }

    public function knowledgeDocuments(): HasMany
    {
        return $this->hasMany(KnowledgeDocument::class);
    }

    public function integrations(): HasMany
    {
        return $this->hasMany(TenantIntegration::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): ?Subscription
    {
        return $this->subscriptions()
            ->whereIn('status', [
                Enums\SubscriptionStatus::Active,
                Enums\SubscriptionStatus::Trialing,
                Enums\SubscriptionStatus::Canceled,
            ])
            ->latest()
            ->first();
    }

    public function paymentHistories(): HasMany
    {
        return $this->hasMany(PaymentHistory::class);
    }
}
