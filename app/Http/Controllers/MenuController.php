<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Offer;
use App\Models\Setting;
use App\Traits\ResolvesTenant;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MenuController extends Controller
{
    use ResolvesTenant;

    public function show(string $slug): View
    {
        $user = $this->resolveTenant($slug);

        if (! $user->hasRunningSubscription()) {
            return view('menu.subscription-ended', ['user' => $user]);
        }

        $categories = $user->categories()
            ->with(['items' => fn (BelongsToMany $items) => $items
                ->where('is_available', true)
                // The menu filter reads each item's category ids.
                ->with('categories')
                ->orderBy('sort_order')
                ->orderBy('id')])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->reject(fn (Category $category): bool => $category->items->isEmpty())
            ->values();

        $offers = $user->offers()
            ->currentlyActive()
            ->whereHas('items', fn (Builder $query) => $query->where('is_available', true))
            ->with(['items' => fn (BelongsToMany $items) => $items
                ->where('is_available', true)
                ->orderBy('sort_order')
                ->orderBy('id')])
            ->orderBy('id')
            ->get();

        // A single-item offer styles the item's own card in the grid; anything
        // bigger is a combo that gets its own section instead.
        $offersByItem = $offers
            ->filter(fn (Offer $offer): bool => $offer->items->count() === 1)
            ->mapWithKeys(fn (Offer $offer): array => [$offer->items->first()->getKey() => $offer]);

        $comboOffers = $offers
            ->filter(fn (Offer $offer): bool => $offer->items->count() > 1)
            ->values();

        return view('menu.show', [
            'user' => $user,
            'settings' => $user->settings,
            'categories' => $categories,
            'offersByItem' => $offersByItem,
            'comboOffers' => $comboOffers,
            'currency' => Setting::currencyFor($user),
        ]);
    }
}
