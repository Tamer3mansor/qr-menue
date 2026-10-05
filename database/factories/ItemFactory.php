<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
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
            'category_id' => fn (array $attributes): int => Category::factory()
                ->create(['user_id' => $attributes['user_id']])
                ->getKey(),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'price' => fake()->randomFloat(2, 1, 500),
            'image' => null,
            'is_available' => true,
            'is_featured' => false,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    /**
     * Indicate that the item is currently sold out.
     */
    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_available' => false,
        ]);
    }

    /**
     * Indicate that the item is highlighted on the public menu.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }
}
