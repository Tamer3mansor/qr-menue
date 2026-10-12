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
 * A specialty coffee shop tenant used to showcase the product with a real menu:
 * hot and iced coffee, matcha, frappe, mojitos and juices, bakery and desserts.
 * The items and the price range mirror what Cairo coffee shops actually charge
 * (إسبريسو، كابتشينو، سبانش لاتيه، آيس لاتيه، موهيتو).
 *
 * Safe to run repeatedly. Everything is matched on its natural key and updated
 * in place, so a second run repairs the demo instead of duplicating it.
 *
 * Skipped in production so a real deployment can never expose this account.
 */
class CoffeeShopDemoSeeder extends Seeder
{
    public const EMAIL = 'demo3@gmail.com';

    public const DOMAIN = 'demo3';

    public const PASSWORD = '12345678';

    /**
     * @var list<array{name: string, items: list<array{title: string, price: float, description?: string, featured?: bool}>}>
     */
    private const MENU = [
        [
            'name' => 'القهوة الساخنة',
            'items' => [
                ['title' => 'إسبريسو سنجل', 'price' => 55, 'description' => 'شوت إسبريسو مركز من بن مختص.'],
                ['title' => 'إسبريسو دبل', 'price' => 65, 'description' => 'شوتين إسبريسو بنكهة قوية.'],
                ['title' => 'أمريكانو', 'price' => 70, 'description' => 'إسبريسو مخفف بالماء الساخن.'],
                ['title' => 'كابتشينو', 'price' => 90, 'description' => 'إسبريسو وحليب مبخر ورغوة كثيفة.', 'featured' => true],
                ['title' => 'كافيه لاتيه', 'price' => 90, 'description' => 'إسبريسو وحليب مبخر بطبقة رغوة رقيقة.'],
                ['title' => 'فلات وايت', 'price' => 85, 'description' => 'شوت دبل بحليب مخملي ورغوة أخف.'],
                ['title' => 'كورتادو', 'price' => 65, 'description' => 'إسبريسو مع قليل من الحليب الدافئ.'],
                ['title' => 'موكا', 'price' => 100, 'description' => 'إسبريسو مع الشوكولاتة والحليب.'],
                ['title' => 'وايت موكا', 'price' => 100, 'description' => 'إسبريسو مع الشوكولاتة البيضاء.'],
                ['title' => 'كراميل ماكياتو', 'price' => 105, 'description' => 'إسبريسو مع صوص الكراميل والحليب المبخر.'],
                ['title' => 'سبانش لاتيه', 'price' => 95, 'description' => 'إسبريسو بالحليب المكثف المحلى.', 'featured' => true],
                ['title' => 'قهوة تركي سنجل', 'price' => 45, 'description' => 'قهوة تركي سادة أو محوج.'],
                ['title' => 'قهوة تركي دبل', 'price' => 65, 'description' => 'قهوة تركي دبل بالتحويجة.'],
                ['title' => 'قهوة فرنساوي', 'price' => 70, 'description' => 'قهوة فرنساوي بالحليب والكريمة.'],
                ['title' => 'قهوة بندق', 'price' => 75, 'description' => 'قهوة بنكهة البندق.'],
                ['title' => 'شوكولاتة ساخنة', 'price' => 95, 'description' => 'شوكولاتة ساخنة كريمية.'],
                ['title' => 'سحلب بالمكسرات', 'price' => 90, 'description' => 'سحلب دافئ بالمكسرات والقرفة.'],
            ],
        ],
        [
            'name' => 'القهوة المثلجة',
            'items' => [
                ['title' => 'آيس لاتيه', 'price' => 95, 'description' => 'إسبريسو مع الحليب البارد والثلج.', 'featured' => true],
                ['title' => 'آيس سبانش لاتيه', 'price' => 110, 'description' => 'سبانش لاتيه بارد بالحليب المكثف.'],
                ['title' => 'آيس أمريكانو', 'price' => 85, 'description' => 'إسبريسو مخفف بالماء المثلج.'],
                ['title' => 'آيس موكا', 'price' => 105, 'description' => 'موكا باردة بالشوكولاتة والحليب.'],
                ['title' => 'آيس وايت موكا', 'price' => 105, 'description' => 'موكا باردة بالشوكولاتة البيضاء.'],
                ['title' => 'آيس كراميل ماكياتو', 'price' => 110, 'description' => 'كراميل ماكياتو بارد بالثلج.'],
                ['title' => 'آيس شوكولاتة', 'price' => 100, 'description' => 'شوكولاتة باردة كريمية.'],
                ['title' => 'كولد برو', 'price' => 100, 'description' => 'قهوة منقوعة على البارد 12 ساعة.'],
            ],
        ],
        [
            'name' => 'ماتشا ومشروبات مميزة',
            'items' => [
                ['title' => 'هوت ماتشا لاتيه', 'price' => 115, 'description' => 'ماتشا خضراء مع الحليب المبخر.', 'featured' => true],
                ['title' => 'آيس ماتشا لاتيه', 'price' => 115, 'description' => 'ماتشا خضراء مثلجة بالحليب.'],
                ['title' => 'هوت بستاشيو لاتيه', 'price' => 135, 'description' => 'لاتيه بنكهة الفستق الحلبي.'],
                ['title' => 'آيس بستاشيو لاتيه', 'price' => 130, 'description' => 'لاتيه فستق بارد بالثلج.'],
                ['title' => 'روز لاتيه', 'price' => 120, 'description' => 'لاتيه بماء الورد والحليب.'],
                ['title' => 'شوكولاتة بالمشملو', 'price' => 110, 'description' => 'شوكولاتة ساخنة بالمشملو.'],
            ],
        ],
        [
            'name' => 'فرابيه وبليندد',
            'items' => [
                ['title' => 'فرابيه كراميل', 'price' => 120, 'description' => 'قهوة مثلجة مخفوقة بصوص الكراميل.'],
                ['title' => 'فرابيه موكا', 'price' => 120, 'description' => 'قهوة مثلجة مخفوقة بالشوكولاتة.'],
                ['title' => 'فرابيه بستاشيو', 'price' => 140, 'description' => 'فرابيه بالفستق الحلبي.'],
                ['title' => 'فرابيه أوريو', 'price' => 125, 'description' => 'فرابيه بالبسكويت والشوكولاتة.'],
            ],
        ],
        [
            'name' => 'العصائر والموهيتو',
            'items' => [
                ['title' => 'موهيتو كلاسيك', 'price' => 90, 'description' => 'نعناع وليمون وصودا بالثلج.', 'featured' => true],
                ['title' => 'موهيتو فراولة', 'price' => 95, 'description' => 'موهيتو بالفراولة الطازجة.'],
                ['title' => 'عصير مانجو طازج', 'price' => 90, 'description' => 'مانجو طبيعي 100%.'],
                ['title' => 'عصير برتقال طازج', 'price' => 75, 'description' => 'برتقال طبيعي طازج.'],
                ['title' => 'ليمون بالنعناع', 'price' => 80, 'description' => 'ليمون طازج مع نعناع.'],
                ['title' => 'كركديه مثلج', 'price' => 60, 'description' => 'كركديه بارد منقوع.'],
                ['title' => 'ليمونادة فراولة', 'price' => 95, 'description' => 'ليمونادة بالفراولة.'],
            ],
        ],
        [
            'name' => 'المخبوزات والحلويات',
            'items' => [
                ['title' => 'كرواسون سادة', 'price' => 60, 'description' => 'كرواسون فرنسي بالزبدة.'],
                ['title' => 'كرواسون بالجبنة', 'price' => 85, 'description' => 'كرواسون محشو جبنة.'],
                ['title' => 'كرواسون بالزعتر', 'price' => 80, 'description' => 'كرواسون بالزعتر والزيت.'],
                ['title' => 'تشيز كيك', 'price' => 110, 'description' => 'تشيز كيك كريمي بصوص التوت.', 'featured' => true],
                ['title' => 'تيراميسو', 'price' => 100, 'description' => 'تيراميسو بالقهوة والمسكربوني.'],
                ['title' => 'براوني', 'price' => 80, 'description' => 'براوني شوكولاتة غني بالمكسرات.'],
                ['title' => 'كوكيز', 'price' => 50, 'description' => 'كوكيز بالشوكولاتة تشيب.'],
            ],
        ],
        [
            'name' => 'الشاي والأعشاب',
            'items' => [
                ['title' => 'شاي كوب', 'price' => 25, 'description' => 'شاي مصري كوب.'],
                ['title' => 'شاي بالنعناع', 'price' => 30, 'description' => 'شاي مصري بالنعناع الطازج.'],
                ['title' => 'كركديه ساخن', 'price' => 30, 'description' => 'كركديه ساخن.'],
                ['title' => 'ينسون', 'price' => 30, 'description' => 'ينسون دافئ.'],
                ['title' => 'قرفة باللبن', 'price' => 45, 'description' => 'قرفة بالحليب الساخن.'],
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
        ['item' => 'كابتشينو', 'title' => 'عرض الصباح', 'offer_price' => 70],
        ['item' => 'آيس لاتيه', 'title' => 'عرض الصيف', 'offer_price' => 75],
        ['item' => 'تشيز كيك', 'title' => 'عرض القهوة والحلى', 'offer_price' => 85],
    ];

    public function run(TenantProvisioner $provisioner): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('Coffee shop demo tenant skipped: this environment is production.');

            return;
        }

        $tenant = $this->createTenant();

        $this->fillSettings($tenant);

        $items = $this->createMenu($tenant);

        $this->createOffers($tenant, $items);

        $provisioner->provision($tenant, self::PASSWORD);

        $this->command?->info('Coffee shop demo tenant ready: '.route('menu.show', self::DOMAIN));
        $this->command?->comment('Login: '.self::EMAIL.' / '.self::PASSWORD);
    }

