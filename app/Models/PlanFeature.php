<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanFeature extends Model
{
    /** @use HasFactory<\Database\Factories\PlanFeatureFactory> */
    use HasFactory;

    protected $fillable = [
        'plan_id',
        'feature_slug',
        'value',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isUnlimited(): bool
    {
        return $this->value === 'unlimited';
    }

    public function numericValue(): ?int
    {
        if ($this->isUnlimited()) {
            return null;
        }

        return (int) $this->value;
    }
}
