<?php

namespace App\Services;

use App\Models\QrCode;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode as QrCodeGenerator;

/**
 * Renders and stores the QR code that points at a tenant's public menu.
 */
class QrCodeService
{
    public function generateForTenant(User $user): QrCode
    {
        $url = $this->menuUrlFor($user);

        $path = $this->imagePathFor($user);

        $svg = QrCodeGenerator::format('svg')
            ->size(500)
            ->margin(2)
            ->errorCorrection('M')
            ->generate($url);

        Storage::disk(QrCode::DISK)->put($path, $svg);

        return QrCode::query()->updateOrCreate(
            ['user_id' => $user->getKey()],
            ['url' => $url, 'image_path' => $path],
        );
    }

    /**
     * The code always addresses the menu by tenant id.
     *
     * A printed code outlives any domain change, so encoding the id keeps every
     * code already sitting on a restaurant table valid. The domain still works
     * as an address, it is just never baked into the printed code.
     */
    public function menuUrlFor(User $user): string
    {
        return rtrim((string) config('app.url'), '/').'/menu/'.$user->getKey();
    }

    /**
     * The path is derived from the tenant, so regenerating overwrites the file
     * instead of accumulating copies.
     */
    public function imagePathFor(User $user): string
    {
        return QrCode::DIRECTORY.'/'.$user->getKey().'.svg';
    }
}
