<?php

namespace Database\Factories;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
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
            'restaurant_name' => fake()->company(),
            'phone' => fake()->phoneNumber(),
            'currency' => Setting::DEFAULT_CURRENCY,
            'bg_image' => null,
            'logo' => null,
            'primary_color' => fake()->hexColor(),
            'secondary_color' => fake()->hexColor(),
            'hero_title' => null,
            'hero_subtitle' => null,
            'hero_image' => null,
            'show_offers_ticker' => true,
            'branches' => null,
            'social_links' => null,
            'seo_title' => null,
            'seo_description' => null,
            'seo_keywords' => null,
            'primary_font' => 'Cairo',
        ];
    }

    /**
     * Indicate that the menu runs without the offers ticker.
     */
    public function withoutOffersTicker(): static
    {
        return $this->state(fn (array $attributes) => [
            'show_offers_ticker' => false,
        ]);
    }
}
