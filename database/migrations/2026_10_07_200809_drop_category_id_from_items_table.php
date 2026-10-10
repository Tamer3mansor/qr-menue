<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared(
            'INSERT INTO category_item (category_id, item_id)
             SELECT category_id, id FROM items WHERE category_id IS NOT NULL'
        );

        Schema::table('items', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('user_id')->constrained()->cascadeOnDelete();
        });

        DB::unprepared(
            'UPDATE items
             SET category_id = (SELECT category_item.category_id FROM category_item WHERE category_item.item_id = items.id)'
        );
    }
};
