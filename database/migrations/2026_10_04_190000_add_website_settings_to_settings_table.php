<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->string('hero_title')->nullable()->after('secondary_color');
            $table->string('hero_subtitle')->nullable()->after('hero_title');
            $table->string('hero_image')->nullable()->after('hero_subtitle');
            $table->boolean('show_offers_ticker')->default(true)->after('hero_image');
            $table->json('branches')->nullable()->after('show_offers_ticker');
            $table->json('social_links')->nullable()->after('branches');
            $table->string('seo_title')->nullable()->after('social_links');
            $table->string('seo_description')->nullable()->after('seo_title');
            $table->string('seo_keywords')->nullable()->after('seo_description');
            $table->string('primary_font')->default('Cairo')->after('seo_keywords');
        });

        Schema::table('items', function (Blueprint $table): void {
            $table->boolean('is_featured')->default(false)->after('is_available');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->dropColumn([
                'hero_title',
                'hero_subtitle',
                'hero_image',
                'show_offers_ticker',
                'branches',
                'social_links',
                'seo_title',
                'seo_description',
                'seo_keywords',
                'primary_font',
            ]);
        });

        Schema::table('items', function (Blueprint $table): void {
            $table->dropColumn('is_featured');
        });
    }
};
