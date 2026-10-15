<?php

namespace App\Services;

use GdImage;
use Throwable;

/**
 * Reads a palette out of an image using PHP's GD extension, so a restaurant
 * can brand its menu from the logo it already has without any external service.
 */
class ColorExtractorService
{
    /**
     * Walking every pixel is wasteful on large uploads; sampling every fifth
     * pixel keeps the palette stable while staying fast.
     */
    protected const SAMPLE_STEP = 5;

    /**
     * Near-white and near-black pixels carry no branding value.
     */
    protected const MIN_BRIGHTNESS = 15;

    protected const MAX_BRIGHTNESS = 240;

    /**
     * A colour needs at least this share of the sampled pixels to be a candidate
     * for the accent, so single-pixel noise cannot win.
     */
    protected const MIN_ACCENT_SHARE = 0.02;

    public function isSupported(): bool
    {
        return $this->gdAvailable() && function_exists('imagecreatefromstring');
    }

    /**
     * @return array{primary: string, secondary: string}
     */
    public function extractFromImage(string $imagePath): array
    {
        try {
            if (! $this->isSupported() || ! is_file($imagePath)) {
                return [];
            }

            $contents = file_get_contents($imagePath);

            if (! is_string($contents) || $contents === '') {
                return [];
            }

            $image = @imagecreatefromstring($contents);

            if (! $image instanceof GdImage) {
                return [];
            }

            try {
                $buckets = $this->sampleColors($image);

                $dominant = $this->dominantColor($buckets);

                if ($dominant === null) {
                    return [];
                }

                $accent = $this->accentColor($buckets, $dominant) ?? $dominant;

                return [
                    'primary' => $this->toHex($dominant[0], $dominant[1], $dominant[2]),
                    'secondary' => $this->toHex($accent[0], $accent[1], $accent[2]),
                ];
            } finally {
                imagedestroy($image);
            }
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Pixels are averaged inside 4-bit-per-channel buckets: exact colours are
     * too noisy to rank, but the bucket a colour lands in is stable.
     *
     * @return array<int, array{count: int, r: int, g: int, b: int}>
     */
    protected function sampleColors(GdImage $image): array
    {
        if (! imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $buckets = [];

        for ($y = 0; $y < $height; $y += self::SAMPLE_STEP) {
            for ($x = 0; $x < $width; $x += self::SAMPLE_STEP) {
                $rgba = imagecolorat($image, $x, $y);

                if (((($rgba >> 24) & 0x7F) === 127)) {
                    continue;
                }

                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;

                $brightness = intdiv($r + $g + $b, 3);

                if ($brightness > self::MAX_BRIGHTNESS || $brightness < self::MIN_BRIGHTNESS) {
                    continue;
                }

                $key = (($r >> 4) << 8) | (($g >> 4) << 4) | ($b >> 4);

                $bucket = $buckets[$key] ?? ['count' => 0, 'r' => 0, 'g' => 0, 'b' => 0];
                $bucket['count']++;
                $bucket['r'] += $r;
                $bucket['g'] += $g;
                $bucket['b'] += $b;

                $buckets[$key] = $bucket;
            }
        }

        return $buckets;
    }

    /**
     * @param  array<int, array{count: int, r: int, g: int, b: int}>  $buckets
     * @return array{int, int, int}|null
     */
    protected function dominantColor(array $buckets): ?array
    {
        $dominant = null;

        foreach ($buckets as $bucket) {
            if ($dominant === null || $bucket['count'] > $dominant['count']) {
                $dominant = $bucket;
            }
        }

        if ($dominant === null) {
            return null;
        }

        return [
            intdiv($dominant['r'], $dominant['count']),
            intdiv($dominant['g'], $dominant['count']),
            intdiv($dominant['b'], $dominant['count']),
        ];
    }

    /**
     * The accent is the well-sampled colour furthest away from the dominant one,
     * measured as Euclidean distance in the RGB cube.
     *
     * @param  array<int, array{count: int, r: int, g: int, b: int}>  $buckets
     * @param  array{int, int, int}  $dominant
     * @return array{int, int, int}|null
     */
    protected function accentColor(array $buckets, array $dominant): ?array
    {
        $total = array_sum(array_column($buckets, 'count'));
        $minimumCount = max(1, (int) ceil($total * self::MIN_ACCENT_SHARE));

        $accent = null;
        $furthest = -1;

        foreach ($buckets as $bucket) {
            if ($bucket['count'] < $minimumCount) {
                continue;
            }

            $r = intdiv($bucket['r'], $bucket['count']);
            $g = intdiv($bucket['g'], $bucket['count']);
            $b = intdiv($bucket['b'], $bucket['count']);

            $distance = ($r - $dominant[0]) ** 2
                + ($g - $dominant[1]) ** 2
                + ($b - $dominant[2]) ** 2;

            if ($distance > $furthest) {
                $furthest = $distance;
                $accent = [$r, $g, $b];
            }
        }

        return $accent;
    }

    protected function toHex(int $r, int $g, int $b): string
    {
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    protected function gdAvailable(): bool
    {
        return extension_loaded('gd');
    }
}
