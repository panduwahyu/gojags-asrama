<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamps();

            $table->unique(['room_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_slots');
    }
};
