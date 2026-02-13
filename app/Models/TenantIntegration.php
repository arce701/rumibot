<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Enums\IntegrationProvider;
use App\Models\Enums\IntegrationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TenantIntegration extends Model
{
    /** @use HasFactory<\Database\Factories\TenantIntegrationFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'provider',
        'url',
        'secret',
        'events',
        'status',
        'is_primary',
        'last_triggered_at',
        'failure_count',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'provider' => IntegrationProvider::class,
            'status' => IntegrationStatus::class,
            'events' => 'array',
            'is_primary' => 'boolean',
            'secret' => 'encrypted',
            'metadata' => 'array',
            'last_triggered_at' => 'datetime',
            'failure_count' => 'integer',
        ];
    }

    public function isSubscribedToEvent(string $event): bool
    {
        return in_array($event, $this->events ?? []);
    }
}
