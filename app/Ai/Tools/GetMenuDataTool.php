<?php

namespace App\Ai\Tools;

use App\Models\Category;
use App\Models\Item;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetMenuDataTool implements Tool
{
    public function __construct(protected int $tenantId) {}

    public function description(): Stringable|string
    {
        return 'يجيب كل المنتجات والأصناف والعروض المتاحة للمطعم.';
    }

    /**
     * The tenant is bound to the tool by the agent, so the model cannot ask for
     * another restaurant's menu.
     */
    public function handle(Request $request): Stringable|string
    {
        $tenant = User::query()->find($this->tenantId);

        if ($tenant === null) {
            return 'لا توجد بيانات متاحة';
        }

        $categories = $tenant->categories()
            ->with(['items' => fn (BelongsToMany $items) => $items
                ->where('is_available', true)
                ->orderBy('sort_order')
                ->orderBy('id')])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->reject(fn (Category $category): bool => $category->items->isEmpty());

        $offers = $tenant->offers()
            ->currentlyActive()
            ->whereHas('items', fn (Builder $items) => $items->where('is_available', true))
            ->with(['items' => fn (BelongsToMany $items) => $items
                ->where('is_available', true)
                ->orderBy('sort_order')
                ->orderBy('id')])
            ->orderBy('id')
            ->get();

        if ($categories->isEmpty() && $offers->isEmpty()) {
            return 'لا توجد بيانات متاحة';
        }

        $payload = [
            'restaurant' => $tenant->settings?->restaurant_name ?: $tenant->name,
            'categories' => $categories->map(fn (Category $category): array => [
                'name' => $category->name,
                'items' => $category->items->map(fn (Item $item): array => [
                    'name' => $item->title,
                    'description' => $item->description,
                    'price' => (float) $item->price,
                    'is_featured' => (bool) $item->is_featured,
                ])->values()->all(),
            ])->values()->all(),
            'offers' => $offers->map(fn (Offer $offer): array => [
                'title' => $offer->title,
                'offer_price' => (float) $offer->offer_price,
                'original_price' => $offer->totalItemsPrice(),
                'items' => $offer->items->pluck('title')->values()->all(),
            ])->values()->all(),
        ];

        return (string) json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
