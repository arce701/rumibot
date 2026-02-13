<?php

namespace App\Models;

use App\Models\Enums\BillingInterval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanPrice extends Model
{
    /** @use HasFactory<\Database\Factories\PlanPriceFactory> */
    use HasFactory;

    protected $fillable = [
        'plan_id',
        'billing_interval',
        'currency',
        'price_amount',
    ];

    protected function casts(): array
    {
        return [
            'billing_interval' => BillingInterval::class,
            'price_amount' => 'integer',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
