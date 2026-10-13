<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catering_menus', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('menu_number');
            $table->string('provider', 255);
            $table->dateTime('served_at');
            $table->string('status', 20)->default('scheduled');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catering_menus');
    }
};
