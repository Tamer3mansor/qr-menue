<?php

namespace Tests\Feature;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryResourceTest extends TestCase
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

    public function test_the_list_only_shows_the_current_tenants_categories(): void
    {
        $ownCategory = $this->createCategoryFor($this->tenant);
        $otherCategory = $this->createCategoryFor(User::factory()->create());

        Livewire::test(ListCategories::class)
            ->assertCanSeeTableRecords([$ownCategory])
            ->assertCanNotSeeTableRecords([$otherCategory]);
    }

    public function test_the_table_shows_the_item_count_for_each_category(): void
    {
        $category = Category::factory()->create(['user_id' => $this->tenant]);
        Item::factory()->count(3)->create(['user_id' => $this->tenant, 'category_id' => $category]);

        Livewire::test(ListCategories::class)
            ->assertCanSeeTableRecords([$category])
            ->assertTableColumnStateSet('items_count', 3, $category);
    }

    public function test_creating_a_category_automatically_assigns_the_tenant(): void
    {
        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name' => 'Starters',
                'sort_order' => 3,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('categories', [
            'name' => 'Starters',
            'sort_order' => 3,
            'user_id' => $this->tenant->id,
        ]);
    }

    public function test_the_category_name_is_required(): void
    {
        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);
    }

    public function test_a_tenant_cannot_open_another_tenants_category(): void
    {
        $otherCategory = $this->createCategoryFor(User::factory()->create());

        $response = $this->actingAs($this->tenant)->get(
            route('filament.admin.resources.categories.edit', [
                'tenant' => $this->tenant->id,
                'record' => $otherCategory->getKey(),
            ])
        );

        $response->assertNotFound();
    }

    public function test_the_resource_is_scoped_to_the_tenant(): void
    {
        $ownCategory = $this->createCategoryFor($this->tenant);
        $this->createCategoryFor(User::factory()->create());

        $this->assertTrue(Category::hasGlobalScope('admin_tenancy'));
        $this->assertCount(2, Category::query()->withoutGlobalScopes()->get());
        $this->assertEqualsCanonicalizing(
            [$ownCategory->name],
            CategoryResource::getEloquentQuery()->pluck('name')->all()
        );
    }
}
