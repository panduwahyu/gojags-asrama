<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('training_batches', function (Blueprint $table) {
            $table->id();
            $table->string('source_id', 100)->unique();
            $table->string('name');
            $table->timestamp('synced_at');
            $table->timestamps();
        });
        Schema::create('training_participants', function (Blueprint $table) {
            $table->id();
            $table->string('source_id', 100)->unique();
            $table->foreignId('batch_id')->constrained('training_batches')->cascadeOnDelete();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_participants');
        Schema::dropIfExists('training_batches');
    }
};
