<?php

namespace Tests\Unit;

use App\Services\ColorExtractorService;
use GdImage;
use PHPUnit\Framework\TestCase;

class ColorExtractorServiceTest extends TestCase
{
    public function test_it_extracts_the_dominant_and_accent_colours(): void
    {
        $this->requireGd();

        $path = $this->twoToneImage();

        try {
            $colors = (new ColorExtractorService)->extractFromImage($path);
        } finally {
            @unlink($path);
        }

        $this->assertSame(['primary', 'secondary'], array_keys($colors));
        $this->assertSame('#c81e1e', $colors['primary']);
        $this->assertSame('#1e1ec8', $colors['secondary']);
    }

    public function test_near_white_and_near_black_pixels_are_ignored(): void
    {
        $this->requireGd();

        $path = $this->imageWithGd(function (GdImage $image): void {
            imagefilledrectangle($image, 0, 0, 39, 39, imagecolorallocate($image, 250, 250, 250));
            imagefilledrectangle($image, 0, 20, 39, 39, imagecolorallocate($image, 5, 5, 5));
            imagefilledrectangle($image, 0, 0, 19, 19, imagecolorallocate($image, 40, 120, 200));
        });

        try {
            $colors = (new ColorExtractorService)->extractFromImage($path);
        } finally {
            @unlink($path);
        }

        // The white and black areas carry no branding value, so the only
        // colour left standing is the mid-tone block.
        $this->assertSame('#2878c8', $colors['primary']);
        $this->assertSame('#2878c8', $colors['secondary']);
    }

    public function test_an_image_without_any_usable_colour_returns_an_empty_array(): void
    {
        $this->requireGd();

        $path = $this->imageWithGd(function (GdImage $image): void {
            imagefilledrectangle($image, 0, 0, 39, 39, imagecolorallocate($image, 255, 255, 255));
        });

        try {
            $colors = (new ColorExtractorService)->extractFromImage($path);
        } finally {
            @unlink($path);
        }

        $this->assertSame([], $colors);
    }

    public function test_a_corrupt_image_returns_an_empty_array(): void
    {
        $this->requireGd();

        $path = tempnam(sys_get_temp_dir(), 'qr-menu-corrupt').'.png';
        file_put_contents($path, 'this is not a real image');

        try {
            $colors = (new ColorExtractorService)->extractFromImage($path);
        } finally {
            @unlink($path);
        }

        $this->assertSame([], $colors);
    }

    public function test_a_missing_file_returns_an_empty_array(): void
    {
        $colors = (new ColorExtractorService)->extractFromImage(__DIR__.'/does-not-exist.png');

        $this->assertSame([], $colors);
    }

    public function test_without_gd_the_service_reports_unsupported_and_returns_nothing(): void
    {
        $service = new class extends ColorExtractorService
        {
            protected function gdAvailable(): bool
            {
                return false;
            }
        };

        $this->assertFalse($service->isSupported());
        $this->assertSame([], $service->extractFromImage(__FILE__));
    }

    /**
     * A half red, half blue square: the dominant colour is the first half and
     * the accent is the furthest well-sampled colour away from it.
     */
    private function twoToneImage(): string
    {
        return $this->imageWithGd(function (GdImage $image): void {
            imagefilledrectangle($image, 0, 0, 29, 59, imagecolorallocate($image, 200, 30, 30));
            imagefilledrectangle($image, 30, 0, 59, 59, imagecolorallocate($image, 30, 30, 200));
        });
    }

    /**
     * @param  callable(GdImage): void  $paint
     */
    private function imageWithGd(callable $paint): string
    {
        $this->requireGd();

        $image = imagecreatetruecolor(60, 60);

        $paint($image);

        $path = tempnam(sys_get_temp_dir(), 'qr-menu-palette').'.png';

        imagepng($image, $path);
        imagedestroy($image);

        return $path;
    }

    private function requireGd(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('The GD extension is required for colour extraction.');
        }
    }
}
