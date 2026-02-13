<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_super_admin',
        'current_tenant_id',
        'locale',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin;
    }

    public function currentTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'current_tenant_id');
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class)
            ->withPivot('role', 'is_default')
            ->withTimestamps();
    }

    public function defaultTenant(): ?Tenant
    {
        return $this->tenants()->wherePivot('is_default', true)->first();
    }

    public function switchToTenant(Tenant $tenant): void
    {
        $this->update(['current_tenant_id' => $tenant->id]);
    }

    public function exitToPlatform(): void
    {
        $defaultTenant = $this->defaultTenant();
        $this->update(['current_tenant_id' => $defaultTenant?->id]);
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }
}
