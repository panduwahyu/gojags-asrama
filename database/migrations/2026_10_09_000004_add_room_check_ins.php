<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('kamar_slots', function (Blueprint $table) {
            $table->timestamp('checked_in_at')->nullable();
        });
        Schema::create('kamar_stays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('kamar_id')->nullable()->constrained('kamar')->nullOnDelete();
            $table->string('user_name');
            $table->string('kamar_name');
            $table->unsignedTinyInteger('lantai');
            $table->timestamp('checked_in_at');
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'checked_out_at']);
        });
        foreach (DB::table('kamar')->pluck('id') as $id) {
            DB::table('kamar')->where('id', $id)->update([
                'status' => DB::table('kamar_slots')->where('kamar_id', $id)->whereNotNull('user_id')->exists() ? 'Assigned' : 'Kosong',
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kamar_stays');
        Schema::table('kamar_slots', fn (Blueprint $table) => $table->dropColumn('checked_in_at'));
        foreach (DB::table('kamar')->pluck('id') as $id) {
            $count = DB::table('kamar_slots')->where('kamar_id', $id)->whereNotNull('user_id')->count();
            DB::table('kamar')->where('id', $id)->update(['status' => ['Kosong', 'Terisi', 'Penuh'][$count]]);
        }
    }
};
