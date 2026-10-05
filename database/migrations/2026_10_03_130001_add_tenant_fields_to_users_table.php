<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('domain')->unique()->nullable()->after('email');
            $table->timestamp('subscription_expires_at')->nullable()->after('password');
            $table->boolean('is_active')->default(true)->after('subscription_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['domain', 'subscription_expires_at', 'is_active']);
        });
    }
};
