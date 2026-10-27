<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Services\CartService;
use App\Traits\ResolvesTenant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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

        $branches = $tenant->settings?->branchesList() ?? [];

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:100'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:500'],
            'branch_id' => [
                count($branches) > 1 ? 'required' : 'nullable',
                'integer',
                Rule::in(array_keys($branches)),
            ],
            'delivery_type' => ['required', Rule::in(['takeaway', 'delivery'])],
            'delivery_address' => ['nullable', 'string', 'max:500', 'required_if:delivery_type,delivery'],
        ], [
            'branch_id.required' => 'يرجى اختيار الفرع.',
            'branch_id.in' => 'الفرع المحدد غير متاح.',
            'delivery_type.required' => 'يرجى اختيار نوع الاستلام.',
            'delivery_type.in' => 'نوع الاستلام غير صحيح.',
            'delivery_address.required_if' => 'يرجى إدخال عنوان التوصيل.',
        ]);

        $cart = $this->cart->getCart($tenant->getKey());

        if ($cart['items'] === []) {
            return response()->json([
                'message' => 'طلبك فارغ، أضف أصنافاً أولاً.',
            ], 422);
        }

        $branchId = $data['branch_id'] ?? null;

        if (count($branches) === 1 && $branchId === null) {
            $branchId = array_key_first($branches);
        }

        $order = DB::transaction(function () use ($tenant, $data, $cart, $branchId): Order {
            $order = Order::create([
                'user_id' => $tenant->getKey(),
                'tenant_order_number' => Order::generateOrderNumber($tenant->getKey()),
                'branch_id' => $branchId,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'notes' => $data['notes'] ?? null,
                'delivery_type' => $data['delivery_type'],
                'delivery_address' => $data['delivery_address'] ?? null,
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
            'order_number' => $order->tenant_order_number,
            'order_number_display' => $order->displayNumber(),
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'delivery_type' => $order->delivery_type,
            'delivery_address' => $order->delivery_address,
            'total' => (float) $order->total,
            'message' => 'تم استلام طلبك بنجاح',
        ]);
    }

    /**
     * The public tracking page: the same lookup as the JSON endpoint, rendered
     * so a customer can bookmark or share the result.
     */
    public function trackPage(Request $request, string $slug, ?string $number = null): View
    {
        $tenant = $this->resolveTenant($slug);

        if (! $tenant->hasRunningSubscription()) {
            return view('website.subscription-ended', ['user' => $tenant]);
        }

        $number = filled($number) ? $number : $request->query('number');
        $number = filled($number) ? trim((string) $number) : null;

        $order = null;
        $error = null;

        if (filled($number)) {
            if (! ctype_digit($number)) {
                $error = 'رقم الطلب غير صحيح.';
            } else {
                $order = Order::query()
                    ->where('tenant_order_number', (int) $number)
                    ->where('user_id', $tenant->getKey())
                    ->with('items')
                    ->first();

                if ($order === null) {
                    $error = 'لم نجد طلباً بهذا الرقم.';
                }
            }
        }

        return view('order.track', [
            'tenant' => $tenant,
            'settings' => $tenant->settings,
            'slug' => $slug,
            'number' => $number,
            'order' => $order,
            'error' => $error,
            'currency' => Setting::currencyFor($tenant),
        ]);
    }

    public function trackOrder(Request $request, string $slug): JsonResponse
    {
        $tenant = $this->resolveTenant($slug);

        $data = $request->validate([
            'order_number' => ['required', 'integer', 'min:1'],
        ]);

        $order = Order::query()
            ->where('tenant_order_number', (int) $data['order_number'])
            ->where('user_id', $tenant->getKey())
            ->with('items')
            ->first();

        if ($order === null) {
            return response()->json([
                'message' => 'لم نجد طلباً بهذا الرقم.',
            ], 404);
        }

        return response()->json([
            'order_number' => $order->tenant_order_number,
            'order_number_display' => $order->displayNumber(),
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
