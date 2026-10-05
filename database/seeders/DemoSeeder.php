<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use App\Models\Offer;
use App\Models\Setting;
use App\Models\User;
use App\Services\TenantProvisioner;
use Illuminate\Database\Seeder;

/**
 * A complete tenant used to try the product before subscribing: the landing page
 * links to it at /menu/demo.
 *
 * Safe to run repeatedly. Everything is matched on its natural key and updated
 * in place, so a second run repairs the demo instead of duplicating it.
 *
 * Skipped in production so a real deployment can never expose this account.
 */
class DemoSeeder extends Seeder
{
    public const EMAIL = 'demo@qrmenu.test';

    public const DOMAIN = 'demo';

    public const PASSWORD = 'demo1234';

    /**
     * @var list<array{name: string, items: list<array{title: string, price: float, featured?: bool}>}>
     */
    private const MENU = [
        [
            'name' => 'مشروبات',
            'items' => [
                ['title' => 'قهوة عربية', 'price' => 15],
                ['title' => 'كابتشينو', 'price' => 35, 'featured' => true],
                ['title' => 'عصير برتقال', 'price' => 25],
            ],
        ],
        [
            'name' => 'المقبلات',
            'items' => [
                ['title' => 'حمص بالطحينة', 'price' => 30],
                ['title' => 'بابا غنوج', 'price' => 30],
                ['title' => 'سلطة فتوش', 'price' => 25, 'featured' => true],
            ],
        ],
        [
            'name' => 'الأطباق الرئيسية',
            'items' => [
                ['title' => 'كباب مشوي', 'price' => 85, 'featured' => true],
                ['title' => 'دجاج مشوي', 'price' => 75],
                ['title' => 'سمك مشوي', 'price' => 95],
            ],
        ],
    ];

    /**
     * Discounts keyed by the item they apply to, so the offer can never end up
     * attached to a product that was renamed.
     *
     * @var list<array{item: string, title: string, offer_price: float}>
     */
    private const OFFERS = [
        ['item' => 'كابتشينو', 'title' => 'عرض الصباح', 'offer_price' => 25],
        ['item' => 'كباب مشوي', 'title' => 'عرض اليوم', 'offer_price' => 65],
    ];

    public function run(TenantProvisioner $provisioner): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('Demo tenant skipped: this environment is production.');

            return;
        }

        $tenant = $this->createTenant();

        $this->fillSettings($tenant);

        $items = $this->createMenu($tenant);

        $this->createOffers($tenant, $items);

        // The same call a real signup makes, so the demo tenant is provisioned by
        // exactly the code path a paying customer goes through.
        $provisioner->provision($tenant, self::PASSWORD);

        $this->command?->info('Demo tenant ready: '.route('menu.show', self::DOMAIN));
        $this->command?->comment('Login: '.self::EMAIL.' / '.self::PASSWORD);
    }

    private function createTenant(): User
    {
        $tenant = User::query()->firstOrNew(['email' => self::EMAIL]);

        $tenant->fill([
            'name' => 'مطعم ليالي الشرق',
            'password' => self::PASSWORD,
            'domain' => self::DOMAIN,
            'is_active' => true,
            'subscription_expires_at' => now()->addYears(10),
        ]);

        $tenant->save();

        return $tenant;
    }

    /**
     * The provisioner creates a bare settings row named after the tenant, so the
     * demo's own values are applied afterwards rather than in place of it.
     *
     * The website fields are filled in too, otherwise the demo restaurant would
     * be the one tenant whose public page shows none of its own sections.
     */
    private function fillSettings(User $tenant): void
    {
        Setting::query()->updateOrCreate(
            ['user_id' => $tenant->getKey()],
            [
                'restaurant_name' => 'مطعم ليالي الشرق',
                'phone' => '01000000000',
                'currency' => Setting::DEFAULT_CURRENCY,
                'primary_color' => '#2D6A4F',
                'secondary_color' => '#52B788',
                'primary_font' => 'Cairo',
                'hero_title' => 'أهلاً بكم في مطعم ليالي الشرق',
                'hero_subtitle' => 'مأكولات شرقية أصيلة تُقدَّم كل يوم من الصبح حتى ب/',
                'show_offers_ticker' => true,
                'branches' => [
                    ['branch_name' => 'فرع المعادي', 'address' => 'شارع 9، المعادي', 'phone' => '01000000001'],
                    ['branch_name' => 'فرع مدينة نصر', 'address' => 'شارع عباس العقاد، مدينة نصر', 'phone' => '01000000002'],
                ],
                'social_links' => [
                    'facebook' => 'https://facebook.com/qrmenu.demo',
                    'instagram' => 'https://instagram.com/qrmenu.demo',
                    'whatsapp' => 'https://wa.me/201000000000',
                    'tiktok' => 'https://tiktok.com/@qrmenu.demo',
                ],
                'seo_title' => 'مطعم ليالي الشرق | قائمة طعام إلكترونية',
                'seo_description' => 'قائمة طعم مطعم ليالي الشرق الإلكترونية: مشروبات ومقبلات وأطباق رئيسية وعروض يومية.',
                'seo_keywords' => 'مطعم, طعام, قائمة طعام, delivery, القاهرة',
            ],
        );
    }

    /**
     * @return array<string, Item> keyed by title, for the offers to look up.
     */
    private function createMenu(User $tenant): array
    {
        $items = [];

        foreach (self::MENU as $sortOrder => $section) {
            $category = Category::query()->updateOrCreate(
                ['user_id' => $tenant->getKey(), 'name' => $section['name']],
                ['sort_order' => $sortOrder + 1],
            );

            foreach ($section['items'] as $itemSortOrder => $item) {
                $items[$item['title']] = Item::query()->updateOrCreate(
                    ['category_id' => $category->getKey(), 'title' => $item['title']],
                    [
                        'user_id' => $tenant->getKey(),
                        'description' => null,
                        'price' => $item['price'],
                        'is_available' => true,
                        'is_featured' => (bool) ($item['featured'] ?? false),
                        'sort_order' => $itemSortOrder + 1,
                    ],
                );
            }
        }

        return $items;
    }

    /**
     * @param  array<string, Item>  $items
     */
    private function createOffers(User $tenant, array $items): void
    {
        foreach (self::OFFERS as $offer) {
            $item = $items[$offer['item']] ?? null;

            if ($item === null) {
                continue;
            }

            Offer::query()->updateOrCreate(
                ['item_id' => $item->getKey(), 'title' => $offer['title']],
                [
                    'user_id' => $tenant->getKey(),
                    'offer_price' => $offer['offer_price'],
                    'is_active' => true,
                    'expires_at' => null,
                ],
            );
        }
    }
}
