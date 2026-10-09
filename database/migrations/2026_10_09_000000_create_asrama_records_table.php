<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('asrama_records', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->string('name');
            $table->string('detail');
            $table->string('status');
            $table->date('date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asrama_records');
    }
};
