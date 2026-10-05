<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Setting;
use App\Traits\ResolvesTenant;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The public website a tenant serves under /site/{slug}.
 *
 * The tenant is resolved from the same slug the menu uses, so the two pages can
 * never disagree about who a slug belongs to.
 */
class WebsiteController extends Controller
{
    use ResolvesTenant;

    public function show(string $slug): View
    {
        $tenant = $this->resolveTenant($slug);

        if (! $tenant->hasRunningSubscription()) {
            return view('website.subscription-ended', ['user' => $tenant]);
        }

        $settings = $tenant->settings;

        $offers = $tenant->offers()
            ->currentlyActive()
            ->whereHas('item', fn (Builder $query) => $query->where('is_available', true))
            ->with('item')
            ->orderBy('id')
            ->get();

        $offersByItem = $offers->keyBy('item_id');

        $categories = $tenant->categories()
            ->with(['items' => fn (HasMany $items) => $items
                ->where('is_available', true)
                ->orderBy('sort_order')
                ->orderBy('id')])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            // A category with nothing to show is noise on the website.
            ->reject(fn (Category $category): bool => $category->items->isEmpty())
            ->values();

        $featured = $tenant->items()
            ->where('is_featured', true)
            ->where('is_available', true)
            ->with('category')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('website.show', [
            'user' => $tenant,
            'settings' => $settings,
            'categories' => $categories,
            'featured' => $featured,
            'offers' => $offers,
            'offersByItem' => $offersByItem,
            'branches' => $settings?->branchesList() ?? [],
            'socialLinks' => $settings?->filledSocialLinks() ?? [],
            'showOffersTicker' => (bool) $settings?->show_offers_ticker,
            'currency' => Setting::currencyFor($tenant),
            'font' => self::fontFor($settings),
        ]);
    }

    /**
     * Only fonts the page actually loads are used, so a hand-edited settings row
     * cannot inject an arbitrary font family into the stylesheet.
     */
    protected static function fontFor(?Setting $settings): string
    {
        $font = (string) ($settings?->primary_font ?: 'Cairo');

        return in_array($font, self::fonts(), strict: true) ? $font : 'Cairo';
    }

    /**
     * @return list<string>
     */
    protected static function fonts(): array
    {
        return ['Cairo', 'Tajawal', 'Almarai', 'Noto Sans Arabic'];
    }
}
