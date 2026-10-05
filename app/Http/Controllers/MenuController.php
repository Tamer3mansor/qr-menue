<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Setting;
use App\Traits\ResolvesTenant;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
            ->with(['items' => fn (HasMany $items) => $items
                ->where('is_available', true)
                ->orderBy('sort_order')
                ->orderBy('id')])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->reject(fn (Category $category): bool => $category->items->isEmpty())
            ->values();

        $offers = $user->offers()
            ->currentlyActive()
            ->whereHas('item', fn (Builder $query) => $query->where('is_available', true))
            ->with('item')
            ->orderBy('id')
            ->get();

        return view('menu.show', [
            'user' => $user,
            'settings' => $user->settings,
            'categories' => $categories,
            'offers' => $offers,
            'offersByItem' => $offers->keyBy('item_id'),
            'currency' => Setting::currencyFor($user),
        ]);
    }
}
