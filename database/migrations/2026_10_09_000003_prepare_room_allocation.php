<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('angkatan', 100)->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('agama', 50)->nullable();
        });
        Schema::table('kamar', function (Blueprint $table) {
            $table->dropColumn('detail');
            $table->unsignedTinyInteger('lantai')->default(3);
        });
        // The old room inventory is explicitly replaced with 78 fixed rooms.
        DB::table('kamar')->delete();
        foreach ([3, 4, 5] as $floor) {
            for ($number = 1; $number <= 26; $number++) {
                DB::table('kamar')->insert([
                    'name' => (string) ($floor * 100 + $number), 'lantai' => $floor,
                    'status' => 'Kosong', 'date' => now()->toDateString(),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
        Schema::create('kamar_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kamar_id')->constrained('kamar')->cascadeOnDelete();
            $table->unsignedTinyInteger('nomor');
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['kamar_id', 'nomor']);
        });
        foreach (DB::table('kamar')->pluck('id') as $id) {
            foreach ([1, 2] as $slot) {
                DB::table('kamar_slots')->insert([
                    'kamar_id' => $id, 'nomor' => $slot, 'user_id' => null,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kamar_slots');
        Schema::table('kamar', function (Blueprint $table) {
            $table->string('detail')->default('');
            $table->dropColumn('lantai');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['angkatan', 'gender', 'agama']);
        });
        // Removed legacy rooms cannot be recovered by rolling back the schema.
    }
};
