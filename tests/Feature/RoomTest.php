<?php

namespace Tests\Feature;

use App\Enums\RoomStatus;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\RoomSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_rooms_with_monitoring_and_derived_statuses(): void
    {
        $this->seed(RoomSeeder::class);

        $admin = User::factory()->admin()->create();
        $residents = User::factory()->resident()->count(6)->create();

        // 501: 1 assigned, 0 checked in -> assigned
        $room501 = Room::where('number', '501')->firstOrFail();
        $room501->slots()->where('position', 1)->update([
            'user_id' => $residents[0]->id,
            'checked_in_at' => null,
        ]);

        // 502: 2 assigned, 1 checked in -> assigned
        $room502 = Room::where('number', '502')->firstOrFail();
        $room502->slots()->where('position', 1)->update([
            'user_id' => $residents[1]->id,
            'checked_in_at' => now(),
        ]);
        $room502->slots()->where('position', 2)->update([
            'user_id' => $residents[2]->id,
            'checked_in_at' => null,
        ]);

        // 503: 1 assigned, 1 checked in -> occupied
        $room503 = Room::where('number', '503')->firstOrFail();
        $room503->slots()->where('position', 1)->update([
            'user_id' => $residents[3]->id,
            'checked_in_at' => now(),
        ]);

        // 504: 2 assigned, 2 checked in -> full
        $room504 = Room::where('number', '504')->firstOrFail();
        $room504->slots()->where('position', 1)->update([
            'user_id' => $residents[4]->id,
            'checked_in_at' => now(),
        ]);
        $room504->slots()->where('position', 2)->update([
            'user_id' => $residents[5]->id,
            'checked_in_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/rooms?per_page=100')
            ->assertOk();

        $response->assertJsonPath('meta.total', 78);
        $response->assertJsonPath('monitoring', [
            'empty' => 74,
            'assigned' => 2,
            'occupied' => 1,
            'full' => 1,
        ]);

        $byNumber = collect($response->json('data'))->keyBy('number');
        $this->assertSame(RoomStatus::Assigned->value, $byNumber['501']['status']);
        $this->assertSame(RoomStatus::Assigned->value, $byNumber['502']['status']);
        $this->assertSame(RoomStatus::Occupied->value, $byNumber['503']['status']);
        $this->assertSame(RoomStatus::Full->value, $byNumber['504']['status']);
        $this->assertSame(RoomStatus::Empty->value, $byNumber['505']['status']);
    }

    public function test_rooms_can_be_sorted_by_status_and_searched_by_number(): void
    {
        $admin = User::factory()->admin()->create();
        $residents = User::factory()->resident()->count(5)->create();

        $emptyRoom = Room::factory()->create(['number' => '501', 'floor' => 5]);
        $emptyRoom->slots()->createMany([['position' => 1], ['position' => 2]]);

        $assignedRoom = Room::factory()->create(['number' => '502', 'floor' => 5]);
        $assignedRoom->slots()->createMany([
            ['position' => 1, 'user_id' => $residents[0]->id, 'checked_in_at' => null],
            ['position' => 2],
        ]);

        $occupiedRoom = Room::factory()->create(['number' => '503', 'floor' => 5]);
        $occupiedRoom->slots()->createMany([
            ['position' => 1, 'user_id' => $residents[1]->id, 'checked_in_at' => now()],
            ['position' => 2],
        ]);

        $fullRoom = Room::factory()->create(['number' => '504', 'floor' => 5]);
        $fullRoom->slots()->createMany([
            ['position' => 1, 'user_id' => $residents[2]->id, 'checked_in_at' => now()],
            ['position' => 2, 'user_id' => $residents[3]->id, 'checked_in_at' => now()],
        ]);

        $sorted = $this->actingAs($admin)
            ->getJson('/api/rooms?sort=status&direction=asc')
            ->assertOk();

        $this->assertSame(
            ['empty', 'assigned', 'occupied', 'full'],
            array_column($sorted->json('data'), 'status')
        );

        $searched = $this->actingAs($admin)
            ->getJson('/api/rooms?q=501')
            ->assertOk();

        $this->assertCount(1, $searched->json('data'));
        $this->assertSame('501', $searched->json('data.0.number'));
    }

    public function test_resident_cannot_access_rooms(): void
    {
        $resident = User::factory()->resident()->create();

        $this->actingAs($resident)
            ->getJson('/api/rooms')
            ->assertForbidden()
            ->assertJsonPath('message', 'Anda tidak memiliki izin untuk halaman ini.');
    }

    public function test_guest_cannot_access_rooms(): void
    {
        $this->getJson('/api/rooms')->assertUnauthorized();
    }
}
