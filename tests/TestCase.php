<?php

namespace Tests;

use App\Models\QrCode;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Uploads and generated images must never touch the real filesystem.
        Storage::fake(QrCode::DISK);
    }
}
