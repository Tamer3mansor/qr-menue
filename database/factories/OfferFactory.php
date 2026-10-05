<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offer>
 */
class OfferFactory extends Factory
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
            'item_id' => fn (array $attributes): int => Item::factory()
                ->create(['user_id' => $attributes['user_id']])
                ->getKey(),
            'title' => fake()->optional()->sentence(3),
            'offer_price' => fake()->randomFloat(2, 1, 500),
            'is_active' => true,
            'expires_at' => null,
        ];
    }

    /**
     * Indicate that the offer is not currently running.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the offer has already expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => fake()->dateTimeBetween('-1 year', '-1 day'),
        ]);
    }
}
