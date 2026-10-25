<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->randomFloat(2, 1, 250);
        $quantity = fake()->numberBetween(1, 5);

        return [
            'order_id' => Order::factory(),
            'item_id' => null,
            'item_name' => fake()->sentence(3),
            'item_price' => $price,
            'quantity' => $quantity,
            'subtotal' => $price * $quantity,
        ];
    }

    /**
     * Indicate that the line still points at a live menu item.
     */
    public function forItem(Item $item): static
    {
        return $this->state(fn (array $attributes): array => [
            'item_id' => $item->getKey(),
        ]);
    }
}
