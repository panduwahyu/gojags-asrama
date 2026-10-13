<?php

namespace Tests\Feature;

use App\Enums\RoomStatus;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyRoomTest extends TestCase
{
    use RefreshDatabase;

    public function test_unallocated_resident_sees_null_room_and_cannot_check_in(): void
    {
        $resident = User::factory()->resident()->create();

        $this->actingAs($resident)
            ->getJson('/api/my-room')
            ->assertOk()
            ->assertJsonPath('data.room', null)
            ->assertJsonPath('data.stays', []);

        $this->actingAs($resident)
            ->postJson('/api/my-room/check-in')
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'room' => 'Anda belum memiliki alokasi kamar.',
            ]);
    }

    public function test_check_in_and_check_out_lifecycle_updates_stays_slots_and_room_status(): void
    {
        $resident = User::factory()->resident()->create(['name' => 'Budi Santoso']);
        $room = Room::factory()->create(['number' => '501', 'floor' => 5]);
        $slot = $room->slots()->create(['position' => 1, 'user_id' => $resident->id]);
        $room->slots()->create(['position' => 2]);

        $this->assertSame(
            RoomStatus::Assigned,
            Room::withOccupancy()->findOrFail($room->id)->status
        );

        // Check-out sebelum check-in -> 422
        $this->actingAs($resident)
            ->postJson('/api/my-room/check-out')
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'room' => 'Anda harus check-in sebelum check-out.',
            ]);

        // Check-in berhasil
        $this->actingAs($resident)
            ->postJson('/api/my-room/check-in')
            ->assertOk()
            ->assertJsonPath('message', 'Check-in berhasil.');

        $this->assertNotNull($slot->fresh()->checked_in_at);
        $this->assertDatabaseHas('stays', [
            'user_id' => $resident->id,
            'room_id' => $room->id,
            'user_name' => 'Budi Santoso',
            'room_number' => '501',
            'floor' => 5,
            'checked_out_at' => null,
        ]);

        $this->assertSame(
            RoomStatus::Occupied,
            Room::withOccupancy()->findOrFail($room->id)->status
        );

        // Check-in dua kali -> 422
        $this->actingAs($resident)
            ->postJson('/api/my-room/check-in')
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'room' => 'Anda sudah check-in.',
            ]);

        // Check-out berhasil
        $this->actingAs($resident)
            ->postJson('/api/my-room/check-out')
            ->assertOk()
            ->assertJsonPath('message', 'Check-out berhasil. Slot kamar telah dilepaskan.');

        $this->assertNull($slot->fresh()->user_id);
        $this->assertNull($slot->fresh()->checked_in_at);
        $this->assertNotNull($resident->stays()->first()->checked_out_at);

        $this->assertSame(
            RoomStatus::Empty,
            Room::withOccupancy()->findOrFail($room->id)->status
        );

        $this->actingAs($resident)
            ->getJson('/api/my-room')
            ->assertOk()
            ->assertJsonPath('data.room', null)
            ->assertJsonCount(1, 'data.stays')
            ->assertJsonPath('data.stays.0.room_number', '501');
    }

    public function test_admin_and_guest_cannot_access_my_room_endpoints(): void
    {
        $admin = User::factory()->admin()->create();

        $this->getJson('/api/my-room')->assertUnauthorized();

        $this->actingAs($admin)
            ->getJson('/api/my-room')
            ->assertForbidden();
    }
}
