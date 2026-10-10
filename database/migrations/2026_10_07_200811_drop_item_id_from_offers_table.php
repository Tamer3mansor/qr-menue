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
            'INSERT INTO offer_item (offer_id, item_id)
             SELECT id, item_id FROM offers WHERE item_id IS NOT NULL'
        );

        Schema::table('offers', function (Blueprint $table) {
            $table->dropForeign(['item_id']);
            $table->dropColumn('item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->foreignId('item_id')->nullable()->after('user_id')->constrained()->cascadeOnDelete();
        });

        DB::unprepared(
            'UPDATE offers
             SET item_id = (SELECT offer_item.item_id FROM offer_item WHERE offer_item.offer_id = offers.id)'
        );
    }
};
