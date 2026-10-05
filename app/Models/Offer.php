<?php

namespace App\Models;

use Database\Factories\OfferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'item_id', 'title', 'offer_price', 'is_active', 'expires_at'])]
class Offer extends Model
{
    /** @use HasFactory<OfferFactory> */
    use HasFactory;

    /**
     * An offer whose expiry date has passed can never stay active, so the flag is
     * forced off on write and reported as false on read.
     */
    protected static function booted(): void
    {
        static::saving(function (self $offer): void {
            if ($offer->isExpired()) {
                $offer->is_active = false;
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'offer_price' => 'decimal:2',
            'expires_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return Attribute<bool, bool>
     */
    protected function isActive(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => (bool) ($this->attributes['is_active'] ?? false) && ! $this->isExpired(),
            set: fn (mixed $value) => $this->attributes['is_active'] = (bool) $value,
        );
    }

    /**
     * The discount this offer represents, as a whole percentage of the item price.
     */
    public function discountPercentage(): ?int
    {
        $itemPrice = (float) $this->item?->price;

        if ($itemPrice <= 0) {
            return null;
        }

        return (int) round((($itemPrice - (float) $this->offer_price) / $itemPrice) * 100);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeCurrentlyActive(Builder $query): void
    {
        $query
            ->where('is_active', true)
            ->where(fn (Builder $query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', now()));
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeCurrentlyInactive(Builder $query): void
    {
        $query->where(fn (Builder $query) => $query
            ->where('is_active', false)
            ->orWhere(fn (Builder $query) => $query
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now())));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
