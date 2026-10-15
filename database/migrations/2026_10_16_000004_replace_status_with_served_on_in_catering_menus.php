<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Keputusan pengelola no. 16: tahapan status menu dihapus; menu ganda dicegah lewat
 * UNIQUE(served_on, meal_time), dengan served_on = tanggal hidang dalam WIB.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catering_menus', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->date('served_on')->nullable()->after('served_at');
        });

        // WIB = UTC+7; served_at disimpan UTC.
        DB::statement('UPDATE catering_menus SET served_on = DATE(DATE_ADD(served_at, INTERVAL 7 HOUR))');

        Schema::table('catering_menus', function (Blueprint $table) {
            $table->date('served_on')->nullable(false)->change();
            $table->unique(['served_on', 'meal_time']);
        });
    }

    public function down(): void
    {
        Schema::table('catering_menus', function (Blueprint $table) {
            $table->dropUnique(['served_on', 'meal_time']);
            $table->dropColumn('served_on');
            $table->string('status', 20)->default('scheduled')->after('served_at');
        });
    }
};
