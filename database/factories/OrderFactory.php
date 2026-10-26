<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'tenant_order_number' => fake()->unique()->numberBetween(1, 1_000_000),
            'branch_id' => null,
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->numerify('01#########'),
            'status' => 'pending',
            'notes' => fake()->optional()->sentence(),
            'delivery_type' => 'takeaway',
            'delivery_address' => null,
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

    /**
     * Indicate that the order should be delivered to an address.
     */
    public function delivery(?string $address = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'delivery_type' => 'delivery',
            'delivery_address' => $address ?? fake()->address(),
        ]);
    }
}
