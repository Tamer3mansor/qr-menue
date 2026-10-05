<?php

namespace App\Models;

use App\Observers\UserObserver;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

#[Fillable(['name', 'email', 'password', 'domain', 'is_active', 'subscription_expires_at'])]
#[Hidden(['password', 'remember_token'])]
#[ObservedBy(UserObserver::class)]
class User extends Authenticatable implements FilamentUser, HasDefaultTenant, HasTenants
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'subscription_expires_at' => 'datetime',
            'is_active' => 'boolean',
            'is_super_admin' => 'boolean',
        ];
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function qrCode(): HasOne
    {
        return $this->hasOne(QrCode::class);
    }

    public function settings(): HasOne
    {
        return $this->hasOne(Setting::class);
    }

    /**
     * Filament blocks every user outside the `local` environment unless the
     * model implements this contract, so it must be implemented explicitly.
     *
     * The two panels are strictly separated: a super admin reaches the
     * `super-admin` panel only, and a customer account reaches its own panel
     * only. Neither may cross into the other.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Cast defensively: a boolean cast returns null for an instance whose
        // attributes were never hydrated from the database.
        $isSuperAdmin = (bool) $this->is_super_admin;

        return match ($panel->getId()) {
            'super-admin' => $isSuperAdmin,
            default => ! $isSuperAdmin && (bool) $this->is_active,
        };
    }

    /**
     * A super admin is never a tenant, so they may not own or reach a tenant.
     */
    public function canAccessTenant(Model $tenant): bool
    {
        return ! $this->is_super_admin && $this->is($tenant);
    }

    /**
     * A deactivated account or a lapsed subscription hides the public menu.
     * A null expiry means the plan never lapses.
     */
    public function hasRunningSubscription(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return $this->subscription_expires_at === null
            || $this->subscription_expires_at->isFuture();
    }

    /**
     * A subscription lapsing soon is worth surfacing on the platform dashboard.
     */
    public function isExpiringSoon(int $withinDays = 30): bool
    {
        return $this->subscription_expires_at !== null
            && $this->subscription_expires_at->between(now(), now()->addDays($withinDays));
    }

    /**
     * Subscriptions still running but lapsing inside the given window. A null
     * expiry never lapses, and a lapsed one is no longer a renewal candidate.
     */
    public function scopeSubscriptionExpiringWithin(Builder $query, int $withinDays = 30): void
    {
        $query
            ->whereNotNull('subscription_expires_at')
            ->whereBetween('subscription_expires_at', [now(), now()->addDays($withinDays)]);
    }

    /**
     * @return Collection<int, static>
     */
    public function getTenants(Panel $panel): Collection
    {
        if ($this->is_super_admin) {
            return collect();
        }

        return collect([$this]);
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        return $this->is_super_admin ? null : $this;
    }
}
