<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('catering', function (Blueprint $table) {
            $table->unsignedInteger('menu')->nullable();
            $table->string('penyedia', 255)->nullable();
            $table->dateTime('waktu_catering')->nullable();
        });
        Schema::create('catering_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catering_id')->constrained('catering')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->timestamps();
            $table->unique(['catering_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catering_ratings');
        Schema::table('catering', fn (Blueprint $table) => $table->dropColumn(['menu', 'penyedia', 'waktu_catering']));
    }
};
