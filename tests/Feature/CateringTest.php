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
                'dish_name' => 'Nasi Kuning Komplit',
                'meal_time' => 'breakfast',
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
                'dish_name' => 'Menu Baru',
                'meal_time' => 'lunch',
                'provider' => 'Penyedia',
                'served_at' => '2026-10-14T05:00:00.000Z',
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->putJson("/api/catering-menus/{$menu->id}/rating", ['rating' => 5])
            ->assertForbidden();
    }

    public function test_admin_can_create_catering_menu_with_dish_name_and_meal_time(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson('/api/catering-menus', [
                'menu_number' => 1,
                'dish_name' => 'Ayam Geprek',
                'meal_time' => 'lunch',
                'provider' => 'Dapur Sehat',
                'served_at' => '2026-10-15T05:00:00.000Z',
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Menu catering berhasil ditambahkan.');

        $menu = CateringMenu::firstOrFail();
        $this->assertSame('Ayam Geprek', $menu->dish_name);
        $this->assertSame('lunch', $menu->meal_time->value);

        $response = $this->actingAs($admin)->getJson('/api/catering-menus')->assertOk();
        $response->assertJsonPath('data.0.dish_name', 'Ayam Geprek');
        $response->assertJsonPath('data.0.meal_time', 'lunch');
        $response->assertJsonPath('data.0.meal_time_label', 'Siang');
    }

    public function test_dish_name_and_meal_time_validation_bounds(): void
    {
        $admin = User::factory()->admin()->create();

        // Empty dish_name -> 422
        $this->actingAs($admin)
            ->postJson('/api/catering-menus', [
                'menu_number' => 1,
                'dish_name' => '',
                'meal_time' => 'lunch',
                'provider' => 'Dapur Sehat',
                'served_at' => '2026-10-15T05:00:00.000Z',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['dish_name']);

        // dish_name > 255 -> 422
        $this->actingAs($admin)
            ->postJson('/api/catering-menus', [
                'menu_number' => 1,
                'dish_name' => str_repeat('a', 256),
                'meal_time' => 'lunch',
                'provider' => 'Dapur Sehat',
                'served_at' => '2026-10-15T05:00:00.000Z',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['dish_name']);

        // meal_time invalid -> 422
        $this->actingAs($admin)
            ->postJson('/api/catering-menus', [
                'menu_number' => 1,
                'dish_name' => 'Ayam',
                'meal_time' => 'snack',
                'provider' => 'Dapur Sehat',
                'served_at' => '2026-10-15T05:00:00.000Z',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['meal_time']);
    }

    public function test_admin_can_patch_only_dish_name(): void
    {
        $admin = User::factory()->admin()->create();
        $menu = CateringMenu::factory()->create([
            'dish_name' => 'Nasi Goreng',
            'provider' => 'Kantin Lama',
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/catering-menus/{$menu->id}", [
                'dish_name' => 'Nasi Uduk',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Menu catering berhasil diperbarui.');

        $menu->refresh();
        $this->assertSame('Nasi Uduk', $menu->dish_name);
        $this->assertSame('Kantin Lama', $menu->provider);
    }

    public function test_admin_can_patch_only_status_leaving_other_attributes_intact(): void
    {
        $admin = User::factory()->admin()->create();
        $menu = CateringMenu::factory()->create([
            'dish_name' => 'Soto Ayam',
            'status' => CateringStatus::Scheduled,
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/catering-menus/{$menu->id}", [
                'status' => CateringStatus::Preparing->value,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Status berhasil diperbarui.');

        $menu->refresh();
        $this->assertSame(CateringStatus::Preparing, $menu->status);
        $this->assertSame('Soto Ayam', $menu->dish_name);
    }

    public function test_admin_can_delete_catering_menu_and_associated_ratings_are_removed(): void
    {
        $admin = User::factory()->admin()->create();
        $resident = User::factory()->resident()->create();
        $menu = CateringMenu::factory()->create();
        $menu->ratings()->create([
            'user_id' => $resident->id,
            'rating' => 4,
        ]);

        $this->assertSame(1, CateringRating::count());

        $this->actingAs($admin)
            ->deleteJson("/api/catering-menus/{$menu->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Menu catering berhasil dihapus.');

        $this->assertDatabaseMissing('catering_menus', ['id' => $menu->id]);
        $this->assertSame(0, CateringRating::count());
    }

    public function test_unauthorized_and_forbidden_access_to_patch_and_delete_catering_menu(): void
    {
        $resident = User::factory()->resident()->create();
        $admin = User::factory()->admin()->create();
        $menu = CateringMenu::factory()->create();

        // Guest
        $this->patchJson("/api/catering-menus/{$menu->id}", ['dish_name' => 'New'])->assertUnauthorized();
        $this->deleteJson("/api/catering-menus/{$menu->id}")->assertUnauthorized();

        // Resident
        $this->actingAs($resident)
            ->patchJson("/api/catering-menus/{$menu->id}", ['dish_name' => 'New'])
            ->assertForbidden();
        $this->actingAs($resident)
            ->deleteJson("/api/catering-menus/{$menu->id}")
            ->assertForbidden();

        // Not found
        $this->actingAs($admin)
            ->patchJson('/api/catering-menus/999999', ['dish_name' => 'New'])
            ->assertNotFound();
        $this->actingAs($admin)
            ->deleteJson('/api/catering-menus/999999')
            ->assertNotFound();
    }

    public function test_search_by_dish_name_and_sort_by_meal_time(): void
    {
        $admin = User::factory()->admin()->create();
        $breakfast = CateringMenu::factory()->create([
            'dish_name' => 'Bubur Ayam Spesial',
            'meal_time' => 'breakfast',
        ]);
        $lunch = CateringMenu::factory()->create([
            'dish_name' => 'Nasi Padang Rendang',
            'meal_time' => 'lunch',
        ]);
        $dinner = CateringMenu::factory()->create([
            'dish_name' => 'Sate Kambing Madura',
            'meal_time' => 'dinner',
        ]);

        // Search dish_name
        $search = $this->actingAs($admin)
            ->getJson('/api/catering-menus?q=Rendang')
            ->assertOk();
        $this->assertCount(1, $search->json('data'));
        $this->assertSame('Nasi Padang Rendang', $search->json('data.0.dish_name'));

        // Sort meal_time asc -> breakfast, lunch, dinner (pagi, siang, malam)
        $sorted = $this->actingAs($admin)
            ->getJson('/api/catering-menus?sort=meal_time&direction=asc')
            ->assertOk();
        $this->assertSame(
            [$breakfast->id, $lunch->id, $dinner->id],
            array_column($sorted->json('data'), 'id')
        );
    }
}
