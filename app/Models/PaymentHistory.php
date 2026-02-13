<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Enums\PaymentProviderType;
use App\Models\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentHistory extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentHistoryFactory> */
    use BelongsToTenant, HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'payment_provider',
        'external_payment_id',
        'status',
        'amount',
        'currency',
        'description',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'payment_provider' => PaymentProviderType::class,
            'status' => PaymentStatus::class,
            'amount' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