    private function createTenant(): User
    {
        $tenant = User::query()->firstOrNew(['email' => self::EMAIL]);

        $tenant->fill([
            'name' => 'كافيه الأصيل',
            'password' => self::PASSWORD,
            'domain' => self::DOMAIN,
            'is_active' => true,
            'subscription_expires_at' => now()->addYears(10),
        ]);

        $tenant->save();

        return $tenant;
    }

    private function fillSettings(User $tenant): void
    {
        Setting::query()->updateOrCreate(
            ['user_id' => $tenant->getKey()],
            [
                'restaurant_name' => 'كافيه الأصيل',
                'phone' => '01000000003',
                'currency' => Setting::DEFAULT_CURRENCY,
                'primary_color' => '#4A2C17',
                'secondary_color' => '#C9975B',
                'primary_font' => 'Cairo',
                'hero_title' => 'أهلاً بكم في كافيه الأصيل',
                'hero_subtitle' => 'قهوة مختصة ومعجنات طازجة ومشروبات باردة على مزاجك.',
                'show_offers_ticker' => true,
                'branches' => [
                    ['branch_name' => 'فرع التجمع الخامس', 'address' => 'التجمع الخامس، القاهرة الجديدة', 'phone' => '01000000013'],
                    ['branch_name' => 'فرع الزمالك', 'address' => 'شارع 26 يوليو، الزمالك، الجيزة', 'phone' => '01000000023'],
                ],
                'social_links' => [
                    'facebook' => 'https://facebook.com/qrmenu.coffeeshop',
                    'instagram' => 'https://instagram.com/qrmenu.coffeeshop',
                    'whatsapp' => 'https://wa.me/201000000003',
                    'tiktok' => 'https://tiktok.com/@qrmenu.coffeeshop',
                ],
                'seo_title' => 'كافيه الأصيل | قائمة مشروبات إلكترونية',
                'seo_description' => 'قائمة كافيه الأصيل الإلكترونية: قهوة ساخنة وباردة، ماتشا، فرابيه، عصائر، موهيتو، مخبوزات وحلويات.',
                'seo_keywords' => 'كافيه, قهوة مختصة, كابتشينو, آيس لاتيه, ماتشا, القاهرة',
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
                    ['user_id' => $tenant->getKey(), 'title' => $item['title']],
                    [
                        'description' => $item['description'] ?? null,
                        'price' => $item['price'],
                        'is_available' => true,
                        'is_featured' => (bool) ($item['featured'] ?? false),
                        'sort_order' => $itemSortOrder + 1,
                    ],
                );

                $items[$item['title']]->categories()->syncWithoutDetaching([$category->getKey()]);
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
                ['user_id' => $tenant->getKey(), 'title' => $offer['title']],
                [
                    'offer_price' => $offer['offer_price'],
                    'is_active' => true,
                    'expires_at' => null,
                ],
            )->items()->syncWithoutDetaching([$item->getKey()]);
        }
    }
}
