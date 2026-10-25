<?php

namespace Tests\Feature;

use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = User::factory()->create();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::auth()->login($this->tenant);
        Filament::setTenant($this->tenant);

        Filament::bootCurrentPanel();
    }

    /**
     * Filament's tenancy `creating` hook force-associates the current tenant, so
     * the tenant must be cleared to build records owned by a different user.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function createOrderFor(User $owner, array $attributes = []): Order
    {
        Filament::setTenant(null);

        try {
            return Order::factory()->create([
                ...$attributes,
                'user_id' => $owner->getKey(),
            ]);
        } finally {
            Filament::setTenant($this->tenant);
        }
    }

    public function test_the_list_only_shows_the_current_tenants_orders(): void
    {
        $ownOrder = $this->createOrderFor($this->tenant);
        $otherOrder = $this->createOrderFor(User::factory()->create());

        Livewire::test(ListOrders::class)
            ->assertCanSeeTableRecords([$ownOrder])
            ->assertCanNotSeeTableRecords([$otherOrder]);
    }

    public function test_the_view_and_update_actions_are_available_for_each_order(): void
    {
        $order = $this->createOrderFor($this->tenant, [
            'customer_name' => 'أحمد',
            'status' => 'preparing',
            'total' => 200,
        ]);

        OrderItem::factory()->for($order)->create([
            'item_name' => 'Kebab',
            'item_price' => 100,
            'quantity' => 2,
            'subtotal' => 200,
        ]);

        Livewire::test(ListOrders::class)
            ->assertActionExists(TestAction::make('view')->table($order))
            ->assertActionExists(TestAction::make('updateStatus')->table($order))
            ->assertSee($order->order_number)
            ->assertSee('أحمد')
            ->assertSee('200')
            ->assertSee('جاري التنفيذ');
    }

    public function test_updating_the_status_changes_the_order(): void
    {
        $order = $this->createOrderFor($this->tenant, ['status' => 'pending']);

        Livewire::test(ListOrders::class)
            ->callAction(TestAction::make('updateStatus')->table($order), data: [
                'status' => 'confirmed',
            ])
            ->assertHasNoActionErrors();

        $this->assertSame('confirmed', $order->refresh()->status);
    }

    public function test_the_list_can_be_filtered_by_status(): void
    {
        $pending = $this->createOrderFor($this->tenant, ['status' => 'pending']);
        $delivered = $this->createOrderFor($this->tenant, ['status' => 'delivered']);

        Livewire::test(ListOrders::class)
            ->filterTable('status', 'pending')
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$delivered]);
    }

    public function test_the_table_header_shows_todays_stats(): void
    {
        $this->createOrderFor($this->tenant, ['status' => 'pending', 'total' => 120]);

        Livewire::test(ListOrders::class)
            ->assertSee('طلبات اليوم')
            ->assertSee('طلبات في الانتظار')
            ->assertSee('إجمالي اليوم');
    }
}
