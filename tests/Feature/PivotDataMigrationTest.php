<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PivotDataMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_rows_are_copied_into_the_pivot_before_the_column_is_dropped(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user]);
        $item = Item::factory()->create(['user_id' => $user]);

        $this->detachPivotForeignKeys();

        $this->restoreLegacyCategoryColumn();

        DB::table('items')->where('id', $item->getKey())->update(['category_id' => $category->getKey()]);

        $migration = require database_path('migrations/2026_10_07_200809_drop_category_id_from_items_table.php');

        $migration->up();

        $this->assertFalse(Schema::hasColumn('items', 'category_id'));
        $this->assertDatabaseHas('category_item', [
            'category_id' => $category->getKey(),
            'item_id' => $item->getKey(),
        ]);
        $this->assertDatabaseCount('category_item', 1);
        $this->assertDatabaseHas('items', ['id' => $item->getKey(), 'user_id' => $user->getKey()]);
    }

    public function test_rolling_the_category_migration_back_restores_the_column(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user]);
        $item = Item::factory()->create(['user_id' => $user]);

        $this->detachPivotForeignKeys();

        $item->categories()->attach($category->getKey());

        $migration = require database_path('migrations/2026_10_07_200809_drop_category_id_from_items_table.php');

        $migration->down();

        $this->assertTrue(Schema::hasColumn('items', 'category_id'));
        $this->assertDatabaseHas('items', [
            'id' => $item->getKey(),
            'category_id' => $category->getKey(),
        ]);
    }

    public function test_offer_rows_are_copied_into_the_pivot_before_the_column_is_dropped(): void
    {
        $user = User::factory()->create();
        $item = Item::factory()->create(['user_id' => $user]);
        $offer = Offer::factory()->create(['user_id' => $user, 'title' => 'عرض اليوم']);

        $this->detachPivotForeignKeys();

        $this->restoreLegacyItemColumn();

        DB::table('offers')->where('id', $offer->getKey())->update(['item_id' => $item->getKey()]);

        $migration = require database_path('migrations/2026_10_07_200811_drop_item_id_from_offers_table.php');

        $migration->up();

        $this->assertFalse(Schema::hasColumn('offers', 'item_id'));
        $this->assertDatabaseHas('offer_item', [
            'offer_id' => $offer->getKey(),
            'item_id' => $item->getKey(),
        ]);
        $this->assertDatabaseCount('offer_item', 1);
        $this->assertDatabaseHas('offers', ['id' => $offer->getKey(), 'user_id' => $user->getKey()]);
    }

    public function test_rolling_the_offer_migration_back_restores_the_column(): void
    {
        $user = User::factory()->create();
        $item = Item::factory()->create(['user_id' => $user]);
        $offer = Offer::factory()->create(['user_id' => $user]);

        $this->detachPivotForeignKeys();

        $offer->items()->attach($item->getKey());

        $migration = require database_path('migrations/2026_10_07_200811_drop_item_id_from_offers_table.php');

        $migration->down();

        $this->assertTrue(Schema::hasColumn('offers', 'item_id'));
        $this->assertDatabaseHas('offers', [
            'id' => $offer->getKey(),
            'item_id' => $item->getKey(),
        ]);
    }

    /**
     * Dropping a foreign key on SQLite rebuilds the table with "drop table",
     * which fires the pivot's ON DELETE CASCADE and would wipe the very rows
     * the legacy migrations just copied. PRAGMA foreign_keys is a no-op inside
     * the test transaction, so the constraints are removed instead; the
     * rollback of the test transaction restores them. MySQL (production)
     * rebuilds rows in place, so it never hits this.
     */
    private function detachPivotForeignKeys(): void
    {
        Schema::table('category_item', function (Blueprint $table): void {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['item_id']);
        });

        Schema::table('offer_item', function (Blueprint $table): void {
            $table->dropForeign(['offer_id']);
            $table->dropForeign(['item_id']);
        });
    }

    /**
     * Reshape the schema back to its legacy state so the drop migration has real work to do.
     */
    private function restoreLegacyCategoryColumn(): void
    {
        if (! Schema::hasColumn('items', 'category_id')) {
            Schema::table('items', function (Blueprint $table): void {
                $table->foreignId('category_id')->nullable()->after('user_id')->constrained()->cascadeOnDelete();
            });
        }
        DB::table('category_item')->truncate();
    }

    /**
     * Reshape the schema back to its legacy state so the drop migration has real work to do.
     */
    private function restoreLegacyItemColumn(): void
    {
        if (! Schema::hasColumn('offers', 'item_id')) {
            Schema::table('offers', function (Blueprint $table): void {
                $table->foreignId('item_id')->nullable()->after('user_id')->constrained()->cascadeOnDelete();
            });
        }
        DB::table('offer_item')->truncate();
    }
}
