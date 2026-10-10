<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryItemPivotTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_item_can_belong_to_multiple_categories(): void
    {
        $user = User::factory()->create();

        $starters = Category::factory()->create(['user_id' => $user, 'name' => 'Starters']);
        $grill = Category::factory()->create(['user_id' => $user, 'name' => 'Grill']);

        $item = Item::factory()->create(['user_id' => $user, 'title' => 'Mixed Grill Platter']);

        $item->categories()->attach([$starters->getKey(), $grill->getKey()]);

        $this->assertEqualsCanonicalizing(
            [$starters->getKey(), $grill->getKey()],
            $item->categories()->pluck('categories.id')->all(),
        );

        $this->assertEqualsCanonicalizing(
            [$item->getKey()],
            $starters->items()->pluck('items.id')->all(),
        );

        $this->assertEqualsCanonicalizing(
            [$item->getKey()],
            $grill->items()->pluck('items.id')->all(),
        );
    }

    public function test_the_factory_can_attach_a_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user]);

        $item = Item::factory()->hasAttached($category)->create(['user_id' => $user]);

        $this->assertEqualsCanonicalizing(
            [$category->getKey()],
            $item->categories()->pluck('categories.id')->all(),
        );
    }

    public function test_deleting_a_category_removes_only_its_pivot_rows(): void
    {
        $user = User::factory()->create();

        $starters = Category::factory()->create(['user_id' => $user, 'name' => 'Starters']);
        $grill = Category::factory()->create(['user_id' => $user, 'name' => 'Grill']);

        $item = Item::factory()->create(['user_id' => $user]);
        $item->categories()->attach([$starters->getKey(), $grill->getKey()]);

        $starters->delete();

        $this->assertDatabaseMissing('category_item', ['category_id' => $starters->getKey()]);
        $this->assertDatabaseHas('category_item', [
            'category_id' => $grill->getKey(),
            'item_id' => $item->getKey(),
        ]);

        $this->assertDatabaseHas('items', ['id' => $item->getKey()]);

        $this->assertEqualsCanonicalizing(
            [$grill->getKey()],
            $item->categories()->pluck('categories.id')->all(),
        );
    }

    public function test_deleting_a_category_with_items_cascades_the_pivot_only(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user]);

        $item = Item::factory()->create(['user_id' => $user]);
        $item->categories()->attach($category->getKey());

        $category->delete();

        $this->assertDatabaseCount('category_item', 0);
        $this->assertDatabaseHas('items', ['id' => $item->getKey()]);
        $this->assertCount(0, $item->categories()->get());
    }
}
