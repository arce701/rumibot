<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Enums\PaymentProviderType;
use App\Models\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Subscription extends Model
{
    /** @use HasFactory<\Database\Factories\SubscriptionFactory> */
    use BelongsToTenant, HasFactory, HasUuids, LogsActivity, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'plan_price_id',
        'status',
        'payment_provider',
        'external_subscription_id',
        'external_customer_id',
        'trial_starts_at',
        'trial_ends_at',
        'current_period_starts_at',
        'current_period_ends_at',
        'canceled_at',
        'grace_period_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'payment_provider' => PaymentProviderType::class,
            'trial_starts_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'current_period_starts_at' => 'datetime',
            'current_period_ends_at' => 'datetime',
            'canceled_at' => 'datetime',
            'grace_period_ends_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty();
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function planPrice(): BelongsTo
    {
        return $this->belongsTo(PlanPrice::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(SubscriptionUsage::class);
    }

    public function paymentHistories(): HasMany
    {
        return $this->hasMany(PaymentHistory::class);
    }

    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::Active;
    }

    public function isTrialing(): bool
    {
        return $this->status === SubscriptionStatus::Trialing;
    }

    public function isCanceled(): bool
    {
        return $this->status === SubscriptionStatus::Canceled;
    }

    public function isInGracePeriod(): bool
    {
        return $this->isCanceled()
            && $this->grace_period_ends_at !== null
            && $this->grace_period_ends_at->isFuture();
    }

    public function hasAccessToFeatures(): bool
    {
        return $this->isActive()
            || $this->isTrialing()
            || $this->isInGracePeriod();
    }
}
