<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\Stay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StayHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_stays_history_returns_ordered_stays_descending(): void
    {
        $resident = User::factory()->resident()->create(['name' => 'Fajar Sidik']);
        $roomA = Room::factory()->create(['number' => '501', 'floor' => 5]);
        $roomB = Room::factory()->create(['number' => '402', 'floor' => 4]);

        // Stay pertama (minggu lalu, sudah checkout)
        Stay::create([
            'user_id' => $resident->id,
            'room_id' => $roomA->id,
            'user_name' => 'Fajar Sidik',
            'room_number' => '501',
            'floor' => 5,
            'checked_in_at' => Carbon::now()->subDays(7),
            'checked_out_at' => Carbon::now()->subDays(3),
        ]);

        // Stay kedua (kemarin, belum checkout)
        Stay::create([
            'user_id' => $resident->id,
            'room_id' => $roomB->id,
            'user_name' => 'Fajar Sidik',
            'room_number' => '402',
            'floor' => 4,
            'checked_in_at' => Carbon::now()->subDay(),
            'checked_out_at' => null,
        ]);

        $response = $this->actingAs($resident)
            ->getJson('/api/my-room')
            ->assertOk();

        $stays = $response->json('data.stays');
        $this->assertCount(2, $stays);
        // Urutan terbaru pertama
        $this->assertSame('402', $stays[0]['room_number']);
        $this->assertSame('501', $stays[1]['room_number']);
    }

    public function test_other_resident_cannot_see_different_user_stays(): void
    {
        $residentA = User::factory()->resident()->create(['name' => 'User A']);
        $residentB = User::factory()->resident()->create(['name' => 'User B']);
        $room = Room::factory()->create(['number' => '301', 'floor' => 3]);

        Stay::create([
            'user_id' => $residentA->id,
            'room_id' => $room->id,
            'user_name' => 'User A',
            'room_number' => '301',
            'floor' => 3,
            'checked_in_at' => Carbon::now()->subDay(),
            'checked_out_at' => null,
        ]);

        $response = $this->actingAs($residentB)
            ->getJson('/api/my-room')
            ->assertOk();

        $this->assertCount(0, $response->json('data.stays'));
    }
}
