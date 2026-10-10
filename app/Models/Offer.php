<?php

namespace App\Models;

use Database\Factories\OfferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['user_id', 'title', 'image', 'offer_price', 'is_active', 'expires_at'])]
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
     * The discount this offer represents, as a whole percentage.
     *
     * Against a specific item the item's own price is used. Without one the
     * combined price of every attached item is used, matching the struck-through
     * total the offer cards display.
     */
    public function discountPercentage(?Item $item = null): ?int
    {
        $referencePrice = (float) ($item?->price ?? $this->totalItemsPrice());

        if ($referencePrice <= 0) {
            return null;
        }

        return (int) round((($referencePrice - (float) $this->offer_price) / $referencePrice) * 100);
    }

    /**
     * The combined price of every attached item, the figure the offer cards
     * show struck through.
     */
    public function totalItemsPrice(): float
    {
        return (float) $this->items->sum('price');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (blank($this->image)) {
            return null;
        }

        return Storage::disk('public')->url($this->image);
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

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'offer_item');
    }
}
