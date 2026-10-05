<?php

namespace App\Models;

use Database\Factories\QrCodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['user_id', 'url', 'image_path'])]
class QrCode extends Model
{
    /** @use HasFactory<QrCodeFactory> */
    use HasFactory;

    public const DISK = 'public';

    public const DIRECTORY = 'qrcodes';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The image is regenerated in place, so the path never becomes stale.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (blank($this->image_path)) {
            return null;
        }

        return Storage::disk(self::DISK)->url($this->image_path);
    }
}
