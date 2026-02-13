<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionUsage extends Model
{
    /** @use HasFactory<\Database\Factories\SubscriptionUsageFactory> */
    use BelongsToTenant, HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'feature_slug',
        'used',
        'period_starts_at',
        'period_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'used' => 'integer',
            'period_starts_at' => 'datetime',
            'period_ends_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
