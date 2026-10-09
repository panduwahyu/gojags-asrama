<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const TABLES = ['kamar', 'laundry', 'catering'];

    public function up(): void
    {
        if (DB::table('asrama_records')->whereNotIn('category', self::TABLES)->exists()) {
            throw new RuntimeException('Kategori data lama tidak dikenal. Migrasi dihentikan agar data tidak hilang.');
        }

        foreach (self::TABLES as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('detail');
                $table->string('status');
                $table->date('date');
                $table->timestamps();
            });

            DB::table('asrama_records')->where('category', $tableName)->orderBy('id')->chunkById(500, function ($records) use ($tableName) {
                DB::table($tableName)->insert($records->map(function ($record) {
                    $data = (array) $record;
                    unset($data['category']);
                    return $data;
                })->all());
            });

            if (DB::table($tableName)->count() !== DB::table('asrama_records')->where('category', $tableName)->count()) {
                throw new RuntimeException('Jumlah data hasil migrasi tidak sesuai untuk tabel '.$tableName.'.');
            }
        }

        Schema::drop('asrama_records');
    }

    public function down(): void
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

        foreach (self::TABLES as $tableName) {
            DB::table($tableName)->orderBy('id')->chunkById(500, function ($records) use ($tableName) {
                foreach ($records as $record) {
                    $data = (array) $record;
                    $data['category'] = $tableName;
                    // Independent service tables can reuse the same ID after the split.
                    if (DB::table('asrama_records')->where('id', $data['id'])->exists()) {
                        unset($data['id']);
                    }
                    DB::table('asrama_records')->insert($data);
                }
            });
        }

        foreach (self::TABLES as $tableName) {
            Schema::drop($tableName);
        }
    }
};
