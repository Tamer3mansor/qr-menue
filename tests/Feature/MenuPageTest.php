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
        return Item::factory()->create([
            'user_id' => $this->user,
            'category_id' => $category,
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
        Item::factory()->create(['user_id' => $user, 'category_id' => $category, 'title' => 'Souvlaki']);

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

        Offer::factory()->create([
            'user_id' => $this->user,
            'item_id' => $item,
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

        Offer::factory()->create([
            'user_id' => $this->user,
            'item_id' => $item,
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

    public function test_running_offers_are_shown_before_the_categories(): void
    {
        $category = $this->category(['name' => 'MainCourse']);
        $item = $this->item($category, ['title' => 'OfferItem', 'price' => 200]);

        Offer::factory()->create([
            'user_id' => $this->user,
            'item_id' => $item,
            'is_active' => true,
            'offer_price' => 140,
            'expires_at' => now()->addDay(),
        ]);

        $response = $this->get("/menu/{$this->user->id}")->assertOk();

        $offersPosition = strpos($response->getContent(), 'id="offers"');
        $categoryPosition = strpos($response->getContent(), 'id="category-');

        $this->assertNotFalse($offersPosition);
        $this->assertNotFalse($categoryPosition);
        $this->assertLessThan($categoryPosition, $offersPosition);

        $response->assertSee('عرض')
            ->assertSee('خصم 30%');
    }

    public function test_an_offer_hides_the_original_price_and_shows_the_offer_price(): void
    {
        $category = $this->category();
        $item = $this->item($category, ['title' => 'DiscountedItem', 'price' => 200]);

        Offer::factory()->create([
            'user_id' => $this->user,
            'item_id' => $item,
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
