<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->json('working_hours')->nullable()->after('primary_font');
            $table->json('payment_methods')->nullable()->after('working_hours');
            $table->decimal('google_rating', 2, 1)->nullable()->after('payment_methods');
            $table->unsignedInteger('google_review_count')->nullable()->after('google_rating');
            $table->string('google_maps_url')->nullable()->after('google_review_count');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->dropColumn([
                'working_hours',
                'payment_methods',
                'google_rating',
                'google_review_count',
                'google_maps_url',
            ]);
        });
    }
};
