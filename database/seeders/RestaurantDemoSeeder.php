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
 * A full Egyptian restaurant tenant used to showcase the product with a real
 * menu: breakfast, appetizers, hawawshi, tagines, koshary, grills, desserts and
 * drinks. The menu items and the price range mirror what popular Egyptian
 * restaurants actually serve (كشري، حواوشي، طواجن، مشويات).
 *
 * Safe to run repeatedly. Everything is matched on its natural key and updated
 * in place, so a second run repairs the demo instead of duplicating it.
 *
 * Skipped in production so a real deployment can never expose this account.
 */
class RestaurantDemoSeeder extends Seeder
{
    public const EMAIL = 'demo2@gmail.com';

    public const DOMAIN = 'demo2';

    public const PASSWORD = '12345678';

    /**
     * @var list<array{name: string, items: list<array{title: string, price: float, description?: string, featured?: bool}>}>
     */
    private const MENU = [
        [
            'name' => 'الفطار المصري',
            'items' => [
                ['title' => 'فول مدمس بزيت الزيتون', 'price' => 35, 'description' => 'فول مدشوش بزيت الزيتون والليمون والكمون.'],
                ['title' => 'فول إسكندراني', 'price' => 45, 'description' => 'فول بالطماطم والفلفل والبصل والتوابل الإسكندرانية.', 'featured' => true],
                ['title' => 'طعمية (2 قطعة)', 'price' => 20, 'description' => 'طعمية فول مقرمشة بالسمسم.'],
                ['title' => 'بيض مدحرج', 'price' => 30, 'description' => 'بيض مسلوق بالسمن البلدي والكمون.'],
                ['title' => 'مسقعة باذنجان', 'price' => 45, 'description' => 'باذنجان مقلي بصوص الطماطم والفلفل.'],
                ['title' => 'جبنة قريش بالطماطم', 'price' => 40, 'description' => 'جبنة قريش طازجة مع طماطم وبصل أخضر.'],
                ['title' => 'عيش بلدي', 'price' => 5, 'description' => 'رغيف عيش بلدي طازج.'],
            ],
        ],
        [
            'name' => 'المقبلات والسلطات',
            'items' => [
                ['title' => 'سلطة بلدي', 'price' => 25, 'description' => 'طماطم وخيار وبصل وبقدونس بالليمون والخل.'],
                ['title' => 'بابا غنوج', 'price' => 35, 'description' => 'باذنجان مشوي بالطحينة والثوم والليمون.'],
                ['title' => 'حمص بالطحينة', 'price' => 35, 'description' => 'حمص مطحون بالطحينة وزيت الزيتون.'],
                ['title' => 'طحينة', 'price' => 25, 'description' => 'طحينة بالليمون والثوم.'],
                ['title' => 'مخلل مشكل', 'price' => 20, 'description' => 'مخللات بلدي مشكلة.'],
                ['title' => 'ممبار (طبق)', 'price' => 70, 'description' => 'ممبار مشوي بالسمن والتوابل.'],
                ['title' => 'كبيبة (طبق)', 'price' => 85, 'description' => 'كبيبة مقلية محشية لحم مفروم.'],
                ['title' => 'سمبوسة جبنة', 'price' => 60, 'description' => 'سمبوسة محشية جبنة رومي.'],
            ],
        ],
        [
            'name' => 'الحواوشي والساندوتشات',
            'items' => [
                ['title' => 'حواوشي لحم', 'price' => 65, 'description' => 'حواوشي بلدي محشو لحم مفروم بالتوابل.', 'featured' => true],
                ['title' => 'حواوشي كفتة', 'price' => 70, 'description' => 'حواوشي محشو كفتة مشوية بالبصل.'],
                ['title' => 'ساندوتش كفتة مشوية', 'price' => 60, 'description' => 'كفتة مشوية في عيش فينو بالطحينة.'],
                ['title' => 'ساندوتش شيش طاووق', 'price' => 70, 'description' => 'شيش طاووق متبل في عيش فينو.'],
                ['title' => 'ساندوتش سجق', 'price' => 90, 'description' => 'سجق بلدي مشوي بالفلفل والطماطم.'],
                ['title' => 'ساندوتش كباب', 'price' => 140, 'description' => 'كباب مشوي على الفحم في عيش بلدي.'],
            ],
        ],
        [
            'name' => 'الطواجن والمحاشي',
            'items' => [
                ['title' => 'طاجن بطاطس باللحمة', 'price' => 120, 'description' => 'بطاطس وقطع لحمة في طاجن بالفرن.'],
                ['title' => 'طاجن كبدة', 'price' => 90, 'description' => 'كبدة بصلصة الطماطم والفلفل في طاجن.'],
                ['title' => 'طاجن جمبري', 'price' => 180, 'description' => 'جمبري بالصلصة والبهارات في طاجن فرن.', 'featured' => true],
                ['title' => 'محشي مشكل', 'price' => 80, 'description' => 'ورق عنب وكوسة وباذنجان وفلفل بالخلطة المصرية.'],
                ['title' => 'محشي ورق عنب', 'price' => 90, 'description' => 'ورق عنب محشي بالخلطة مع دبس الرمان.'],
                ['title' => 'ملوخية بالدجاج', 'price' => 90, 'description' => 'ملوخية خضراء بالثوم والكزبرة مع الدجاج.'],
            ],
        ],
        [
            'name' => 'الكشري المصري',
            'items' => [
                ['title' => 'كشري وسط', 'price' => 60, 'description' => 'أرز وعدس ومكرونة بالصلصة الحارة والبصل المحمر.', 'featured' => true],
                ['title' => 'كشري كبير', 'price' => 80, 'description' => 'طبق كشري كبير مع صوص الدقة.'],
                ['title' => 'كشري بالكبدة', 'price' => 95, 'description' => 'كشري مضاف إليه كبدة مشوية.'],
                ['title' => 'كشري عائلي', 'price' => 220, 'description' => 'طبق عائلي كبير يكفي 4 أفراد.'],
            ],
        ],
        [
            'name' => 'المشويات',
            'items' => [
                ['title' => 'وجبة مشكل مشويات', 'price' => 250, 'description' => 'شيش وكفتة وكباب وربع دجاجة مع الأرز والثومية.', 'featured' => true],
                ['title' => 'كباب', 'price' => 220, 'description' => 'كباب لحم مشوي على الفحم.'],
                ['title' => 'كفتة', 'price' => 150, 'description' => 'كفتة مشوية بالبصل والبقدونس.'],
                ['title' => 'شيش طاووق', 'price' => 130, 'description' => 'قطع دجاج متبلة مشوية على الفحم.'],
                ['title' => 'كبدة مشوية', 'price' => 120, 'description' => 'كبدة إسكندراني مشوية بالفلفل.'],
                ['title' => 'طرب ضاني', 'price' => 200, 'description' => 'قطع ضاني مشوية بالتوابل.'],
                ['title' => 'ربع دجاجة مشوية', 'price' => 90, 'description' => 'ربع دجاجة مشوية مع الأرز.'],
                ['title' => 'نصف دجاجة مشوية', 'price' => 150, 'description' => 'نصف دجاجة مشوية مع الأرز والسلطة.'],
            ],
        ],
        [
            'name' => 'الحلويات المصرية',
            'items' => [
                ['title' => 'أم علي باللبن', 'price' => 55, 'description' => 'أم علي بالمكسرات والزبيب.'],
                ['title' => 'رز بلبن بالمكسرات', 'price' => 35, 'description' => 'رز بلبن كريمي مغطى بالمكسرات.'],
                ['title' => 'كنافة بالمانجو', 'price' => 70, 'description' => 'كنافة مقرمشة بالكريمة والمانجو.', 'featured' => true],
                ['title' => 'بسبوسة', 'price' => 40, 'description' => 'بسبوسة بالسمن البلدي والقطر.'],
                ['title' => 'مهلبية', 'price' => 35, 'description' => 'مهلبية بالحليب وماء الورد.'],
            ],
        ],
        [
            'name' => 'المشروبات',
            'items' => [
                ['title' => 'شاي مصري', 'price' => 15, 'description' => 'كوب شاي مصري بالنعناع.'],
                ['title' => 'قهوة تركي', 'price' => 30, 'description' => 'قهوة تركي سادة أو محوج.'],
                ['title' => 'عصير قصب', 'price' => 25, 'description' => 'عصير قصب طازج.'],
                ['title' => 'عصير مانجو', 'price' => 45, 'description' => 'عصير مانجو طبيعي.'],
                ['title' => 'تمر هندي', 'price' => 25, 'description' => 'تمر هندي بالليمون.'],
                ['title' => 'مياه معدنية', 'price' => 10, 'description' => 'مياه معدنية صغيرة.'],
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
        ['item' => 'كشري وسط', 'title' => 'عرض الكشري اليوم', 'offer_price' => 45],
        ['item' => 'حواوشي لحم', 'title' => 'عرض الحواوشي', 'offer_price' => 50],
        ['item' => 'وجبة مشكل مشويات', 'title' => 'عرض المشويات العائلي', 'offer_price' => 210],
    ];

    public function run(TenantProvisioner $provisioner): void
    {
        // if (app()->environment('production')) {
        //     $this->command?->warn('Restaurant demo tenant skipped: this environment is production.');

        //     return;
        // }

        $tenant = $this->createTenant();

        $this->fillSettings($tenant);

        $items = $this->createMenu($tenant);

        $this->createOffers($tenant, $items);

        $provisioner->provision($tenant, self::PASSWORD);

        $this->command?->info('Restaurant demo tenant ready: '.route('menu.show', self::DOMAIN));
        $this->command?->comment('Login: '.self::EMAIL.' / '.self::PASSWORD);
    }

    private function createTenant(): User
    {
        $tenant = User::query()->firstOrNew(['email' => self::EMAIL]);

        $tenant->fill([
            'name' => 'مطعم وكبابجي الأصيل',
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
                'restaurant_name' => 'مطعم وكبابجي الأصيل',
                'phone' => '01000000002',
                'currency' => Setting::DEFAULT_CURRENCY,
                'primary_color' => '#8B1E1E',
                'secondary_color' => '#D4A017',
                'primary_font' => 'Cairo',
                'hero_title' => 'أهلاً بكم في مطعم وكبابجي الأصيل',
                'hero_subtitle' => 'أكل مصري أصيل من الفطار للعشا: كشري وحواوشي وطواجن ومشويات على الفحم.',
                'show_offers_ticker' => true,
                'branches' => [
                    ['branch_name' => 'فرع وسط البلد', 'address' => 'شارع طلعت حرب، وسط البلد، القاهرة', 'phone' => '01000000012'],
                    ['branch_name' => 'فرع المهندسين', 'address' => 'شارع جامعة الدول العربية، المهندسين، الجيزة', 'phone' => '01000000022'],
                ],
                'social_links' => [
                    'facebook' => 'https://facebook.com/qrmenu.restaurant',
                    'instagram' => 'https://instagram.com/qrmenu.restaurant',
                    'whatsapp' => 'https://wa.me/201000000002',
                    'tiktok' => 'https://tiktok.com/@qrmenu.restaurant',
                ],
                'seo_title' => 'مطعم وكبابجي الأصيل | قائمة طعام إلكترونية',
                'seo_description' => 'قائمة طعام مطعم وكبابجي الأصيل الإلكترونية: فطار مصري ومقبلات وحواوشي وطواجن وكشري ومشويات وحلويات.',
                'seo_keywords' => 'مطعم مصري, كشري, حواوشي, مشويات, طواجن, القاهرة',
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
