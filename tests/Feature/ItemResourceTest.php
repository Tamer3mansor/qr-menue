<?php

namespace Tests\Feature;

use App\Filament\Resources\Items\ItemResource;
use App\Filament\Resources\Items\Pages\CreateItem;
use App\Filament\Resources\Items\Pages\ListItems;
use App\Models\Category;
use App\Models\Item;
use App\Models\Setting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ItemResourceTest extends TestCase
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
    protected function createCategoryFor(User $owner, array $attributes = []): Category
    {
        Filament::setTenant(null);

        try {
            return Category::factory()->create([
                ...$attributes,
                'user_id' => $owner,
            ]);
        } finally {
            Filament::setTenant($this->tenant);
        }
    }

    protected function createItemFor(User $owner, Category $category, array $attributes = []): Item
    {
        Filament::setTenant(null);

        try {
            return Item::factory()->hasAttached($category)->create([
                ...$attributes,
                'user_id' => $owner,
            ]);
        } finally {
            Filament::setTenant($this->tenant);
        }
    }

    public function test_the_list_only_shows_the_current_tenants_items(): void
    {
        $ownItem = $this->createItemFor($this->tenant, $this->createCategoryFor($this->tenant));
        $otherItem = $this->createItemFor(
            User::factory()->create(),
            $this->createCategoryFor(User::factory()->create()),
        );

        Livewire::test(ListItems::class)
            ->assertCanSeeTableRecords([$ownItem])
            ->assertCanNotSeeTableRecords([$otherItem]);
    }

    public function test_a_tenant_cannot_open_another_tenants_item(): void
    {
        $otherOwner = User::factory()->create();
        $otherItem = $this->createItemFor($otherOwner, $this->createCategoryFor($otherOwner));

        $response = $this->actingAs($this->tenant)->get(
            route('filament.admin.resources.items.edit', [
                'tenant' => $this->tenant->id,
                'record' => $otherItem->getKey(),
            ])
        );

        $response->assertNotFound();
    }

    public function test_the_resource_is_scoped_to_the_tenant(): void
    {
        $ownItem = $this->createItemFor($this->tenant, $this->createCategoryFor($this->tenant));
        $this->createItemFor(
            User::factory()->create(),
            $this->createCategoryFor(User::factory()->create()),
        );

        $this->assertTrue(Item::hasGlobalScope('admin_tenancy'));
        $this->assertCount(2, Item::query()->withoutGlobalScopes()->get());
        $this->assertEqualsCanonicalizing(
            [$ownItem->title],
            ItemResource::getEloquentQuery()->pluck('title')->all()
        );
    }

    public function test_the_category_select_only_offers_the_current_tenants_categories(): void
    {
        $ownCategory = $this->createCategoryFor($this->tenant, ['name' => 'Starters']);
        $otherCategory = $this->createCategoryFor(User::factory()->create(), ['name' => 'Foreign Food']);

        Livewire::test(CreateItem::class)
            ->assertSee($ownCategory->name)
            ->assertDontSee($otherCategory->name);
    }

    public function test_creating_an_item_automatically_assigns_the_tenant(): void
    {
        $category = $this->createCategoryFor($this->tenant);

        Livewire::test(CreateItem::class)
            ->fillForm([
                'categories' => [$category->getKey()],
                'title' => 'Grilled Kebab',
                'price' => 149.50,
                'is_available' => true,
                'sort_order' => 2,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $item = Item::query()->withoutGlobalScopes()->where('title', 'Grilled Kebab')->firstOrFail();

        $this->assertDatabaseHas('items', [
            'title' => 'Grilled Kebab',
            'user_id' => $this->tenant->id,
        ]);

        $this->assertDatabaseHas('category_item', [
            'category_id' => $category->getKey(),
            'item_id' => $item->getKey(),
        ]);
    }

    public function test_creating_an_item_can_attach_multiple_categories(): void
    {
        $firstCategory = $this->createCategoryFor($this->tenant, ['name' => 'Starters']);
        $secondCategory = $this->createCategoryFor($this->tenant, ['name' => 'Grill']);

        Livewire::test(CreateItem::class)
            ->fillForm([
                'categories' => [$firstCategory->getKey(), $secondCategory->getKey()],
                'title' => 'Mixed Grill',
                'price' => 250,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $item = Item::query()->withoutGlobalScopes()->where('title', 'Mixed Grill')->firstOrFail();

        $this->assertEqualsCanonicalizing(
            [$firstCategory->getKey(), $secondCategory->getKey()],
            $item->categories()->pluck('categories.id')->all(),
        );
    }

    public function test_the_item_title_is_required(): void
    {
        Livewire::test(CreateItem::class)
            ->fillForm(['title' => null])
            ->call('create')
            ->assertHasFormErrors(['title' => 'required']);
    }

    public function test_the_item_price_is_required(): void
    {
        Livewire::test(CreateItem::class)
            ->fillForm(['price' => null])
            ->call('create')
            ->assertHasFormErrors(['price' => 'required']);
    }

    public function test_the_item_category_is_required(): void
    {
        Livewire::test(CreateItem::class)
            ->fillForm(['categories' => []])
            ->call('create')
            ->assertHasFormErrors(['categories' => 'required']);
    }

    public function test_the_description_is_optional(): void
    {
        $category = $this->createCategoryFor($this->tenant);

        Livewire::test(CreateItem::class)
            ->fillForm([
                'categories' => [$category->getKey()],
                'title' => 'Plain Falafel',
                'price' => 25,
                'description' => null,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('items', [
            'title' => 'Plain Falafel',
            'description' => null,
        ]);
    }

    public function test_the_image_is_stored_in_the_public_items_directory(): void
    {
        Storage::fake('public');

        $category = $this->createCategoryFor($this->tenant);

        Livewire::test(CreateItem::class)
            ->fillForm([
                'categories' => [$category->getKey()],
                'title' => 'Shawarma',
                'price' => 60,
                'image' => [UploadedFile::fake()->image('shawarma.jpg')],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $item = Item::query()->withoutGlobalScopes()->firstOrFail();

        $this->assertNotNull($item->image);
        $this->assertStringStartsWith('items/', $item->image);
        Storage::disk('public')->assertExists($item->image);
    }

    public function test_the_table_shows_the_category_name(): void
    {
        $category = $this->createCategoryFor($this->tenant, ['name' => 'Desserts']);
        $item = $this->createItemFor($this->tenant, $category);

        Livewire::test(ListItems::class)
            ->assertTableColumnStateSet('categories.name', ['Desserts'], $item);
    }

    public function test_the_availability_can_be_toggled_from_the_table(): void
    {
        $item = $this->createItemFor($this->tenant, $this->createCategoryFor($this->tenant), [
            'is_available' => true,
        ]);

        Livewire::test(ListItems::class)
            ->call('updateTableColumnState', 'is_available', $item->getKey(), false);

        $this->assertFalse($item->refresh()->is_available);
    }

    public function test_the_list_can_be_searched_by_title(): void
    {
        $category = $this->createCategoryFor($this->tenant);

        $matching = $this->createItemFor($this->tenant, $category, ['title' => 'Mushroom Risotto']);
        $other = $this->createItemFor($this->tenant, $category, ['title' => 'Chicken Wrap']);

        Livewire::test(ListItems::class)
            ->searchTable('Risotto')
            ->assertCanSeeTableRecords([$matching])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_the_list_can_be_filtered_by_category(): void
    {
        $firstCategory = $this->createCategoryFor($this->tenant);
        $secondCategory = $this->createCategoryFor($this->tenant);

        $firstItem = $this->createItemFor($this->tenant, $firstCategory);
        $secondItem = $this->createItemFor($this->tenant, $secondCategory);

        Livewire::test(ListItems::class)
            ->filterTable('categories', $firstCategory->getKey())
            ->assertCanSeeTableRecords([$firstItem])
            ->assertCanNotSeeTableRecords([$secondItem]);
    }

    public function test_the_list_can_be_filtered_by_availability(): void
    {
        $category = $this->createCategoryFor($this->tenant);

        $available = $this->createItemFor($this->tenant, $category, ['is_available' => true]);
        $unavailable = $this->createItemFor($this->tenant, $category, ['is_available' => false]);

        Livewire::test(ListItems::class)
            ->filterTable('is_available', false)
            ->assertCanSeeTableRecords([$unavailable])
            ->assertCanNotSeeTableRecords([$available]);
    }

    public function test_the_list_can_be_sorted_by_price(): void
    {
        $category = $this->createCategoryFor($this->tenant);

        $cheap = $this->createItemFor($this->tenant, $category, ['title' => 'Cheap', 'price' => 10]);
        $pricey = $this->createItemFor($this->tenant, $category, ['title' => 'Pricey', 'price' => 900]);

        Livewire::test(ListItems::class)
            ->sortTable('price')
            ->assertTableColumnStateSet('price', 10, $cheap)
            ->assertTableColumnStateSet('price', 900, $pricey)
            ->assertSeeInOrder(['Cheap', 'Pricey']);
    }

    public function test_the_price_column_shows_the_tenants_currency_as_a_suffix(): void
    {
        $category = $this->createCategoryFor($this->tenant);

        Setting::factory()->create([
            'user_id' => $this->tenant,
            'currency' => 'ج.م',
        ]);

        $item = $this->createItemFor($this->tenant, $category, ['price' => 149.5]);

        Livewire::test(ListItems::class)
            ->assertTableColumnStateSet('price', 149.5, $item)
            ->assertTableColumnFormattedStateSet('price', '149.5 ج.م', $item);
    }

    public function test_the_price_column_falls_back_to_the_default_currency_without_settings(): void
    {
        $category = $this->createCategoryFor($this->tenant);
        $item = $this->createItemFor($this->tenant, $category, ['price' => 20]);

        $this->assertNull($this->tenant->settings);

        Livewire::test(ListItems::class)
            ->assertTableColumnFormattedStateSet('price', '20 '.Setting::DEFAULT_CURRENCY, $item);
    }

    public function test_each_tenant_sees_the_price_in_their_own_currency(): void
    {
        $otherOwner = User::factory()->create();

        Setting::factory()->create([
            'user_id' => $this->tenant,
            'currency' => 'ج.م',
        ]);

        Setting::factory()->create([
            'user_id' => $otherOwner,
            'currency' => 'USD',
        ]);

        $ownItem = $this->createItemFor($this->tenant, $this->createCategoryFor($this->tenant), ['price' => 100]);
        $otherItem = $this->createItemFor($otherOwner, $this->createCategoryFor($otherOwner), ['price' => 100]);

        Livewire::test(ListItems::class)
            ->assertTableColumnFormattedStateSet('price', '100 ج.م', $ownItem);

        Filament::auth()->login($otherOwner);
        Filament::setTenant($otherOwner);

        Livewire::test(ListItems::class)
            ->assertTableColumnFormattedStateSet('price', '100 USD', $otherItem);
    }

    public function test_the_price_field_shows_the_currency_as_a_suffix(): void
    {
        Setting::factory()->create([
            'user_id' => $this->tenant,
            'currency' => 'ج.م',
        ]);

        Livewire::test(CreateItem::class)
            ->assertSee('ج.م');
    }

    public function test_the_table_exposes_the_expected_columns(): void
    {
        Livewire::test(ListItems::class)
            ->assertTableColumnExists('image')
            ->assertTableColumnExists('title')
            ->assertTableColumnExists('categories.name')
            ->assertTableColumnExists('price')
            ->assertTableColumnExists('is_available')
            ->assertTableColumnExists('sort_order');
    }
}
