<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferItemPivotTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_offer_can_attach_multiple_items(): void
    {
        $user = User::factory()->create();

        $kebab = Item::factory()->create(['user_id' => $user, 'title' => 'Kebab', 'price' => 200]);
        $shawarma = Item::factory()->create(['user_id' => $user, 'title' => 'Shawarma', 'price' => 150]);

        $offer = Offer::factory()->create(['user_id' => $user, 'offer_price' => 100]);

        $offer->items()->attach([$kebab->getKey(), $shawarma->getKey()]);

        $this->assertEqualsCanonicalizing(
            [$kebab->getKey(), $shawarma->getKey()],
            $offer->items()->pluck('items.id')->all(),
        );

        $this->assertEqualsCanonicalizing(
            [$offer->getKey()],
            $kebab->offers()->pluck('offers.id')->all(),
        );

        $this->assertEqualsCanonicalizing(
            [$offer->getKey()],
            $shawarma->offers()->pluck('offers.id')->all(),
        );
    }

    public function test_the_factory_can_attach_an_item(): void
    {
        $user = User::factory()->create();
        $item = Item::factory()->create(['user_id' => $user]);

        $offer = Offer::factory()->hasAttached($item)->create(['user_id' => $user]);

        $this->assertEqualsCanonicalizing(
            [$item->getKey()],
            $offer->items()->pluck('items.id')->all(),
        );
    }

    public function test_the_discount_without_an_item_is_measured_from_the_total_item_price(): void
    {
        $user = User::factory()->create();

        $kebab = Item::factory()->create(['user_id' => $user, 'price' => 200]);
        $shawarma = Item::factory()->create(['user_id' => $user, 'price' => 100]);

        $offer = Offer::factory()->create(['user_id' => $user, 'offer_price' => 70]);
        $offer->items()->attach([$kebab->getKey(), $shawarma->getKey()]);

        // Against the combined 300 the offer is 77% off; against a single item
        // the discount is measured from that item's own price.
        $this->assertSame(77, $offer->discountPercentage());
        $this->assertSame(30, $offer->discountPercentage($shawarma));
        $this->assertSame(65, $offer->discountPercentage($kebab));
    }

    public function test_an_offer_without_items_reports_no_discount(): void
    {
        $offer = Offer::factory()->create(['offer_price' => 50]);

        $this->assertNull($offer->discountPercentage());
    }

    public function test_deleting_an_item_removes_only_its_pivot_rows(): void
    {
        $user = User::factory()->create();

        $kebab = Item::factory()->create(['user_id' => $user]);
        $shawarma = Item::factory()->create(['user_id' => $user]);

        $offer = Offer::factory()->create(['user_id' => $user]);
        $offer->items()->attach([$kebab->getKey(), $shawarma->getKey()]);

        $kebab->delete();

        $this->assertDatabaseMissing('offer_item', ['item_id' => $kebab->getKey()]);
        $this->assertDatabaseHas('offer_item', [
            'offer_id' => $offer->getKey(),
            'item_id' => $shawarma->getKey(),
        ]);

        $this->assertDatabaseHas('offers', ['id' => $offer->getKey()]);

        $this->assertEqualsCanonicalizing(
            [$shawarma->getKey()],
            $offer->items()->pluck('items.id')->all(),
        );
    }

    public function test_deleting_an_offer_removes_its_pivot_rows(): void
    {
        $user = User::factory()->create();
        $item = Item::factory()->create(['user_id' => $user]);

        $offer = Offer::factory()->create(['user_id' => $user]);
        $offer->items()->attach($item->getKey());

        $offer->delete();

        $this->assertDatabaseCount('offer_item', 0);
        $this->assertDatabaseHas('items', ['id' => $item->getKey()]);
    }
}
