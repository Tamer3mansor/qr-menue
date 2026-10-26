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

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function orderPayload(array $overrides = []): array
    {
        return [
            'customer_name' => 'أحمد',
            'customer_phone' => '01000000000',
            'delivery_type' => 'takeaway',
            ...$overrides,
        ];
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

        $response = $this->postJson(route('menu.order.submit', ['slug' => $this->tenant->getKey()]), $this->orderPayload([
            'notes' => 'بدون بصل',
        ]));

        $response->assertOk()
            ->assertJsonPath('customer_name', 'أحمد')
            ->assertJsonPath('customer_phone', '01000000000')
            ->assertJsonPath('order_number', 1)
            ->assertJsonPath('order_number_display', '#1')
            ->assertJsonPath('total', 200)
            ->assertJsonStructure(['order_number', 'order_number_display', 'message']);

        $order = Order::query()->firstOrFail();

        $this->assertSame($this->tenant->getKey(), $order->user_id);
        $this->assertSame(1, $order->tenant_order_number);
        $this->assertSame('pending', $order->status);
        $this->assertSame('takeaway', $order->delivery_type);
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

        $this->postJson(route('menu.order.submit', ['slug' => $this->tenant->getKey()]), $this->orderPayload())
            ->assertOk();

        $this->assertNull(session($this->cartKey()));
    }

    public function test_an_empty_cart_cannot_be_submitted(): void
    {
        $this->postJson(route('menu.order.submit', ['slug' => $this->tenant->getKey()]), $this->orderPayload())
            ->assertStatus(422)
            ->assertJson(['message' => 'طلبك فارغ، أضف أصنافاً أولاً.']);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_delivery_type_is_required(): void
    {
        $this->seedCart();

        $this->postJson(route('menu.order.submit', ['slug' => $this->tenant->getKey()]), [
            'customer_name' => 'أحمد',
            'customer_phone' => '01000000000',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['delivery_type']);
    }

    public function test_tenant_order_number_starts_at_one(): void
    {
        $this->seedCart();

        $this->postJson(route('menu.order.submit', ['slug' => $this->tenant->getKey()]), $this->orderPayload())
            ->assertOk()
            ->assertJsonPath('order_number', 1);

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->tenant->getKey(),
            'tenant_order_number' => 1,
        ]);
    }

    public function test_tenant_order_number_increments_for_each_order(): void
    {
        $this->seedCart();

        $this->postJson(route('menu.order.submit', ['slug' => $this->tenant->getKey()]), $this->orderPayload())
            ->assertOk()
            ->assertJsonPath('order_number', 1);

        $this->seedCart();

        $this->postJson(route('menu.order.submit', ['slug' => $this->tenant->getKey()]), $this->orderPayload())
            ->assertOk()
            ->assertJsonPath('order_number', 2);

        $this->assertSame([1, 2], Order::query()->orderBy('id')->pluck('tenant_order_number')->all());
    }

    public function test_tenant_order_numbers_are_unique_per_tenant(): void
    {
        $this->seedCart();

        $this->postJson(route('menu.order.submit', ['slug' => $this->tenant->getKey()]), $this->orderPayload())
            ->assertOk();

        $this->seedCart();

        $this->postJson(route('menu.order.submit', ['slug' => $this->tenant->getKey()]), $this->orderPayload())
            ->assertOk();

        $numbers = Order::query()
            ->where('user_id', $this->tenant->getKey())
            ->pluck('tenant_order_number');

        $this->assertSame($numbers->count(), $numbers->unique()->count());
    }

    public function test_tenant_order_numbers_are_independent_between_tenants(): void
    {
        Order::factory()->for($this->tenant)->create(['tenant_order_number' => 1]);

        $otherTenant = User::factory()->create();

        $this->assertSame(1, Order::generateOrderNumber($otherTenant->getKey()));
        $this->assertSame(2, Order::generateOrderNumber($this->tenant->getKey()));
    }

    public function test_a_single_branch_is_selected_automatically(): void
    {
        $this->tenant->settings->update(['branches' => [
            ['branch_name' => 'الفرع الرئيسي', 'address' => 'شارع 9', 'phone' => null],
        ]]);

        $this->seedCart();

        $this->postJson(route('menu.order.submit', ['slug' => $this->tenant->getKey()]), $this->orderPayload())
            ->assertOk();

        $this->assertSame(0, Order::query()->firstOrFail()->branch_id);
    }

    public function test_a_branch_is_required_when_more_than_one_exists(): void
    {
        $this->tenant->settings->update(['branches' => [
            ['branch_name' => 'فرع المعادي', 'address' => 'شارع 9', 'phone' => null],
            ['branch_name' => 'فرع مدينة نصر', 'address' => 'شارع عباس', 'phone' => null],
        ]]);

        $this->seedCart();

        $this->postJson(route('menu.order.submit', ['slug' => $this->tenant->getKey()]), $this->orderPayload())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['branch_id']);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_the_selected_branch_is_stored(): void
    {
        $this->tenant->settings->update(['branches' => [
            ['branch_name' => 'فرع المعادي', 'address' => 'شارع 9', 'phone' => null],
            ['branch_name' => 'فرع مدينة نصر', 'address' => 'شارع عباس', 'phone' => null],
        ]]);

        $this->seedCart();

        $this->postJson(route('menu.order.submit', ['slug' => $this->tenant->getKey()]), $this->orderPayload([
            'branch_id' => 1,
        ]))->assertOk();

        $this->assertSame(1, Order::query()->firstOrFail()->branch_id);
    }

    public function test_a_branch_outside_the_configured_list_is_rejected(): void
    {
        $this->tenant->settings->update(['branches' => [
            ['branch_name' => 'فرع المعادي', 'address' => 'شارع 9', 'phone' => null],
            ['branch_name' => 'فرع مدينة نصر', 'address' => 'شارع عباس', 'phone' => null],
        ]]);

        $this->seedCart();

        $this->postJson(route('menu.order.submit', ['slug' => $this->tenant->getKey()]), $this->orderPayload([
            'branch_id' => 5,
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['branch_id']);
    }

    public function test_delivery_requires_an_address(): void
    {
        $this->seedCart();

        $this->postJson(route('menu.order.submit', ['slug' => $this->tenant->getKey()]), $this->orderPayload([
            'delivery_type' => 'delivery',
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['delivery_address']);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_a_delivery_order_stores_its_address(): void
    {
        $this->seedCart();

        $this->postJson(route('menu.order.submit', ['slug' => $this->tenant->getKey()]), $this->orderPayload([
            'delivery_type' => 'delivery',
            'delivery_address' => 'شارع 9، المعادي',
        ]))->assertOk()
            ->assertJsonPath('delivery_type', 'delivery')
            ->assertJsonPath('delivery_address', 'شارع 9، المعادي');

        $order = Order::query()->firstOrFail();

        $this->assertSame('delivery', $order->delivery_type);
        $this->assertSame('شارع 9، المعادي', $order->delivery_address);
    }

    public function test_tracking_an_order_returns_its_details(): void
    {
        $order = $this->orderFor($this->tenant, [
            'tenant_order_number' => 47,
            'customer_name' => 'سارة',
            'status' => 'preparing',
            'total' => 150,
        ], quantity: 2, price: 75);

        $this->getJson(route('menu.order.track', [
            'slug' => $this->tenant->getKey(),
            'order_number' => 47,
        ]))
            ->assertOk()
            ->assertJson([
                'order_number' => 47,
                'order_number_display' => '#47',
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
            'order_number' => 999999,
        ]))->assertNotFound()
            ->assertJson(['message' => 'لم نجد طلباً بهذا الرقم.']);
    }

    public function test_tracking_another_tenants_order_returns_a_404(): void
    {
        $otherTenant = User::factory()->create();

        $this->orderFor($otherTenant, ['tenant_order_number' => 1]);

        $this->getJson(route('menu.order.track', [
            'slug' => $this->tenant->getKey(),
            'order_number' => 1,
        ]))->assertNotFound();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function orderFor(User $tenant, array $attributes = [], int $quantity = 1, float $price = 100): Order
    {
        $order = Order::factory()->for($tenant)->create([
            'tenant_order_number' => Order::generateOrderNumber($tenant->getKey()),
            'delivery_type' => 'takeaway',
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
