<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ServiceTableMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_split_preserves_existing_records_and_timestamps(): void
    {
        $migration = require database_path('migrations/2026_10_09_000002_split_asrama_service_tables.php');
        $cateringMenus = require database_path('migrations/2026_10_09_000007_add_catering_menus_and_ratings.php');
        $cateringMenus->down();
        $laundrySubmissions = require database_path('migrations/2026_10_09_000006_add_laundry_submissions.php');
        $laundrySubmissions->down();
        $checkIns = require database_path('migrations/2026_10_09_000004_add_room_check_ins.php');
        $checkIns->down();
        $current = require database_path('migrations/2026_10_09_000003_prepare_room_allocation.php');
        $current->down();
        DB::table('kamar')->delete();
        $migration->down();
        foreach (['kamar' => 'Tersedia', 'laundry' => 'Menunggu', 'catering' => 'Terjadwal'] as $category => $status) {
            DB::table('asrama_records')->insert([
                'category' => $category, 'name' => $category, 'detail' => 'Data lama',
                'status' => $status, 'date' => '2026-10-09',
                'created_at' => '2026-10-01 08:00:00', 'updated_at' => '2026-10-02 09:00:00',
            ]);
        }
        $original = DB::table('asrama_records')->orderBy('id')->get();
        $migration->up();

        $this->assertFalse(Schema::hasTable('asrama_records'));
        foreach ($original as $record) {
            $data = (array) $record;
            unset($data['category']);
            $this->assertDatabaseCount($record->category, 1);
            $this->assertDatabaseHas($record->category, $data);
            $this->assertFalse(Schema::hasColumn($record->category, 'category'));
        }

        DB::table('laundry')->delete();
        DB::table('catering')->delete();
        $current->up();
        $checkIns->up();
        $laundrySubmissions->up();
        $cateringMenus->up();
    }

    public function test_rollback_keeps_records_with_identical_ids_in_different_tables(): void
    {
        $migration = require database_path('migrations/2026_10_09_000002_split_asrama_service_tables.php');
        $cateringMenus = require database_path('migrations/2026_10_09_000007_add_catering_menus_and_ratings.php');
        $cateringMenus->down();
        $laundrySubmissions = require database_path('migrations/2026_10_09_000006_add_laundry_submissions.php');
        $laundrySubmissions->down();
        $checkIns = require database_path('migrations/2026_10_09_000004_add_room_check_ins.php');
        $checkIns->down();
        $current = require database_path('migrations/2026_10_09_000003_prepare_room_allocation.php');
        $current->down();
        DB::table('kamar')->delete();
        foreach (['kamar', 'laundry', 'catering'] as $table) {
            DB::table($table)->insert([
                'id' => 1, 'name' => $table, 'detail' => 'Data', 'status' => 'Selesai',
                'date' => '2026-10-09', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $migration->down();
        $this->assertDatabaseCount('asrama_records', 3);
        foreach (['kamar', 'laundry', 'catering'] as $table) {
            $this->assertDatabaseHas('asrama_records', ['category' => $table, 'name' => $table]);
        }
        $migration->up();
        foreach (['kamar', 'laundry', 'catering'] as $table) {
            $this->assertDatabaseCount($table, 1);
        }

        DB::table('laundry')->delete();
        DB::table('catering')->delete();
        $current->up();
        $checkIns->up();
        $laundrySubmissions->up();
        $cateringMenus->up();
    }

    public function test_unknown_legacy_category_stops_migration_without_removing_data(): void
    {
        $migration = require database_path('migrations/2026_10_09_000002_split_asrama_service_tables.php');
        $cateringMenus = require database_path('migrations/2026_10_09_000007_add_catering_menus_and_ratings.php');
        $cateringMenus->down();
        $laundrySubmissions = require database_path('migrations/2026_10_09_000006_add_laundry_submissions.php');
        $laundrySubmissions->down();
        $checkIns = require database_path('migrations/2026_10_09_000004_add_room_check_ins.php');
        $checkIns->down();
        $current = require database_path('migrations/2026_10_09_000003_prepare_room_allocation.php');
        $current->down();
        DB::table('kamar')->delete();
        $migration->down();
        DB::table('asrama_records')->insert([
            'category' => 'unknown', 'name' => 'Tetap disimpan', 'detail' => 'Data',
            'status' => 'Selesai', 'date' => '2026-10-09',
        ]);
        try {
            $migration->up();
            $this->fail('Kategori yang tidak dikenal harus menghentikan migrasi.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Kategori data lama tidak dikenal', $exception->getMessage());
            $this->assertDatabaseHas('asrama_records', ['name' => 'Tetap disimpan']);
            $this->assertFalse(Schema::hasTable('kamar'));
        }
        // Restore the expected schema before the next RefreshDatabase test.
        DB::table('asrama_records')->delete();
        $migration->up();
        $current->up();
        $checkIns->up();
        $laundrySubmissions->up();
        $cateringMenus->up();
    }
}
