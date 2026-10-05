<?php

namespace Database\Factories;

use App\Models\QrCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<QrCode>
 */
class QrCodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'url' => 'https://example.com/menu/'.Str::random(10),
            'image_path' => null,
        ];
    }
}
