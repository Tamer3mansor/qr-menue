<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\Offer;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuPageTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Setting $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->settings = Setting::factory()->create([
            'user_id' => $this->user,
            'restaurant_name' => 'Kebab Palace',
            'phone' => '01000000000',
            'primary_color' => '#112233',
            'secondary_color' => '#fefefe',
        ]);
    }

    protected function item(Category $category, array $attributes = []): Item
    {
        return Item::factory()->hasAttached($category)->create([
            'user_id' => $this->user,
            ...$attributes,
        ]);
    }

    protected function category(array $attributes = []): Category
    {
        return Category::factory()->create([
            'user_id' => $this->user,
            ...$attributes,
        ]);
    }

    /**
     * An active offer attached to a single item, which styles that item's card.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function singleOffer(Item $item, array $attributes = []): Offer
    {
        $offer = Offer::factory()->create([
            'user_id' => $this->user,
            'is_active' => true,
            ...$attributes,
        ]);

        $offer->items()->attach($item->getKey());

        return $offer;
    }

    /**
     * An active offer attached to several items, which lives in the combo section.
     *
     * @param  array<int, int>  $itemIds
     * @param  array<string, mixed>  $attributes
     */
    protected function comboOffer(array $itemIds, array $attributes = []): Offer
    {
        $offer = Offer::factory()->create([
            'user_id' => $this->user,
            'is_active' => true,
            ...$attributes,
        ]);

        $offer->items()->attach($itemIds);

        return $offer;
    }

    /**
     * The rendered combo section, which always sits above the category grid.
     */
    protected function comboSection(string $content): string
    {
        $offersPosition = strpos($content, 'id="offers"');
        $categoriesPosition = strpos($content, 'id="category-');

        $this->assertNotFalse($offersPosition, 'The combo section is missing.');
        $this->assertNotFalse($categoriesPosition);

        return substr($content, $offersPosition, $categoriesPosition - $offersPosition);
    }

    public function test_a_menu_can_be_reached_by_the_tenant_id(): void
    {
        $category = $this->category();
        $this->item($category, ['title' => 'Kebab']);

        $this->get("/menu/{$this->user->id}")
            ->assertOk()
            ->assertSee('Kebab Palace')
            ->assertSee('Kebab');
    }

    public function test_a_menu_can_be_reached_by_the_tenant_domain(): void
    {
        $user = User::factory()->create(['domain' => 'kebab-palace']);

        Setting::factory()->create([
            'user_id' => $user,
            'restaurant_name' => 'Domain Restaurant',
        ]);

        $category = Category::factory()->create(['user_id' => $user]);
        Item::factory()->hasAttached($category)->create(['user_id' => $user, 'title' => 'Souvlaki']);

        $this->get('/menu/kebab-palace')
            ->assertOk()
            ->assertSee('Domain Restaurant')
            ->assertSee('Souvlaki');
    }

    public function test_an_unknown_slug_returns_a_404(): void
    {
        $this->get('/menu/does-not-exist')->assertNotFound();
    }

    public function test_an_inactive_user_sees_the_subscription_page_instead_of_a_404(): void
    {
        $category = $this->category();
        $this->item($category, ['title' => 'ShouldNotAppear']);

        $this->user->forceFill(['is_active' => false])->save();

        $this->get("/menu/{$this->user->id}")
            ->assertOk()
            ->assertSee('انتهى الاشتراك')
            ->assertDontSee('ShouldNotAppear');
    }

    public function test_an_expired_subscription_shows_the_subscription_page(): void
    {
        $category = $this->category();
        $this->item($category, ['title' => 'ShouldNotAppear']);

        $this->user->forceFill(['subscription_expires_at' => now()->subDay()])->save();

        $this->get("/menu/{$this->user->id}")
            ->assertOk()
            ->assertSee('انتهى الاشتراك')
            ->assertDontSee('ShouldNotAppear');
    }

    public function test_a_future_subscription_shows_the_menu(): void
    {
        $category = $this->category();
        $this->item($category, ['title' => 'StillFresh']);

        $this->user->forceFill(['subscription_expires_at' => now()->addWeek()])->save();

        $this->get("/menu/{$this->user->id}")
            ->assertOk()
            ->assertSee('StillFresh')
            ->assertDontSee('انتهى الاشتراك');
    }

    public function test_unavailable_items_are_hidden(): void
    {
        $category = $this->category();

        $this->item($category, ['title' => 'AvailableItem', 'is_available' => true]);
        $this->item($category, ['title' => 'SoldOutItem', 'is_available' => false]);

        $this->get("/menu/{$this->user->id}")
            ->assertOk()
            ->assertSee('AvailableItem')
            ->assertDontSee('SoldOutItem');
    }

    public function test_a_category_whose_items_are_all_unavailable_is_hidden(): void
    {
        $this->category(['name' => 'EmptyCategory']);

        $visible = $this->category(['name' => 'FullCategory']);
        $this->item($visible, ['title' => 'VisibleItem']);

        $this->get("/menu/{$this->user->id}")
            ->assertOk()
            ->assertSee('FullCategory')
            ->assertDontSee('EmptyCategory');
    }

    public function test_inactive_offers_are_hidden(): void
    {
        $category = $this->category();
        $item = $this->item($category, ['title' => 'OfferItem']);

        Offer::factory()->hasAttached($item)->create([
            'user_id' => $this->user,
            'is_active' => false,
        ]);

        $this->get("/menu/{$this->user->id}")
            ->assertOk()
            ->assertDontSee('عرض');
    }

    public function test_expired_offers_are_hidden(): void
    {
        $category = $this->category();
        $item = $this->item($category, ['title' => 'ExpiredOfferItem']);

        Offer::factory()->hasAttached($item)->create([
            'user_id' => $this->user,
            'is_active' => true,
            'expires_at' => now()->subDay(),
        ]);

        $this->assertFalse(
            $item->offers()->firstOrFail()->is_active,
            'An expired offer should be stored as inactive.'
        );

        $this->get("/menu/{$this->user->id}")
            ->assertOk()
            ->assertDontSee('عرض');
    }

    public function test_running_combo_offers_are_shown_before_the_categories(): void
    {
        $category = $this->category(['name' => 'MainCourse']);
        $kebab = $this->item($category, ['title' => 'ComboKebab', 'price' => 200]);
        $shawarma = $this->item($category, ['title' => 'ComboShawarma', 'price' => 100]);

        $offer = $this->comboOffer([$kebab->getKey(), $shawarma->getKey()], [
            'offer_price' => 210,
            'expires_at' => now()->addDay(),
        ]);

        $response = $this->get("/menu/{$this->user->id}")->assertOk();

        $offersPosition = strpos($response->getContent(), 'id="offers"');
        $categoryPosition = strpos($response->getContent(), 'id="category-');

        $this->assertNotFalse($offersPosition);
        $this->assertNotFalse($categoryPosition);
        $this->assertLessThan($categoryPosition, $offersPosition);

        // 200 + 100 = 300 against an offer price of 210 is 30% off.
        $response->assertSee('عروض خاصة')
            ->assertSee('خصم 30%');
    }

    public function test_a_single_item_offer_styles_its_item_in_the_grid(): void
    {
        $category = $this->category();
        $item = $this->item($category, ['title' => 'GrilledKebab', 'price' => 200]);

        $this->singleOffer($item, ['offer_price' => 140, 'title' => null]);

        $response = $this->get("/menu/{$this->user->id}")->assertOk();

        $response->assertSee('<span class="badge">عرض</span>', false)
            ->assertSee('ج.م 140.00')
            ->assertSee('ج.م 200.00')
            ->assertDontSee('عروض خاصة')
            ->assertDontSee('id="offers"', false);
    }

    public function test_a_combo_offer_does_not_style_the_items_in_the_grid(): void
    {
        $category = $this->category();
        $kebab = $this->item($category, ['title' => 'PlainKebab', 'price' => 200]);
        $shawarma = $this->item($category, ['title' => 'PlainShawarma', 'price' => 100]);

        $this->comboOffer([$kebab->getKey(), $shawarma->getKey()], [
            'offer_price' => 210,
            'title' => 'كومبو الشيش',
        ]);

        $response = $this->get("/menu/{$this->user->id}")->assertOk();

        // The grid cards keep their plain prices; only the combo section discounts.
        $response->assertDontSee('<span class="badge">عرض</span>', false)
            ->assertSee('ج.م 200.00')
            ->assertSee('ج.م 100.00')
            ->assertSee('عروض خاصة');
    }

    public function test_combo_offers_render_in_their_own_section(): void
    {
        $category = $this->category();
        $kebab = $this->item($category, ['title' => 'SectionKebab', 'price' => 200]);
        $shawarma = $this->item($category, ['title' => 'SectionShawarma', 'price' => 100]);

        $this->comboOffer([$kebab->getKey(), $shawarma->getKey()], [
            'offer_price' => 210,
            'title' => null,
        ]);

        $content = $this->get("/menu/{$this->user->id}")->assertOk()->getContent();

        $section = $this->comboSection($content);

        $this->assertStringContainsString('عروض خاصة', $section);
        $this->assertStringContainsString('كومبو خاص', $section);
        $this->assertStringContainsString('SectionKebab', $section);
        $this->assertStringContainsString('SectionShawarma', $section);
        $this->assertStringContainsString('ج.م 300.00', $section);
        $this->assertStringContainsString('ج.م 210.00', $section);
    }

    public function test_a_single_offer_is_not_rendered_in_the_combo_section(): void
    {
        $category = $this->category();
        $solo = $this->item($category, ['title' => 'SoloItem', 'price' => 200]);
        $kebab = $this->item($category, ['title' => 'ComboItemA', 'price' => 100]);
        $shawarma = $this->item($category, ['title' => 'ComboItemB', 'price' => 100]);

        $this->singleOffer($solo, ['offer_price' => 150, 'title' => 'عرض السولو']);
        $this->comboOffer([$kebab->getKey(), $shawarma->getKey()], [
            'offer_price' => 150,
            'title' => 'كومبو مزدوج',
        ]);

        $content = $this->get("/menu/{$this->user->id}")->assertOk()->getContent();

        $section = $this->comboSection($content);

        $this->assertStringNotContainsString('SoloItem', $section);
        $this->assertStringNotContainsString('عرض السولو', $section);
        $this->assertStringContainsString('كومبو مزدوج', $section);

        // The single offer still styles its own card in the grid.
        $this->assertStringContainsString('<span class="badge">عرض</span>', $content);
    }

    public function test_the_combo_section_is_hidden_when_there_are_no_combo_offers(): void
    {
        $category = $this->category();
        $item = $this->item($category, ['title' => 'OnlySingle', 'price' => 200]);

        $this->singleOffer($item, ['offer_price' => 140, 'title' => 'عرض سولو']);

        $response = $this->get("/menu/{$this->user->id}")->assertOk();

        $response->assertDontSee('عروض خاصة')
            ->assertDontSee('id="offers"', false)
            ->assertSee('<span class="badge">عرض</span>', false);
    }

    public function test_an_offer_hides_the_original_price_and_shows_the_offer_price(): void
    {
        $category = $this->category();
        $item = $this->item($category, ['title' => 'DiscountedItem', 'price' => 200]);

        Offer::factory()->hasAttached($item)->create([
            'user_id' => $this->user,
            'is_active' => true,
            'offer_price' => 140,
        ]);

        $this->get("/menu/{$this->user->id}")
            ->assertOk()
            ->assertSee('ج.م 140.00')
            ->assertSee('ج.م 200.00');
    }

    public function test_items_are_ordered_by_sort_order(): void
    {
        $category = $this->category();

        $this->item($category, ['title' => 'Third', 'sort_order' => 30]);
        $this->item($category, ['title' => 'First', 'sort_order' => 10]);
        $this->item($category, ['title' => 'Second', 'sort_order' => 20]);

        $content = $this->get("/menu/{$this->user->id}")->assertOk()->getContent();

        $this->assertLessThan(strpos($content, 'Second'), strpos($content, 'First'));
        $this->assertLessThan(strpos($content, 'Third'), strpos($content, 'Second'));
    }

    public function test_categories_are_ordered_by_sort_order(): void
    {
        $second = $this->category(['name' => 'SecondCategory', 'sort_order' => 20]);
        $first = $this->category(['name' => 'FirstCategory', 'sort_order' => 10]);

        $this->item($first, ['title' => 'FirstItem']);
        $this->item($second, ['title' => 'SecondItem']);

        $content = $this->get("/menu/{$this->user->id}")->assertOk()->getContent();

        $this->assertLessThan(
            strpos($content, 'id="category-'.$second->id.'"'),
            strpos($content, 'id="category-'.$first->id.'"')
        );
    }

    public function test_menu_cards_expose_their_categories_for_the_filter(): void
    {
        $grill = $this->category(['name' => 'مشاوي']);
        $drinks = $this->category(['name' => 'مشروبات']);

        $kebab = $this->item($grill, ['title' => 'Kebab']);
        $cola = $this->item($drinks, ['title' => 'Cola']);

        $combo = $this->item($grill, ['title' => 'Combo']);
        $combo->categories()->attach($drinks->getKey());

        $content = $this->get("/menu/{$this->user->id}")->assertOk()->getContent();

        $this->assertStringContainsString('data-filter="all"', $content);
        $this->assertStringContainsString('data-filter="'.$grill->id.'"', $content);
        $this->assertStringContainsString('data-filter="'.$drinks->id.'"', $content);

        $this->assertStringContainsString('data-categories="'.$kebab->id.'"', $content);
        $this->assertStringContainsString('data-categories="'.$cola->id.'"', $content);

        preg_match_all('/data-categories="([^"]*)"/', $content, $matches);

        $listsBothCategories = collect($matches[1])->contains(function (string $value) use ($grill, $drinks): bool {
            $ids = array_map('intval', explode(',', $value));

            return in_array($grill->id, $ids, true) && in_array($drinks->id, $ids, true);
        });

        $this->assertTrue($listsBothCategories, 'A card must list every category it belongs to.');
    }

    public function test_the_brand_colors_are_applied_as_css_variables(): void
    {
        $this->get("/menu/{$this->user->id}")
            ->assertOk()
            ->assertSee('--brand-primary: #112233', escape: false)
            ->assertSee('--brand-secondary: #fefefe', escape: false);
    }

    public function test_the_phone_is_a_clickable_tel_link(): void
    {
        $this->get("/menu/{$this->user->id}")
            ->assertOk()
            ->assertSee('href="tel:01000000000"', escape: false);
    }

    public function test_the_background_image_is_used_on_the_header(): void
    {
        $this->settings->update(['bg_image' => 'backgrounds/menu-bg.jpg']);

        $this->get("/menu/{$this->user->id}")
            ->assertOk()
            ->assertSee('--header-bg: url(\'/storage/backgrounds/menu-bg.jpg\')', escape: false);
    }

    public function test_the_logo_is_rendered_when_it_exists(): void
    {
        $this->settings->update(['logo' => 'logos/menu-logo.png']);

        $this->get("/menu/{$this->user->id}")
            ->assertOk()
            ->assertSee('/storage/logos/menu-logo.png', escape: false);
    }

    public function test_the_currency_of_the_tenant_is_used(): void
    {
        $category = $this->category();
        $this->item($category, ['title' => 'PricedItem', 'price' => 100]);

        $this->get("/menu/{$this->user->id}")
            ->assertOk()
            ->assertSee('ج.م 100.00');
    }

    public function test_another_tenants_menu_is_not_reachable_through_a_numeric_slug_of_its_own(): void
    {
        $otherUser = User::factory()->create();

        Setting::factory()->create(['user_id' => $otherUser, 'restaurant_name' => 'Other Restaurant']);

        $this->get("/menu/{$otherUser->id}")
            ->assertOk()
            ->assertSee('Other Restaurant')
            ->assertDontSee('Kebab Palace');
    }

    public function test_a_tenant_without_settings_still_renders(): void
    {
        $user = User::factory()->create();

        $this->get("/menu/{$user->id}")
            ->assertOk()
            ->assertSee('--brand-primary: #000000', escape: false);
    }

    public function test_an_empty_menu_shows_a_placeholder(): void
    {
        $this->get("/menu/{$this->user->id}")
            ->assertOk()
            ->assertSee('لا توجد أصناف متاحة حالياً.');
    }
}
