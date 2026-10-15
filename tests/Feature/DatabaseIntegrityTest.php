<?php

namespace Tests\Feature;

use App\Models\CateringMenu;
use App\Models\CateringRating;
use App\Models\LaundryOrder;
use App\Models\LeavePermit;
use App\Models\Room;
use App\Models\RoomSlot;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Invarian yang dijaga basis data (constraint), bukan hanya FormRequest.
 */
class DatabaseIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_cannot_occupy_two_slots(): void
    {
        $user = User::factory()->resident()->create();
        $first = Room::factory()->create(['number' => '501']);
        $second = Room::factory()->create(['number' => '502']);
        RoomSlot::factory()->create(['room_id' => $first->id, 'position' => 1, 'user_id' => $user->id]);

        $this->expectException(QueryException::class);

        RoomSlot::factory()->create(['room_id' => $second->id, 'position' => 1, 'user_id' => $user->id]);
    }

    public function test_room_numbers_and_slot_positions_are_unique(): void
    {
        $room = Room::factory()->create(['number' => '501']);
        RoomSlot::factory()->create(['room_id' => $room->id, 'position' => 1]);

        try {
            Room::factory()->create(['number' => '501']);
            $this->fail('Nomor kamar ganda seharusnya ditolak.');
        } catch (QueryException) {
            // diharapkan
        }

        $this->expectException(QueryException::class);

        RoomSlot::factory()->create(['room_id' => $room->id, 'position' => 1]);
    }

    public function test_a_resident_can_rate_a_menu_only_once(): void
    {
        $user = User::factory()->resident()->create();
        $menu = CateringMenu::factory()->create();
        CateringRating::factory()->create(['catering_menu_id' => $menu->id, 'user_id' => $user->id]);

        $this->expectException(QueryException::class);

        CateringRating::factory()->create(['catering_menu_id' => $menu->id, 'user_id' => $user->id]);
    }

    public function test_rating_outside_one_to_five_is_rejected_by_the_database(): void
    {
        $this->expectException(QueryException::class);

        CateringRating::factory()->create(['rating' => 9]);
    }

    public function test_leave_permit_must_return_after_it_leaves_in_the_database(): void
    {
        $this->expectException(QueryException::class);

        LeavePermit::factory()->create([
            'leaves_at' => now()->addDays(2),
            'returns_at' => now()->addDay(),
        ]);
    }

    public function test_laundry_cost_cannot_be_negative_in_the_database(): void
    {
        $this->expectException(QueryException::class);

        LaundryOrder::factory()->create(['cost' => -5]);
    }

    public function test_laundry_set_count_must_be_at_least_one_in_the_database(): void
    {
        $this->expectException(QueryException::class);

        LaundryOrder::factory()->create(['set_count' => 0]);
    }
}
