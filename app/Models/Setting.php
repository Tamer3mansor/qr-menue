<?php

namespace App\Models;

use App\Observers\SettingObserver;
use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'user_id',
    'restaurant_name',
    'phone',
    'currency',
    'bg_image',
    'logo',
    'primary_color',
    'secondary_color',
    'hero_title',
    'hero_subtitle',
    'hero_image',
    'show_offers_ticker',
    'branches',
    'social_links',
    'seo_title',
    'seo_description',
    'seo_keywords',
    'primary_font',
])]
#[ObservedBy(SettingObserver::class)]
class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    public const DEFAULT_CURRENCY = 'ج.م';

    public const DISK = 'public';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'show_offers_ticker' => 'boolean',
            // Structured website settings are read as arrays, never as raw JSON.
            'branches' => 'array',
            'social_links' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Resolve the currency to display for a given tenant owner.
     */
    public static function currencyFor(?User $owner): string
    {
        return $owner?->settings?->currency ?? self::DEFAULT_CURRENCY;
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->fileUrl($this->logo);
    }

    public function getBgImageUrlAttribute(): ?string
    {
        return $this->fileUrl($this->bg_image);
    }

    public function getHeroImageUrlAttribute(): ?string
    {
        return $this->fileUrl($this->hero_image);
    }

    /**
     * The social networks the tenant actually filled in, in a stable order.
     *
     * @return array<string, string>
     */
    public function filledSocialLinks(): array
    {
        return array_filter((array) $this->social_links, 'filled');
    }

    /**
     * The branches the tenant filled in, in the order they were entered.
     *
     * @return list<array<string, mixed>>
     */
    public function branchesList(): array
    {
        return array_values((array) $this->branches);
    }

    protected function fileUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return Storage::disk(self::DISK)->url($path);
    }
}
