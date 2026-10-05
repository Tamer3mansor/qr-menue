<?php

namespace Tests\Feature;

use App\Filament\Resources\Items\Pages\EditItem;
use App\Filament\Resources\Items\Pages\ListItems;
use App\Models\Item;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ItemFeaturedTest extends TestCase
{
    use RefreshDatabase;

    protected User $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = User::factory()->create(['name' => 'مطعم ليالي الشرق']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::auth()->login($this->tenant);
        Filament::setTenant($this->tenant);

        Filament::bootCurrentPanel();
    }

    public function test_the_form_exposes_the_featured_toggle(): void
    {
        $item = $this->item();

        Livewire::test(EditItem::class, ['record' => $item->getKey()])
            ->assertOk()
            ->assertSchemaComponentExists('is_featured')
            ->fillForm(['is_featured' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($item->refresh()->is_featured);
    }

    public function test_an_item_starts_unfeatured(): void
    {
        $item = Item::factory()->create([
            'user_id' => $this->tenant->getKey(),
            'is_featured' => false,
        ]);

        $this->assertFalse($item->refresh()->is_featured);
    }

    public function test_the_flag_is_cleared_again_from_the_form(): void
    {
        $item = Item::factory()->featured()->create([
            'user_id' => $this->tenant->getKey(),
        ]);

        $this->assertTrue($item->refresh()->is_featured);

        Livewire::test(EditItem::class, ['record' => $item->getKey()])
            ->fillForm(['is_featured' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($item->refresh()->is_featured);
    }

    public function test_the_table_shows_a_star_for_a_featured_item(): void
    {
        $featured = Item::factory()->featured()->create([
            'user_id' => $this->tenant->getKey(),
            'title' => 'كباب مشوي',
        ]);

        Livewire::test(ListItems::class)
            ->assertCanSeeTableRecords([$featured])
            ->assertTableColumnStateSet('is_featured', true, $featured);
    }

    public function test_the_table_star_is_off_for_an_ordinary_item(): void
    {
        $item = Item::factory()->create([
            'user_id' => $this->tenant->getKey(),
            'title' => 'قهوة عربية',
        ]);

        Livewire::test(ListItems::class)
            ->assertCanSeeTableRecords([$item])
            ->assertTableColumnStateSet('is_featured', false, $item);
    }

    public function test_the_items_can_be_filtered_by_featured(): void
    {
        Livewire::test(ListItems::class)
            ->filterTable('is_featured', true)
            ->assertCanNotSeeTableRecords([$this->item('عادي')]);
    }

    public function test_the_table_exposes_the_flag_as_a_column_and_a_filter(): void
    {
        Livewire::test(ListItems::class)
            ->assertTableColumnExists('is_featured')
            ->assertTableFilterExists('is_featured');
    }

    private function item(string $title = 'صنف'): Item
    {
        $category = $this->tenant->categories()->create(['name' => 'مشروبات']);

        return Item::factory()->create([
            'user_id' => $this->tenant->getKey(),
            'category_id' => $category->getKey(),
            'title' => $title,
        ]);
    }
}
