<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\Offer;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferCardDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        Setting::factory()->create([
            'user_id' => $this->user,
            'restaurant_name' => 'Kebab Palace',
        ]);
    }

    public function test_the_offer_card_uses_the_uploaded_offer_image(): void
    {
        $kebab = $this->item(['price' => 200]);
        $shawarma = $this->item(['price' => 100]);

        $offer = $this->activeOffer(['image' => 'offers/banner.jpg', 'offer_price' => 150]);
        $offer->items()->attach([$kebab->getKey(), $shawarma->getKey()]);

        $this->get("/menu/{$this->user->getKey()}")
            ->assertOk()
            ->assertSee('/storage/offers/banner.jpg', false);

        $this->get("/site/{$this->user->getKey()}")
            ->assertOk()
            ->assertSee('/storage/offers/banner.jpg', false);
    }

    public function test_the_offer_card_falls_back_to_the_attached_items_image(): void
    {
        $kebab = $this->item(['image' => 'items/kebab.jpg', 'price' => 200]);
        $shawarma = $this->item(['price' => 100]);

        $offer = $this->activeOffer(['offer_price' => 70]);
        $offer->items()->attach([$kebab->getKey(), $shawarma->getKey()]);

        // The item's own card renders the same image further down the page, so
        // the image has to be found inside the combo section to prove the
        // offer card itself picked it up.
        $this->assertMenuOffersSectionContains('/storage/items/kebab.jpg');

        // Nothing after the combo section on the website renders item images,
        // so any found there can only come from an offer card.
        $this->assertWebsiteOffersSectionContains('/storage/items/kebab.jpg');
    }

    public function test_the_struck_through_price_is_the_total_of_all_attached_items(): void
    {
        $kebab = $this->item(['price' => 200]);
        $shawarma = $this->item(['price' => 100]);

        $offer = $this->activeOffer(['offer_price' => 70]);
        $offer->items()->attach([$kebab->getKey(), $shawarma->getKey()]);

        // 200 + 100 = 300 against an offer price of 70 is 77% off.
        $this->get("/menu/{$this->user->getKey()}")
            ->assertOk()
            ->assertSee('ج.م 300.00')
            ->assertSee('ج.م 70.00')
            ->assertSee('خصم 77%');

        $this->get("/site/{$this->user->getKey()}")
            ->assertOk()
            ->assertSee('300.00')
            ->assertSee('70.00')
            ->assertSee('خصم 77%');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function item(array $attributes = []): Item
    {
        $category = Category::factory()->create(['user_id' => $this->user]);

        return Item::factory()->hasAttached($category)->create([
            'user_id' => $this->user,
            ...$attributes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function activeOffer(array $attributes = [], ?Item $item = null): Offer
    {
        $offer = Offer::factory()->create([
            'user_id' => $this->user,
            'is_active' => true,
            ...$attributes,
        ]);

        if ($item !== null) {
            $offer->items()->attach($item->getKey());
        }

        return $offer;
    }

    private function assertMenuOffersSectionContains(string $needle): void
    {
        $content = $this->get("/menu/{$this->user->getKey()}")->assertOk()->getContent();

        $offersPosition = strpos($content, 'id="offers"');
        $categoriesPosition = strpos($content, 'id="category-');

        $this->assertNotFalse($offersPosition);
        $this->assertNotFalse($categoriesPosition);

        $this->assertStringContainsString(
            $needle,
            substr($content, $offersPosition, $categoriesPosition - $offersPosition),
        );
    }

    private function assertWebsiteOffersSectionContains(string $needle): void
    {
        $content = $this->get("/site/{$this->user->getKey()}")->assertOk()->getContent();

        $offersPosition = strpos($content, 'id="offers"');

        $this->assertNotFalse($offersPosition);

        $this->assertStringContainsString($needle, substr($content, $offersPosition));
    }
}
