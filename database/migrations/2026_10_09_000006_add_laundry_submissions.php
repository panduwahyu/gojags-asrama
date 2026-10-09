<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('laundry', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('jumlah_stel')->nullable();
            $table->decimal('biaya', 12, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('laundry', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() !== 'sqlite') $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'jumlah_stel', 'biaya']);
        });
    }
};
