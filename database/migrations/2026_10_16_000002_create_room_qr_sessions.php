<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('room_qr_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('training_batches')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->string('action', 20);
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['batch_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_qr_sessions');
    }
};
