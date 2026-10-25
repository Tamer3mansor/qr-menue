<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $tenant;

    protected Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = User::factory()->create();

        Setting::factory()->create([
            'user_id' => $this->tenant->getKey(),
            'currency' => 'ج.م',
        ]);

        $category = Category::factory()->create(['user_id' => $this->tenant->getKey()]);

        $this->item = Item::factory()->hasAttached($category)->create([
            'user_id' => $this->tenant->getKey(),
            'title' => 'Kebab',
            'price' => 100,
        ]);
    }

    protected function cartKey(): string
    {
        return "cart_{$this->tenant->getKey()}";
    }

    protected function seedCart(int $quantity = 1): void
    {
        $price = (float) $this->item->price;

        $this->withSession([
            $this->cartKey() => [
                'items' => [
                    $this->item->getKey() => [
                        'id' => $this->item->getKey(),
                        'name' => $this->item->title,
                        'price' => $price,
                        'quantity' => $quantity,
                        'subtotal' => round($price * $quantity, 2),
                        'image' => null,
                    ],
                ],
                'total' => round($price * $quantity, 2),
                'count' => $quantity,
            ],
        ]);
    }

    public function test_adding_an_item_puts_it_in_the_cart(): void
    {
        $response = $this->postJson(route('menu.cart.add', ['slug' => $this->tenant->getKey()]), [
            'item_id' => $this->item->getKey(),
        ]);

        $response->assertOk()->assertJson([
            'count' => 1,
            'message' => 'تم الإضافة',
        ]);

        $cart = session($this->cartKey());

        $this->assertSame(1, $cart['items'][$this->item->getKey()]['quantity']);
        $this->assertSame(100.0, $cart['total']);
    }

    public function test_adding_the_same_item_twice_increases_its_quantity(): void
    {
        $this->postJson(route('menu.cart.add', ['slug' => $this->tenant->getKey()]), [
            'item_id' => $this->item->getKey(),
        ])->assertOk();

        $this->postJson(route('menu.cart.add', ['slug' => $this->tenant->getKey()]), [
            'item_id' => $this->item->getKey(),
        ])->assertOk()->assertJsonPath('count', 2);

        $cart = session($this->cartKey());

        $this->assertSame(2, $cart['items'][$this->item->getKey()]['quantity']);
        $this->assertSame(200.0, $cart['total']);
    }

    public function test_adding_another_tenants_item_is_forbidden(): void
    {
        $foreignItem = Item::factory()->create([
            'user_id' => User::factory()->create()->getKey(),
        ]);

        $this->postJson(route('menu.cart.add', ['slug' => $this->tenant->getKey()]), [
            'item_id' => $foreignItem->getKey(),
        ])->assertForbidden();

        $this->assertNull(session($this->cartKey()));
    }

    public function test_the_site_prefix_uses_the_same_cart(): void
    {
        $this->postJson(route('site.cart.add', ['slug' => $this->tenant->getKey()]), [
            'item_id' => $this->item->getKey(),
        ])->assertOk()->assertJsonPath('count', 1);

        $this->getJson(route('site.cart', ['slug' => $this->tenant->getKey()]))
            ->assertOk()
            ->assertJsonPath('count', 1);
    }

    public function test_adding_to_the_cart_from_the_site_route_stores_the_item(): void
    {
        $this->postJson(route('site.cart.add', ['slug' => $this->tenant->getKey()]), [
            'item_id' => $this->item->getKey(),
        ])->assertOk()
            ->assertJson([
                'count' => 1,
                'message' => 'تم الإضافة',
            ]);

        $cart = session($this->cartKey());

        $this->assertSame(1, $cart['items'][$this->item->getKey()]['quantity']);
        $this->assertSame(100.0, $cart['total']);
    }

    public function test_updating_the_quantity_to_zero_removes_the_item(): void
    {
        $this->seedCart(quantity: 2);

        $this->postJson(route('menu.cart.update', ['slug' => $this->tenant->getKey()]), [
            'item_id' => $this->item->getKey(),
            'quantity' => 0,
        ])->assertOk()
            ->assertJsonPath('cart.count', 0)
            ->assertJsonPath('total', 0);

        $cart = session($this->cartKey());

        $this->assertSame([], $cart['items']);
        $this->assertSame(0.0, $cart['total']);
    }

    public function test_updating_another_tenants_item_is_forbidden(): void
    {
        $foreignItem = Item::factory()->create([
            'user_id' => User::factory()->create()->getKey(),
        ]);

        $this->postJson(route('menu.cart.update', ['slug' => $this->tenant->getKey()]), [
            'item_id' => $foreignItem->getKey(),
            'quantity' => 2,
        ])->assertForbidden();
    }

    public function test_submitting_an_order_creates_the_order_and_its_items(): void
    {
        $this->seedCart(quantity: 2);

        $response = $this->postJson(route('menu.order.submit', ['slug' => $this->tenant->getKey()]), [
            'customer_name' => 'أحمد',
            'customer_phone' => '01000000000',
            'notes' => 'بدون بصل',
        ]);

        $response->assertOk()
            ->assertJsonPath('customer_name', 'أحمد')
            ->assertJsonPath('customer_phone', '01000000000')
            ->assertJsonPath('total', 200)
            ->assertJsonStructure(['order_number', 'message']);

        $order = Order::query()->firstOrFail();

        $this->assertSame($this->tenant->getKey(), $order->user_id);
        $this->assertSame('pending', $order->status);
        $this->assertSame(200.0, $order->total);
        $this->assertSame('بدون بصل', $order->notes);

        $line = $order->items()->firstOrFail();

        $this->assertSame($this->item->getKey(), $line->item_id);
        $this->assertSame('Kebab', $line->item_name);
        $this->assertSame(2, $line->quantity);
        $this->assertSame(100.0, $line->item_price);
        $this->assertSame(200.0, $line->subtotal);
    }

    public function test_submitting_an_order_clears_the_cart(): void
    {
        $this->seedCart();

        $this->postJson(route('menu.order.submit', ['slug' => $this->tenant->getKey()]), [
            'customer_name' => 'أحمد',
            'customer_phone' => '01000000000',
        ])->assertOk();

        $this->assertNull(session($this->cartKey()));
    }

    public function test_an_empty_cart_cannot_be_submitted(): void
    {
        $this->postJson(route('menu.order.submit', ['slug' => $this->tenant->getKey()]), [
            'customer_name' => 'أحمد',
            'customer_phone' => '01000000000',
        ])->assertStatus(422)
            ->assertJson(['message' => 'السلة فارغة، أضف أصنافاً أولاً.']);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_generated_order_numbers_are_unique(): void
    {
        $first = Order::generateOrderNumber($this->tenant->getKey());
        $second = Order::generateOrderNumber($this->tenant->getKey());

        $this->assertNotSame($first, $second);
        $this->assertMatchesRegularExpression('/^ORD-[A-F0-9]{8}$/', $first);
        $this->assertMatchesRegularExpression('/^ORD-[A-F0-9]{8}$/', $second);
    }

    public function test_tracking_an_order_returns_its_details(): void
    {
        $order = $this->orderFor($this->tenant, [
            'customer_name' => 'سارة',
            'status' => 'preparing',
            'total' => 150,
        ], quantity: 2, price: 75);

        $this->getJson(route('menu.order.track', [
            'slug' => $this->tenant->getKey(),
            'order_number' => $order->order_number,
        ]))
            ->assertOk()
            ->assertJson([
                'order_number' => $order->order_number,
                'status' => 'preparing',
                'status_label' => 'جاري التنفيذ',
                'customer_name' => 'سارة',
                'total' => 150,
            ])
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.name', 'Kebab')
            ->assertJsonPath('items.0.quantity', 2);
    }

    public function test_tracking_an_unknown_order_number_returns_a_404(): void
    {
        $this->getJson(route('menu.order.track', [
            'slug' => $this->tenant->getKey(),
            'order_number' => 'ORD-NOTFOUND',
        ]))->assertNotFound()
            ->assertJson(['message' => 'لم نجد طلباً بهذا الرقم.']);
    }

    public function test_tracking_another_tenants_order_returns_a_404(): void
    {
        $otherTenant = User::factory()->create();

        $order = $this->orderFor($otherTenant, ['order_number' => 'ORD-FOREIGN1']);

        $this->getJson(route('menu.order.track', [
            'slug' => $this->tenant->getKey(),
            'order_number' => $order->order_number,
        ]))->assertNotFound();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function orderFor(User $tenant, array $attributes = [], int $quantity = 1, float $price = 100): Order
    {
        $order = Order::factory()->for($tenant)->create([
            'order_number' => Order::generateOrderNumber($tenant->getKey()),
            ...$attributes,
        ]);

        OrderItem::factory()->for($order)->create([
            'item_id' => $this->item->getKey(),
            'item_name' => 'Kebab',
            'item_price' => $price,
            'quantity' => $quantity,
            'subtotal' => round($price * $quantity, 2),
        ]);

        return $order;
    }
}
