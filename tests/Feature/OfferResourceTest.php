<?php

namespace Tests\Feature;

use App\Filament\Resources\Offers\OfferResource;
use App\Filament\Resources\Offers\Pages\CreateOffer;
use App\Filament\Resources\Offers\Pages\ListOffers;
use App\Models\Item;
use App\Models\Offer;
use App\Models\Setting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OfferResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = User::factory()->create();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::auth()->login($this->tenant);
        Filament::setTenant($this->tenant);

        Filament::bootCurrentPanel();
    }

    /**
     * Filament's tenancy `creating` hook force-associates the current tenant, so the
     * tenant must be cleared to build records owned by a different user.
     */
    protected function createItemFor(User $owner, array $attributes = []): Item
    {
        Filament::setTenant(null);

        try {
            return Item::factory()->create([
                ...$attributes,
                'user_id' => $owner,
            ]);
        } finally {
            Filament::setTenant($this->tenant);
        }
    }

    protected function createOfferFor(User $owner, Item $item, array $attributes = []): Offer
    {
        Filament::setTenant(null);

        try {
            return Offer::factory()->create([
                ...$attributes,
                'user_id' => $owner,
                'item_id' => $item,
            ]);
        } finally {
            Filament::setTenant($this->tenant);
        }
    }

    public function test_the_list_only_shows_the_current_tenants_offers(): void
    {
        $ownOffer = $this->createOfferFor($this->tenant, $this->createItemFor($this->tenant));

        $otherOwner = User::factory()->create();
        $otherOffer = $this->createOfferFor($otherOwner, $this->createItemFor($otherOwner));

        Livewire::test(ListOffers::class)
            ->assertCanSeeTableRecords([$ownOffer])
            ->assertCanNotSeeTableRecords([$otherOffer]);
    }

    public function test_a_tenant_cannot_open_another_tenants_offer(): void
    {
        $otherOwner = User::factory()->create();
        $otherOffer = $this->createOfferFor($otherOwner, $this->createItemFor($otherOwner));

        $response = $this->actingAs($this->tenant)->get(
            route('filament.admin.resources.offers.edit', [
                'tenant' => $this->tenant->id,
                'record' => $otherOffer->getKey(),
            ])
        );

        $response->assertNotFound();
    }

    public function test_the_resource_is_scoped_to_the_tenant(): void
    {
        $ownOffer = $this->createOfferFor($this->tenant, $this->createItemFor($this->tenant), ['title' => 'Mine']);

        $otherOwner = User::factory()->create();
        $this->createOfferFor($otherOwner, $this->createItemFor($otherOwner), ['title' => 'Theirs']);

        $this->assertTrue(Offer::hasGlobalScope('admin_tenancy'));
        $this->assertCount(2, Offer::query()->withoutGlobalScopes()->get());
        $this->assertEqualsCanonicalizing(
            [$ownOffer->title],
            OfferResource::getEloquentQuery()->pluck('title')->all()
        );
    }

    public function test_an_expired_offer_is_not_active(): void
    {
        $item = $this->createItemFor($this->tenant);

        $offer = $this->createOfferFor($this->tenant, $item, [
            'is_active' => true,
            'expires_at' => now()->subDay(),
        ]);

        $this->assertTrue($offer->isExpired());
        $this->assertFalse($offer->is_active);
        $this->assertFalse($offer->refresh()->is_active);
        $this->assertDatabaseHas('offers', [
            'id' => $offer->getKey(),
            'is_active' => false,
        ]);
    }

    public function test_a_future_offer_stays_active(): void
    {
        $item = $this->createItemFor($this->tenant);

        $offer = $this->createOfferFor($this->tenant, $item, [
            'is_active' => true,
            'expires_at' => now()->addDay(),
        ]);

        $this->assertFalse($offer->isExpired());
        $this->assertTrue($offer->is_active);
    }

    public function test_an_offer_without_an_expiry_date_stays_active(): void
    {
        $item = $this->createItemFor($this->tenant);

        $offer = $this->createOfferFor($this->tenant, $item, [
            'is_active' => true,
            'expires_at' => null,
        ]);

        $this->assertFalse($offer->isExpired());
        $this->assertTrue($offer->is_active);
    }

    public function test_the_form_rejects_an_expiry_date_in_the_past(): void
    {
        $item = $this->createItemFor($this->tenant, ['price' => 100]);

        Livewire::test(CreateOffer::class)
            ->fillForm([
                'item_id' => $item->getKey(),
                'offer_price' => 70,
                'expires_at' => now()->subWeek()->format('Y-m-d H:i:s'),
            ])
            ->call('create')
            ->assertHasFormErrors(['expires_at']);

        $this->assertDatabaseCount('offers', 0);
    }

    public function test_the_offer_price_must_be_lower_than_the_item_price(): void
    {
        $item = $this->createItemFor($this->tenant, ['price' => 100]);

        Livewire::test(CreateOffer::class)
            ->fillForm([
                'item_id' => $item->getKey(),
                'offer_price' => 120,
            ])
            ->call('create')
            ->assertHasFormErrors(['offer_price']);

        $this->assertDatabaseCount('offers', 0);
    }

    public function test_the_offer_price_may_not_equal_the_item_price(): void
    {
        $item = $this->createItemFor($this->tenant, ['price' => 100]);

        Livewire::test(CreateOffer::class)
            ->fillForm([
                'item_id' => $item->getKey(),
                'offer_price' => 100,
            ])
            ->call('create')
            ->assertHasFormErrors(['offer_price']);
    }

    public function test_a_lower_offer_price_is_accepted(): void
    {
        $item = $this->createItemFor($this->tenant, ['price' => 100]);

        Livewire::test(CreateOffer::class)
            ->fillForm([
                'item_id' => $item->getKey(),
                'title' => 'عرض اليوم',
                'offer_price' => 70,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('offers', [
            'item_id' => $item->getKey(),
            'offer_price' => 70,
            'user_id' => $this->tenant->id,
        ]);
    }

    public function test_creating_an_offer_automatically_assigns_the_tenant(): void
    {
        $item = $this->createItemFor($this->tenant, ['price' => 100]);

        Livewire::test(CreateOffer::class)
            ->fillForm([
                'item_id' => $item->getKey(),
                'offer_price' => 80,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('offers', [
            'item_id' => $item->getKey(),
            'user_id' => $this->tenant->id,
        ]);
    }

    public function test_the_offer_title_is_optional(): void
    {
        $item = $this->createItemFor($this->tenant, ['price' => 100]);

        Livewire::test(CreateOffer::class)
            ->fillForm([
                'item_id' => $item->getKey(),
                'title' => null,
                'offer_price' => 80,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('offers', [
            'item_id' => $item->getKey(),
            'title' => null,
        ]);
    }

    public function test_the_item_select_shows_the_original_price_as_a_hint(): void
    {
        Setting::factory()->create([
            'user_id' => $this->tenant,
            'currency' => 'ج.م',
        ]);

        $item = $this->createItemFor($this->tenant, ['price' => 250.75]);

        Livewire::test(CreateOffer::class)
            ->fillForm(['item_id' => $item->getKey()])
            ->assertSee('Original price: ج.م 250.75');
    }

    public function test_the_discount_percentage_is_calculated_from_the_item_price(): void
    {
        $item = $this->createItemFor($this->tenant, ['price' => 200]);

        $offer = $this->createOfferFor($this->tenant, $item, ['offer_price' => 140]);

        $this->assertSame(30, $offer->discountPercentage());

        Livewire::test(ListOffers::class)
            ->assertTableColumnStateSet('discount', 30, $offer)
            ->assertTableColumnFormattedStateSet('discount', 'خصم 30%', $offer);
    }

    public function test_the_discount_column_renders_a_dash_when_there_is_no_discount(): void
    {
        $item = $this->createItemFor($this->tenant, ['price' => 100]);

        $offer = $this->createOfferFor($this->tenant, $item, ['offer_price' => 100]);

        $this->assertSame(0, $offer->discountPercentage());

        Livewire::test(ListOffers::class)
            ->assertTableColumnFormattedStateSet('discount', '—', $offer);
    }

    public function test_the_offer_price_column_shows_the_currency_as_a_suffix(): void
    {
        Setting::factory()->create([
            'user_id' => $this->tenant,
            'currency' => 'ج.م',
        ]);

        $item = $this->createItemFor($this->tenant, ['price' => 200]);
        $offer = $this->createOfferFor($this->tenant, $item, ['offer_price' => 150]);

        Livewire::test(ListOffers::class)
            ->assertTableColumnFormattedStateSet('offer_price', '150 ج.م', $offer);
    }

    public function test_the_table_shows_the_original_item_price_as_a_description(): void
    {
        Setting::factory()->create([
            'user_id' => $this->tenant,
            'currency' => 'ج.م',
        ]);

        $item = $this->createItemFor($this->tenant, ['price' => 300.25, 'title' => 'Kebab']);
        $offer = $this->createOfferFor($this->tenant, $item, ['offer_price' => 200]);

        Livewire::test(ListOffers::class)
            ->assertTableColumnStateSet('item.title', 'Kebab', $offer)
            ->assertSee('Original price: ج.م 300.25');
    }

    public function test_the_list_can_be_filtered_by_active_offers(): void
    {
        $item = $this->createItemFor($this->tenant, ['price' => 100]);

        $active = $this->createOfferFor($this->tenant, $item, [
            'is_active' => true,
            'expires_at' => now()->addDay(),
        ]);

        $inactive = $this->createOfferFor($this->tenant, $item, [
            'is_active' => false,
            'expires_at' => null,
        ]);

        Livewire::test(ListOffers::class)
            ->filterTable('is_active', true)
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$inactive]);
    }

    public function test_the_active_filter_excludes_expired_offers(): void
    {
        $item = $this->createItemFor($this->tenant, ['price' => 100]);

        $running = $this->createOfferFor($this->tenant, $item, [
            'is_active' => true,
            'expires_at' => now()->addDay(),
        ]);

        $expired = $this->createOfferFor($this->tenant, $item, [
            'is_active' => true,
            'expires_at' => now()->subDay(),
        ]);

        $this->assertDatabaseHas('offers', ['id' => $expired->getKey(), 'is_active' => false]);

        Livewire::test(ListOffers::class)
            ->filterTable('is_active', true)
            ->assertCanSeeTableRecords([$running])
            ->assertCanNotSeeTableRecords([$expired]);
    }

    public function test_the_inactive_filter_includes_expired_offers(): void
    {
        $item = $this->createItemFor($this->tenant, ['price' => 100]);

        $running = $this->createOfferFor($this->tenant, $item, [
            'is_active' => true,
            'expires_at' => now()->addDay(),
        ]);

        $expired = $this->createOfferFor($this->tenant, $item, [
            'is_active' => true,
            'expires_at' => now()->subDay(),
        ]);

        Livewire::test(ListOffers::class)
            ->filterTable('is_active', false)
            ->assertCanSeeTableRecords([$expired])
            ->assertCanNotSeeTableRecords([$running]);
    }

    public function test_the_table_can_be_sorted_by_offer_price(): void
    {
        $item = $this->createItemFor($this->tenant, ['price' => 500]);

        $cheap = $this->createOfferFor($this->tenant, $item, ['title' => 'Cheap', 'offer_price' => 50]);
        $pricey = $this->createOfferFor($this->tenant, $item, ['title' => 'Pricey', 'offer_price' => 400]);

        Livewire::test(ListOffers::class)
            ->sortTable('offer_price')
            ->assertSeeInOrder(['Cheap', 'Pricey']);
    }

    public function test_the_activity_can_be_toggled_from_the_table(): void
    {
        $item = $this->createItemFor($this->tenant, ['price' => 100]);
        $offer = $this->createOfferFor($this->tenant, $item, ['is_active' => true, 'expires_at' => null]);

        Livewire::test(ListOffers::class)
            ->call('updateTableColumnState', 'is_active', $offer->getKey(), false);

        $this->assertFalse($offer->refresh()->is_active);
    }
}
