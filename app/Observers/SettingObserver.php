<?php

namespace App\Observers;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Removes files from disk once the database no longer references them, so that
 * replacing, clearing or deleting a setting does not leave orphaned files behind.
 */
class SettingObserver
{
    /**
     * Every attribute that stores an uploaded file path.
     *
     * @var list<string>
     */
    protected array $fileAttributes = ['logo', 'bg_image', 'hero_image'];

    /**
     * Runs after the record is written, unlike `saving`, so a failed update
     * cannot point the database at a file that was already removed.
     */
    public function saved(Model $setting): void
    {
        foreach ($this->fileAttributes as $attribute) {
            if (! $setting->wasChanged($attribute)) {
                continue;
            }

            $this->deleteFile($setting->getRawOriginal($attribute));
        }
    }

    /**
     * Deleting a user cascades to its settings, so the uploads have to go too.
     */
    public function deleted(Model $setting): void
    {
        foreach ($this->fileAttributes as $attribute) {
            $this->deleteFile($setting->getRawOriginal($attribute));
        }
    }

    protected function deleteFile(mixed $path): void
    {
        if (blank($path) || ! is_string($path)) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
