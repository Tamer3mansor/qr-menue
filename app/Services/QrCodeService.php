<?php

namespace App\Services;

use App\Models\QrCode;
use App\Models\User;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use SimpleSoftwareIO\QrCode\Facades\QrCode as QrCodeGenerator;
use Throwable;

/**
 * Renders and stores the QR code that points at a tenant's public menu.
 */
class QrCodeService
{
    /**
     * The printed size is large enough to stay sharp on a phone screen.
     */
    public const SIZE = 400;

    public const MARGIN = 2;

    public function generateForTenant(User $user): QrCode
    {
        $url = $this->menuUrlFor($user);

        $path = $this->imagePathFor($user);

        $disk = Storage::disk(QrCode::DISK);
        $disk->makeDirectory(QrCode::DIRECTORY);

        $this->ensurePublicStorageLink();

        $disk->put($path, $this->renderPng($url));

        if (! $disk->exists($path)) {
            throw new RuntimeException("QR generation failed: [{$path}] was not written to the [".QrCode::DISK.'] disk.');
        }

        return QrCode::query()->updateOrCreate(
            ['user_id' => $user->getKey()],
            ['url' => $url, 'image_path' => $path],
        );
    }

    /**
     * simple-qrcode renders PNGs through Imagick, which is not installed on every
     * host. GD is always available with PHP, so it draws the same matrix when
     * Imagick is missing.
     */
    protected function renderPng(string $url): string
    {
        if (extension_loaded('imagick')) {
            return (string) QrCodeGenerator::format('png')
                ->size(self::SIZE)
                ->margin(self::MARGIN)
                ->errorCorrection('H')
                ->generate($url);
        }

        return $this->renderPngWithGd($url);
    }

    protected function renderPngWithGd(string $url): string
    {
        $matrix = Encoder::encode($url, ErrorCorrectionLevel::H())->getMatrix();

        $moduleCount = $matrix->getWidth();
        $totalModules = $moduleCount + (self::MARGIN * 2);
        $scale = self::SIZE / $totalModules;

        $image = imagecreatetruecolor(self::SIZE, self::SIZE);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);

        imagefilledrectangle($image, 0, 0, self::SIZE - 1, self::SIZE - 1, $white);

        for ($y = 0; $y < $moduleCount; $y++) {
            for ($x = 0; $x < $moduleCount; $x++) {
                if ($matrix->get($x, $y) !== 1) {
                    continue;
                }

                $left = (int) round(($x + self::MARGIN) * $scale);
                $top = (int) round(($y + self::MARGIN) * $scale);
                $right = (int) round(($x + self::MARGIN + 1) * $scale) - 1;
                $bottom = (int) round(($y + self::MARGIN + 1) * $scale) - 1;

                imagefilledrectangle($image, $left, $top, $right, $bottom, $black);
            }
        }

        ob_start();
        imagepng($image);
        $contents = (string) ob_get_clean();

        imagedestroy($image);

        return $contents;
    }

    /**
     * The generated file is served through the public symlink, so a missing
     * symlink turns every printed code into a 404. Creation is best effort —
     * on a read-only filesystem a clear warning beats a silent failure.
     */
    protected function ensurePublicStorageLink(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        $link = public_path('storage');

        if (is_link($link)) {
            if (! file_exists($link)) {
                Log::warning('The [public/storage] symlink exists but points at a missing directory; QR images will 404.');
            }

            return;
        }

        if (file_exists($link)) {
            return;
        }

        try {
            if (@symlink(Storage::disk(QrCode::DISK)->path(''), $link)) {
                return;
            }

            Log::warning('The [public/storage] symlink is missing and could not be created; QR images may 404. Run [php artisan storage:link].');
        } catch (Throwable $exception) {
            Log::warning('The [public/storage] symlink is missing and could not be created; QR images may 404.', [
                'exception' => $exception,
            ]);
        }
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
        return QrCode::DIRECTORY.'/'.$user->getKey().'.png';
    }
}
