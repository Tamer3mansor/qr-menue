<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('restaurant_name');
            $table->string('phone')->nullable();
            $table->string('bg_image')->nullable();
            $table->string('logo')->nullable();
            $table->string('primary_color')->default('#000000');
            $table->string('secondary_color')->default('#ffffff');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
