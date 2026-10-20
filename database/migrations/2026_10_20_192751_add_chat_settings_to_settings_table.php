<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->boolean('chat_enabled')->default(true)->after('google_maps_url');
            $table->string('chat_avatar_type')->default('robot')->after('chat_enabled');
            $table->string('chat_welcome_message')
                ->default('أهلاً! كيف أقدر أساعدك؟')
                ->after('chat_avatar_type');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->dropColumn([
                'chat_enabled',
                'chat_avatar_type',
                'chat_welcome_message',
            ]);
        });
    }
};
