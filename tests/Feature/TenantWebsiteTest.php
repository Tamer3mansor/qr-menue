<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\Offer;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TenantWebsiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_serves_the_website_for_a_known_domain(): void
    {
        $tenant = $this->tenant();

        $response = $this->get('/site/demo');

        $response->assertOk();
        $response->assertViewIs('website.show');
        $response->assertViewHas('user', fn (User $user): bool => $user->is($tenant));
    }

    public function test_it_serves_the_website_for_a_tenant_id(): void
    {
        $tenant = $this->tenant();

        $response = $this->get("/site/{$tenant->getKey()}");

        $response->assertOk();
        $response->assertViewIs('website.show');
        $response->assertViewHas('user', fn (User $user): bool => $user->is($tenant));
    }

    public function test_an_unknown_slug_is_a_404(): void
    {
        $this->tenant();

        $this->get('/site/nobody')->assertNotFound();
        $this->get('/site/999999')->assertNotFound();
    }

    public function test_an_inactive_tenant_sees_the_subscription_ended_page(): void
    {
        $this->tenant(['is_active' => false]);

        $response = $this->get('/site/demo');

        $response->assertOk();
        $response->assertViewIs('website.subscription-ended');
        $response->assertDontSee('id="products"', false);
    }

    public function test_a_lapsed_subscription_sees_the_subscription_ended_page(): void
    {
        $this->tenant(['subscription_expires_at' => now()->subDay()]);

        $response = $this->get('/site/demo');

        $response->assertOk();
        $response->assertViewIs('website.subscription-ended');
    }

    public function test_a_null_expiry_keeps_the_website_available(): void
    {
        $tenant = $this->tenant(['subscription_expires_at' => null]);

        $this->assertTrue($tenant->hasRunningSubscription());

        $this->get('/site/demo')->assertOk();
    }

    public function test_a_lapsed_subscription_is_also_guarded_by_id(): void
    {
        $tenant = $this->tenant(['subscription_expires_at' => now()->subDay()]);

        $response = $this->get("/site/{$tenant->getKey()}");

        $response->assertOk();
        $response->assertViewIs('website.subscription-ended');
    }

    public function test_featured_items_are_shown_in_their_own_section(): void
    {
        $tenant = $this->tenant();
        $category = Category::factory()->for($tenant)->create(['name' => 'مأكولات']);

        $featured = Item::factory()->for($tenant)->hasAttached($category)->create([
            'title' => 'كبسة مميزة',
            'is_featured' => true,
        ]);

        Item::factory()->for($tenant)->hasAttached($category)->create([
            'title' => 'صنف عادي',
            'is_featured' => false,
        ]);

        $response = $this->get('/site/demo');

        $response->assertOk();
        $response->assertSee('أبرز منتجاتنا');
        $response->assertSee('كبسة مميزة');
        $response->assertSee('مميز');

        $featured->update(['is_featured' => false]);

        $this->get('/site/demo')
            ->assertOk()
            ->assertDontSee('أبرز منتجاتنا');
    }

    public function test_unavailable_featured_items_are_hidden(): void
    {
        $tenant = $this->tenant();
        $category = Category::factory()->for($tenant)->create();

        Item::factory()->for($tenant)->hasAttached($category)->create([
            'title' => 'مخفي',
            'is_featured' => true,
            'is_available' => false,
        ]);

        $this->get('/site/demo')
            ->assertOk()
            ->assertDontSee('مخفي')
            ->assertDontSee('أبرز منتجاتنا');
    }

    public function test_the_ticker_is_hidden_when_the_setting_is_off(): void
    {
        $tenant = $this->tenant();
        $item = $this->availableItem($tenant);

        Offer::factory()->for($tenant)->hasAttached($item)->create(['is_active' => true]);

        $tenant->settings->update(['show_offers_ticker' => false]);

        $this->get('/site/demo')
            ->assertOk()
            ->assertDontSee('class="ticker"', false)
            // The single offer still styles its own item card instead.
            ->assertSee('<span class="badge badge--offer">عرض</span>', false);
    }

    public function test_the_ticker_is_shown_when_the_setting_is_on_and_offers_exist(): void
    {
        $tenant = $this->tenant();
        $item = $this->availableItem($tenant);

        Offer::factory()->for($tenant)->hasAttached($item)->create([
            'title' => 'عرض التحلية',
            'offer_price' => 25,
            'is_active' => true,
        ]);

        $tenant->settings->update(['show_offers_ticker' => true]);

        $response = $this->get('/site/demo');

        $response->assertOk();
        $response->assertSee('class="ticker"', false);
        $response->assertSee('عرض التحلية');
        $response->assertSee('ticker-scroll', false);
    }

    public function test_the_offers_section_disappears_without_active_offers(): void
    {
        $tenant = $this->tenant();
        $item = $this->availableItem($tenant);

        Offer::factory()->for($tenant)->hasAttached($item)->create([
            'is_active' => true,
            'expires_at' => now()->subDay(),
        ]);

        $tenant->settings->update(['show_offers_ticker' => true]);

        $this->get('/site/demo')
            ->assertOk()
            ->assertDontSee('عروض خاصة')
            ->assertDontSee('class="ticker"', false);
    }

    public function test_the_branches_section_disappears_without_branches(): void
    {
        $tenant = $this->tenant();

        $tenant->settings->update(['branches' => null, 'social_links' => null]);

        $this->get('/site/demo')
            ->assertOk()
            ->assertDontSee('فروعنا')
            ->assertDontSee('id="branches"', false);
    }

    public function test_branches_are_rendered_when_configured(): void
    {
        $tenant = $this->tenant();

        $tenant->settings->update(['branches' => [
            ['branch_name' => 'فرع المعادي', 'address' => 'شارع 9', 'phone' => '01000000001'],
            ['branch_name' => 'فرع مدينة نصر', 'address' => 'شارع عباس العقاد', 'phone' => '01000000002'],
        ]]);

        $response = $this->get('/site/demo');

        $response->assertOk();
        $response->assertSee('فروعنا');
        $response->assertSee('فرع المعادي');
        $response->assertSee('tel:01000000001');
    }

    public function test_the_order_button_opens_whatsapp_with_the_settings_phone(): void
    {
        $tenant = $this->tenant();

        $tenant->settings->update(['phone' => '201122334455']);

        $this->get('/site/demo')
            ->assertOk()
            ->assertSee('https://wa.me/201122334455', false);
    }

    public function test_the_order_button_falls_back_to_the_menu_without_a_phone(): void
    {
        $tenant = $this->tenant();

        $tenant->settings->update(['phone' => null]);

        $this->get('/site/demo')
            ->assertOk()
            ->assertDontSee('wa.me/', false);
    }

    public function test_seo_tags_are_rendered(): void
    {
        $tenant = $this->tenant();

        $tenant->settings->update([
            'restaurant_name' => 'مطعم النخيل',
            'seo_title' => 'أكل مصري أصيل في القاهرة',
            'seo_description' => 'وصف تجريبي للموقع',
            'seo_keywords' => 'مطعم, قهوة, فطار',
        ]);

        $response = $this->get('/site/demo');

        $response->assertOk();
        $response->assertSee('<title>أكل مصري أصيل في القاهرة</title>', false);
        $response->assertSee('<meta name="description" content="وصف تجريبي للموقع">', false);
        $response->assertSee('<meta name="keywords" content="مطعم, قهوة, فطار">', false);
        $response->assertSee('<meta property="og:title" content="أكل مصري أصيل في القاهرة">', false);
    }

    public function test_the_title_falls_back_to_the_restaurant_name(): void
    {
        $tenant = $this->tenant();

        $tenant->settings->update(['restaurant_name' => 'مطعم النخيل', 'seo_title' => null]);

        $this->get('/site/demo')
            ->assertOk()
            ->assertSee('<title>مطعم النخيل</title>', false);
    }

    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function fonts(): array
    {
        return [
            'cairo' => ['Cairo', 'family=Cairo', true],
            'tajawal' => ['Tajawal', 'family=Tajawal', true],
            'almarai' => ['Almarai', 'family=Almarai', true],
            'unknown font falls back' => ['Comic Sans', 'family=Cairo', true],
            'missing font falls back' => ['', 'family=Cairo', true],
        ];
    }

    #[DataProvider('fonts')]
    public function test_the_chosen_font_is_loaded(string $font, string $expected, bool $usesGoogleFonts): void
    {
        $tenant = $this->tenant();

        $tenant->settings->update(['primary_font' => $font]);

        $this->get('/site/demo')
            ->assertOk()
            ->assertSee($expected, false);
    }

    public function test_the_primary_and_secondary_colors_become_css_variables(): void
    {
        $tenant = $this->tenant();

        $tenant->settings->update([
            'primary_color' => '#112233',
            'secondary_color' => '#445566',
        ]);

        $this->get('/site/demo')
            ->assertOk()
            ->assertSee('--primary: #112233', false)
            ->assertSee('--secondary: #445566', false);
    }

    public function test_items_without_an_offer_show_the_regular_price(): void
    {
        $tenant = $this->tenant();

        $item = $this->availableItem($tenant, ['title' => 'كبسة', 'price' => 150]);

        Offer::factory()->for($tenant)->hasAttached($item)->create([
            'offer_price' => 120,
            'is_active' => false,
        ]);

        $response = $this->get('/site/demo');

        $response->assertOk();
        $response->assertDontSee('عروض خاصة');
        $response->assertSee('150.00');
        $response->assertDontSee('120.00');
    }

    public function test_an_active_single_offer_styles_the_item_card_in_the_grid(): void
    {
        $tenant = $this->tenant();

        $item = $this->availableItem($tenant, ['title' => 'كبسة', 'price' => 200]);

        Offer::factory()->for($tenant)->hasAttached($item)->create([
            'offer_price' => 150,
            'is_active' => true,
        ]);

        $response = $this->get('/site/demo');

        $response->assertOk();
        $response->assertDontSee('عروض خاصة');
        $response->assertSee('<span class="badge badge--offer">عرض</span>', false);
        $response->assertSee('200.00');
        $response->assertSee('150.00');
    }

    public function test_an_offer_on_an_unavailable_item_is_ignored(): void
    {
        $tenant = $this->tenant();
        $item = $this->availableItem($tenant, ['title' => 'مخفي', 'price' => 100, 'is_available' => false]);

        Offer::factory()->for($tenant)->hasAttached($item)->create(['offer_price' => 50, 'is_active' => true]);

        $this->get('/site/demo')
            ->assertOk()
            ->assertDontSee('عروض خاصة');
    }

    public function test_a_combo_offer_does_not_style_the_items_in_the_grid(): void
    {
        $tenant = $this->tenant();

        $kebab = $this->availableItem($tenant, ['title' => 'كباب', 'price' => 200]);
        $shawarma = $this->availableItem($tenant, ['title' => 'شاورما', 'price' => 100]);

        $offer = Offer::factory()->for($tenant)->create([
            'offer_price' => 210,
            'is_active' => true,
            'title' => 'كومبو الشيش',
        ]);
        $offer->items()->attach([$kebab->getKey(), $shawarma->getKey()]);

        $response = $this->get('/site/demo');

        $response->assertOk();
        // The grid cards keep their plain prices; only the combo section discounts.
        $response->assertDontSee('<span class="badge badge--offer">عرض</span>', false)
            ->assertSee('200.00')
            ->assertSee('100.00')
            ->assertSee('عروض خاصة');
    }

    public function test_combo_offers_render_in_their_own_section(): void
    {
        $tenant = $this->tenant();

        $kebab = $this->availableItem($tenant, ['title' => 'قسم كباب', 'price' => 200]);
        $shawarma = $this->availableItem($tenant, ['title' => 'قسم شاورما', 'price' => 100]);

        $offer = Offer::factory()->for($tenant)->create([
            'offer_price' => 210,
            'is_active' => true,
            'title' => null,
        ]);
        $offer->items()->attach([$kebab->getKey(), $shawarma->getKey()]);

        $content = $this->get('/site/demo')->assertOk()->getContent();

        $section = $this->comboSection($content);

        $this->assertStringContainsString('عروض خاصة', $section);
        $this->assertStringContainsString('كومبو خاص', $section);
        $this->assertStringContainsString('قسم كباب', $section);
        $this->assertStringContainsString('قسم شاورما', $section);
        $this->assertStringContainsString('300.00', $section);
        $this->assertStringContainsString('210.00', $section);

        // The nav link to the section only exists because a combo exists.
        $this->assertStringContainsString('href="#offers"', $content);
    }

    public function test_a_single_offer_is_not_rendered_in_the_combo_section(): void
    {
        $tenant = $this->tenant();

        $solo = $this->availableItem($tenant, ['title' => 'صنف سولو', 'price' => 200]);
        $kebab = $this->availableItem($tenant, ['title' => 'كومبو كباب', 'price' => 100]);
        $shawarma = $this->availableItem($tenant, ['title' => 'كومبو شاورما', 'price' => 100]);

        Offer::factory()->for($tenant)->hasAttached($solo)->create([
            'offer_price' => 150,
            'is_active' => true,
            'title' => 'عرض السولو',
        ]);

        $combo = Offer::factory()->for($tenant)->create([
            'offer_price' => 150,
            'is_active' => true,
            'title' => 'كومبو مزدوج',
        ]);
        $combo->items()->attach([$kebab->getKey(), $shawarma->getKey()]);

        $content = $this->get('/site/demo')->assertOk()->getContent();

        $section = $this->comboSection($content);

        $this->assertStringNotContainsString('صنف سولو', $section);
        $this->assertStringNotContainsString('عرض السولو', $section);
        $this->assertStringContainsString('كومبو مزدوج', $section);

        // The single offer still styles its own card in the grid.
        $this->assertStringContainsString('<span class="badge badge--offer">عرض</span>', $content);
    }

    public function test_the_combo_section_is_hidden_when_there_are_no_combo_offers(): void
    {
        $tenant = $this->tenant();

        $item = $this->availableItem($tenant, ['title' => 'عرض فقط', 'price' => 200]);

        Offer::factory()->for($tenant)->hasAttached($item)->create([
            'offer_price' => 140,
            'is_active' => true,
            'title' => 'عرض سولو',
        ]);

        $response = $this->get('/site/demo');

        $response->assertOk();
        $response->assertDontSee('عروض خاصة')
            ->assertDontSee('id="offers"', false)
            ->assertDontSee('href="#offers"', false)
            ->assertSee('<span class="badge badge--offer">عرض</span>', false);
    }

    public function test_empty_categories_are_not_rendered(): void
    {
        $tenant = $this->tenant();

        $category = Category::factory()->for($tenant)->create(['name' => 'قسم فارغ']);

        Item::factory()->for($tenant)->hasAttached($category)->create(['is_available' => false]);

        $this->get('/site/demo')
            ->assertOk()
            ->assertDontSee('قسم فارغ')
            ->assertSee('لا توجد أصناف متاحة حالياً.');
    }

    public function test_the_footer_links_back_to_the_landing_page(): void
    {
        $this->tenant();

        $response = $this->get('/site/demo');

        $response->assertOk();
        $response->assertSee('Powered by');
        $response->assertSee(route('landing'), false);
    }

    public function test_the_navigation_only_links_to_sections_that_exist(): void
    {
        $tenant = $this->tenant();

        $response = $this->get('/site/demo');

        $response->assertOk();
        $response->assertSee('href="#products"', false);
        $response->assertDontSee('href="#offers"', false);
        $response->assertDontSee('href="#branches"', false);
    }

    private function tenant(array $attributes = []): User
    {
        $tenant = User::factory()->create(['domain' => 'demo'] + $attributes);

        Setting::factory()->for($tenant)->create();

        return $tenant;
    }

    /**
     * The rendered combo section, which sits after the product grid and runs to
     * the end of the page.
     */
    private function comboSection(string $content): string
    {
        $offersPosition = strpos($content, 'id="offers"');

        $this->assertNotFalse($offersPosition, 'The combo section is missing.');

        return substr($content, $offersPosition);
    }

    /**
     * The per-test attributes win over the defaults, so a test can override
     * availability or price without losing the rest of the item setup.
     */
    private function availableItem(User $tenant, array $attributes = []): Item
    {
        $category = Category::factory()->for($tenant)->create();

        return Item::factory()->for($tenant)->hasAttached($category)->create(array_merge([
            'is_available' => true,
            'price' => 100,
        ], $attributes));
    }
}
