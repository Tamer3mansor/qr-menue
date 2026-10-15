<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Offer;
use App\Models\Setting;
use App\Traits\ResolvesTenant;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

        $font = self::fontFor($settings);

        $offers = $tenant->offers()
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

        $categories = $tenant->categories()
            ->with(['items' => fn (BelongsToMany $items) => $items
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
            ->with('categories')
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
            'comboOffers' => $comboOffers,
            'branches' => $settings?->branchesList() ?? [],
            'socialLinks' => $settings?->filledSocialLinks() ?? [],
            'showOffersTicker' => (bool) $settings?->show_offers_ticker,
            'currency' => Setting::currencyFor($tenant),
            'font' => $font,
            'fontWeights' => self::fontWeightsFor($font),
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
     * Google Fonts rejects a stylesheet that requests a weight the family does
     * not ship, so the narrower Arabic faces stop at 700 while the wide-spectrum
     * ones keep the heavy display weight the site uses.
     */
    protected static function fontWeightsFor(string $font): string
    {
        return in_array($font, ['Cairo', 'Tajawal', 'Almarai', 'Noto Sans Arabic'], strict: true)
            ? '400;600;700;800'
            : '400;500;600;700';
    }

    /**
     * @return list<string>
     */
    protected static function fonts(): array
    {
        return [
            'Cairo',
            'Tajawal',
            'Almarai',
            'Noto Sans Arabic',
            'Reem Kufi',
            'Amiri',
            'El Messiri',
            'Lemonada',
        ];
    }
}
