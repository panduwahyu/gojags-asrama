<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catering_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catering_menu_id')->constrained('catering_menus')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->timestamps();

            $table->unique(['catering_menu_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catering_ratings');
    }
};
