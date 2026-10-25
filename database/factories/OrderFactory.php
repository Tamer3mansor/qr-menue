<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
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
            'order_number' => 'ORD-'.Str::upper(fake()->unique()->bothify('????####')),
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->numerify('01#########'),
            'status' => 'pending',
            'notes' => fake()->optional()->sentence(),
            'total' => 0,
        ];
    }

    /**
     * Indicate that the order has reached a given status.
     */
    public function status(string $status): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => $status,
        ]);
    }
}
