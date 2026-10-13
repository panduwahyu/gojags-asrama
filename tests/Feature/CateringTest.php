<?php

namespace Tests\Feature;

use App\Enums\CateringStatus;
use App\Models\CateringMenu;
use App\Models\CateringRating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CateringTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_update_catering_menu_status(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson('/api/catering-menus', [
                'menu_number' => 3,
                'provider' => 'Dapur Berkah',
                'served_at' => '2026-10-14T05:00:00.000Z',
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Menu catering berhasil ditambahkan.');

        $menu = CateringMenu::firstOrFail();
        $this->assertSame(CateringStatus::Scheduled, $menu->status);

        $this->actingAs($admin)
            ->patchJson("/api/catering-menus/{$menu->id}", [
                'status' => CateringStatus::Preparing->value,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Status berhasil diperbarui.');

        $this->assertSame(CateringStatus::Preparing, $menu->fresh()->status);
    }

    public function test_resident_rating_bounds_overwrite_and_aggregates(): void
    {
        $firstResident = User::factory()->resident()->create();
        $secondResident = User::factory()->resident()->create();
        $unratedResident = User::factory()->resident()->create();
        $menu = CateringMenu::factory()->create();

        foreach ([0, 6] as $invalidRating) {
            $this->actingAs($firstResident)
                ->putJson("/api/catering-menus/{$menu->id}/rating", ['rating' => $invalidRating])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['rating']);
        }

        // First rating -> 3, then overwrite -> 5 (still 1 row)
        $this->actingAs($firstResident)
            ->putJson("/api/catering-menus/{$menu->id}/rating", ['rating' => 3])
            ->assertOk()
            ->assertJsonPath('message', 'Rating catering berhasil disimpan.');

        $this->actingAs($firstResident)
            ->putJson("/api/catering-menus/{$menu->id}/rating", ['rating' => 5])
            ->assertOk();

        $this->assertSame(1, CateringRating::count());

        // Second resident rates 4 -> average 4.5, count 2
        $this->actingAs($secondResident)
            ->putJson("/api/catering-menus/{$menu->id}/rating", ['rating' => 4])
            ->assertOk();

        $this->actingAs($firstResident)
            ->getJson('/api/catering-menus')
            ->assertOk()
            ->assertJsonPath('data.0.rating_average', 4.5)
            ->assertJsonPath('data.0.rating_count', 2)
            ->assertJsonPath('data.0.my_rating', 5);

        $this->actingAs($unratedResident)
            ->getJson('/api/catering-menus')
            ->assertOk()
            ->assertJsonPath('data.0.rating_average', 4.5)
            ->assertJsonPath('data.0.rating_count', 2)
            ->assertJsonPath('data.0.my_rating', null);
    }

    public function test_role_boundaries_for_catering_and_rating(): void
    {
        $admin = User::factory()->admin()->create();
        $resident = User::factory()->resident()->create();
        $menu = CateringMenu::factory()->create();

        $this->getJson('/api/catering-menus')->assertUnauthorized();

        $this->actingAs($resident)
            ->postJson('/api/catering-menus', [
                'menu_number' => 1,
                'provider' => 'Penyedia',
                'served_at' => '2026-10-14T05:00:00.000Z',
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->putJson("/api/catering-menus/{$menu->id}/rating", ['rating' => 5])
            ->assertForbidden();
    }
}
