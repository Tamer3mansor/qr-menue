<?php

namespace App\Services;

use App\Models\Item;
use Illuminate\Contracts\Session\Session;

/**
 * Holds a visitor's cart in the session, one cart per tenant.
 *
 * Every read recalculates the totals from the stored lines, so a stale total
 * can never be trusted over the items themselves.
 */
class CartService
{
    public function __construct(protected Session $session) {}

    /**
     * @return array{items: array<int, array{id: int, name: string, price: float, quantity: int, subtotal: float, image: ?string}>, total: float, count: int}
     */
    public function getCart(int $tenantId): array
    {
        $cart = $this->session->get($this->key($tenantId));

        if (! is_array($cart) || ! is_array($cart['items'] ?? null)) {
            return $this->emptyCart();
        }

        return $this->recalculate($cart['items']);
    }

    public function addItem(int $tenantId, Item $item): void
    {
        $cart = $this->getCart($tenantId);
        $itemId = $item->getKey();
        $quantity = ($cart['items'][$itemId]['quantity'] ?? 0) + 1;

        $cart['items'][$itemId] = [
            'id' => $itemId,
            'name' => $item->title,
            'price' => (float) $item->price,
            'quantity' => $quantity,
            'subtotal' => round((float) $item->price * $quantity, 2),
            'image' => $item->image_url,
        ];

        $this->put($tenantId, $cart['items']);
    }

    public function removeItem(int $tenantId, int $itemId): void
    {
        $cart = $this->getCart($tenantId);

        unset($cart['items'][$itemId]);

        $this->put($tenantId, $cart['items']);
    }

    public function updateQuantity(int $tenantId, int $itemId, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->removeItem($tenantId, $itemId);

            return;
        }

        $cart = $this->getCart($tenantId);

        if (! isset($cart['items'][$itemId])) {
            return;
        }

        $cart['items'][$itemId]['quantity'] = $quantity;
        $cart['items'][$itemId]['subtotal'] = round($cart['items'][$itemId]['price'] * $quantity, 2);

        $this->put($tenantId, $cart['items']);
    }

    public function clearCart(int $tenantId): void
    {
        $this->session->forget($this->key($tenantId));
    }

    public function getTotal(int $tenantId): float
    {
        return $this->getCart($tenantId)['total'];
    }

    public function getCount(int $tenantId): int
    {
        return $this->getCart($tenantId)['count'];
    }

    protected function key(int $tenantId): string
    {
        return "cart_{$tenantId}";
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function put(int $tenantId, array $items): void
    {
        $this->session->put($this->key($tenantId), $this->recalculate($items));
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array{items: array<int, array{id: int, name: string, price: float, quantity: int, subtotal: float, image: ?string}>, total: float, count: int}
     */
    protected function recalculate(array $items): array
    {
        $total = 0.0;
        $count = 0;
        $normalized = [];

        foreach ($items as $itemId => $item) {
            $quantity = max(0, (int) ($item['quantity'] ?? 0));

            if ($quantity === 0) {
                continue;
            }

            $price = (float) ($item['price'] ?? 0);
            $subtotal = round($price * $quantity, 2);

            $normalized[(int) $itemId] = [
                'id' => (int) $itemId,
                'name' => (string) ($item['name'] ?? ''),
                'price' => $price,
                'quantity' => $quantity,
                'subtotal' => $subtotal,
                'image' => $item['image'] ?? null,
            ];

            $total += $subtotal;
            $count += $quantity;
        }

        return [
            'items' => $normalized,
            'total' => round($total, 2),
            'count' => $count,
        ];
    }

    /**
     * @return array{items: array<int, array{id: int, name: string, price: float, quantity: int, subtotal: float, image: ?string}>, total: float, count: int}
     */
    protected function emptyCart(): array
    {
        return [
            'items' => [],
            'total' => 0.0,
            'count' => 0,
        ];
    }
}
