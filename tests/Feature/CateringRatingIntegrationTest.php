<?php

namespace Tests\Feature;

use App\Models\CateringMenu;
use App\Models\CateringRating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CateringRatingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_catering_menu_cascades_and_removes_all_ratings(): void
    {
        $admin = User::factory()->admin()->create();
        $firstResident = User::factory()->resident()->create();
        $secondResident = User::factory()->resident()->create();

        $menu = CateringMenu::factory()->create([
            'served_at' => Carbon::now()->subHour(),
        ]);

        CateringRating::create([
            'catering_menu_id' => $menu->id,
            'user_id' => $firstResident->id,
            'rating' => 5,
        ]);
        CateringRating::create([
            'catering_menu_id' => $menu->id,
            'user_id' => $secondResident->id,
            'rating' => 4,
        ]);

        $this->assertSame(2, CateringRating::where('catering_menu_id', $menu->id)->count());

        $this->actingAs($admin)
            ->deleteJson("/api/catering-menus/{$menu->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Menu catering berhasil dihapus.');

        $this->assertDatabaseMissing('catering_menus', ['id' => $menu->id]);
        $this->assertSame(0, CateringRating::where('catering_menu_id', $menu->id)->count());
    }

    public function test_admin_cannot_submit_rating(): void
    {
        $admin = User::factory()->admin()->create();
        $menu = CateringMenu::factory()->create([
            'served_at' => Carbon::now()->subHour(),
        ]);

        $this->actingAs($admin)
            ->putJson("/api/catering-menus/{$menu->id}/rating", [
                'rating' => 5,
            ])
            ->assertForbidden();
    }
}
