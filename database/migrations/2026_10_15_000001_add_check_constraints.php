<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menjaga di basis data aturan yang sebelumnya hanya dijaga FormRequest (audit integritas data).
 */
return new class extends Migration
{
    /** @var array<string, array{0: string, 1: string}> */
    private array $constraints = [
        'chk_catering_ratings_rating' => ['catering_ratings', 'rating BETWEEN 1 AND 5'],
        'chk_leave_permits_period' => ['leave_permits', 'returns_at > leaves_at'],
        'chk_laundry_orders_cost' => ['laundry_orders', 'cost IS NULL OR cost >= 0'],
        'chk_laundry_orders_set_count' => ['laundry_orders', 'set_count >= 1'],
    ];

    public function up(): void
    {
        if (! $this->supportsChecks()) {
            return;
        }

        foreach ($this->constraints as $name => [$table, $expression]) {
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$name} CHECK ({$expression})");
        }
    }

    public function down(): void
    {
        if (! $this->supportsChecks()) {
            return;
        }

        foreach ($this->constraints as $name => [$table]) {
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT {$name}");
        }
    }

    private function supportsChecks(): bool
    {
        return in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);
    }
};
