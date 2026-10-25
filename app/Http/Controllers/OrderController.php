<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Order;
use App\Models\User;
use App\Services\CartService;
use App\Traits\ResolvesTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    use ResolvesTenant;

    public function __construct(protected CartService $cart) {}

    public function addToCart(Request $request, string $slug): JsonResponse
    {
        $tenant = $this->resolveTenant($slug);

        $data = $request->validate([
            'item_id' => ['required', 'integer'],
        ]);

        $item = $this->itemForTenant((int) $data['item_id'], $tenant);

        $this->cart->addItem($tenant->getKey(), $item);

        return response()->json([
            'count' => $this->cart->getCount($tenant->getKey()),
            'message' => 'تم الإضافة',
        ]);
    }

    public function updateCart(Request $request, string $slug): JsonResponse
    {
        $tenant = $this->resolveTenant($slug);

        $data = $request->validate([
            'item_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:0'],
        ]);

        $this->itemForTenant((int) $data['item_id'], $tenant);

        $this->cart->updateQuantity($tenant->getKey(), (int) $data['item_id'], (int) $data['quantity']);

        $cart = $this->cart->getCart($tenant->getKey());

        return response()->json([
            'cart' => $cart,
            'total' => $cart['total'],
        ]);
    }

    public function getCart(string $slug): JsonResponse
    {
        $tenant = $this->resolveTenant($slug);

        return response()->json($this->cart->getCart($tenant->getKey()));
    }

    public function submitOrder(Request $request, string $slug): JsonResponse
    {
        $tenant = $this->resolveTenant($slug);

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:100'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $cart = $this->cart->getCart($tenant->getKey());

        if ($cart['items'] === []) {
            return response()->json([
                'message' => 'السلة فارغة، أضف أصنافاً أولاً.',
            ], 422);
        }

        $order = DB::transaction(function () use ($tenant, $data, $cart): Order {
            $order = Order::create([
                'user_id' => $tenant->getKey(),
                'order_number' => Order::generateOrderNumber($tenant->getKey()),
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'notes' => $data['notes'] ?? null,
                'total' => $cart['total'],
                'status' => 'pending',
            ]);

            $order->items()->createMany(array_values(array_map(
                fn (array $line): array => [
                    'item_id' => $line['id'],
                    'item_name' => $line['name'],
                    'item_price' => $line['price'],
                    'quantity' => $line['quantity'],
                    'subtotal' => $line['subtotal'],
                ],
                $cart['items'],
            )));

            return $order;
        });

        $this->cart->clearCart($tenant->getKey());

        return response()->json([
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'total' => (float) $order->total,
            'message' => 'تم استلام طلبك بنجاح',
        ]);
    }

    public function trackOrder(Request $request, string $slug): JsonResponse
    {
        $tenant = $this->resolveTenant($slug);

        $data = $request->validate([
            'order_number' => ['required', 'string', 'max:50'],
        ]);

        $order = Order::query()
            ->where('order_number', $data['order_number'])
            ->where('user_id', $tenant->getKey())
            ->with('items')
            ->first();

        if ($order === null) {
            return response()->json([
                'message' => 'لم نجد طلباً بهذا الرقم.',
            ], 404);
        }

        return response()->json([
            'order_number' => $order->order_number,
            'status' => $order->status,
            'status_label' => $order->statusLabel(),
            'customer_name' => $order->customer_name,
            'notes' => $order->notes,
            'items' => $order->items->map(fn ($item): array => [
                'id' => $item->getKey(),
                'item_id' => $item->item_id,
                'name' => $item->item_name,
                'price' => (float) $item->item_price,
                'quantity' => $item->quantity,
                'subtotal' => (float) $item->subtotal,
            ])->values(),
            'total' => (float) $order->total,
            'created_at' => $order->created_at?->toIso8601String(),
        ]);
    }

    /**
     * A cross-tenant item id must never reach the cart.
     */
    protected function itemForTenant(int $itemId, User $tenant): Item
    {
        $item = Item::query()->find($itemId);

        abort_if($item === null, 404);

        abort_unless((int) $item->user_id === (int) $tenant->getKey(), 403);

        return $item;
    }
}
