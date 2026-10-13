<?php

namespace Tests\Feature;

use App\Enums\Gender;
use App\Models\Room;
use App\Models\RoomSlot;
use App\Models\Stay;
use App\Models\User;
use Database\Seeders\RoomSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomAllocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_cohort_is_placed_on_highest_empty_floor(): void
    {
        $this->seed(RoomSeeder::class);
        $admin = User::factory()->admin()->create();
        User::factory()->resident()->profiled('64', Gender::Male, 'islam')->count(3)->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/rooms/allocate')
            ->assertOk();

        $this->assertCount(3, $response->json('allocated'));
        $floors = array_unique(array_column($response->json('allocated'), 'floor'));
        $this->assertSame([5], array_values($floors));
    }

    public function test_second_cohort_gets_next_unused_floor_instead_of_mixing_on_floor_five(): void
    {
        $this->seed(RoomSeeder::class);
        $admin = User::factory()->admin()->create();
        $first = User::factory()->resident()->profiled('63', Gender::Male, 'islam')->create();
        $second = User::factory()->resident()->profiled('64', Gender::Male, 'islam')->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/rooms/allocate')
            ->assertOk();

        $allocated = collect($response->json('allocated'))->keyBy('id');
        $this->assertSame(5, $allocated[$first->id]['floor']);
        $this->assertSame(4, $allocated[$second->id]['floor']);
    }

    public function test_cohort_with_existing_resident_on_floor_four_continues_on_floor_four(): void
    {
        $this->seed(RoomSeeder::class);
        $admin = User::factory()->admin()->create();
        $existing = User::factory()->resident()->profiled('64', Gender::Male, 'islam')->create();
        $room401 = Room::where('number', '401')->firstOrFail();
        $room401->slots()->where('position', 1)->update(['user_id' => $existing->id]);

        $newMember = User::factory()->resident()->profiled('64', Gender::Male, 'islam')->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/rooms/allocate')
            ->assertOk();

        $this->assertSame(4, $response->json('allocated.0.floor'));
        $this->assertSame('401', $response->json('allocated.0.room'));
        $this->assertSame($newMember->id, $response->json('allocated.0.id'));
    }

    public function test_roommates_always_share_gender_and_religion(): void
    {
        $this->seed(RoomSeeder::class);
        $admin = User::factory()->admin()->create();

        foreach ([Gender::Male, Gender::Female] as $gender) {
            foreach (['islam', 'kristen'] as $religion) {
                User::factory()->resident()->profiled('64', $gender, $religion)->create();
                User::factory()->resident()->profiled('64', $gender, ' '.strtoupper($religion).' ')->create();
            }
        }

        $this->actingAs($admin)
            ->postJson('/api/rooms/allocate')
            ->assertOk()
            ->assertJsonCount(8, 'allocated');

        $occupiedSlots = RoomSlot::with('user')
            ->whereNotNull('user_id')
            ->get()
            ->groupBy('room_id');

        $this->assertCount(4, $occupiedSlots);
        foreach ($occupiedSlots as $slots) {
            $this->assertCount(2, $slots);
            $this->assertCount(1, $slots->map(fn (RoomSlot $slot) => $slot->user->gender)->unique());
            $this->assertCount(
                1,
                $slots->map(fn (RoomSlot $slot) => strtolower(trim($slot->user->religion)))->unique()
            );
        }
    }

    public function test_partially_occupied_compatible_room_is_filled_before_empty_room(): void
    {
        $this->seed(RoomSeeder::class);
        $admin = User::factory()->admin()->create();

        $first = User::factory()->resident()->profiled('64', Gender::Male, 'islam')->create();
        $room502 = Room::where('number', '502')->firstOrFail();
        $room502->slots()->where('position', 1)->update(['user_id' => $first->id]);

        $second = User::factory()->resident()->profiled('64', Gender::Male, 'islam')->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/rooms/allocate')
            ->assertOk();

        $this->assertSame('502', $response->json('allocated.0.room'));
        $this->assertSame($second->id, $response->json('allocated.0.id'));
    }

    public function test_incomplete_profile_is_reported_in_incomplete_and_gets_no_slot(): void
    {
        $this->seed(RoomSeeder::class);
        $admin = User::factory()->admin()->create();
        $incomplete = User::factory()->resident()->create([
            'cohort' => '64',
            'gender' => Gender::Male,
            'religion' => null,
        ]);

        $response = $this->actingAs($admin)
            ->postJson('/api/rooms/allocate')
            ->assertOk();

        $response->assertJsonCount(0, 'allocated');
        $response->assertJsonPath('incomplete.0.id', $incomplete->id);
        $this->assertNull($incomplete->fresh()->slot);
    }

    public function test_user_who_has_checked_out_is_not_reallocated(): void
    {
        $this->seed(RoomSeeder::class);
        $admin = User::factory()->admin()->create();
        $departed = User::factory()->resident()->profiled()->create();
        Stay::factory()->for($departed)->checkedOut()->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/rooms/allocate')
            ->assertOk();

        $response->assertJsonCount(0, 'allocated');
        $response->assertJsonCount(0, 'waiting');
        $response->assertJsonCount(0, 'incomplete');
    }

    public function test_already_assigned_resident_is_not_moved_on_subsequent_allocation(): void
    {
        $this->seed(RoomSeeder::class);
        $admin = User::factory()->admin()->create();
        $resident = User::factory()->resident()->profiled()->create();

        $this->actingAs($admin)->postJson('/api/rooms/allocate')->assertOk();
        $firstSlotId = $resident->fresh()->slot->id;

        $secondRun = $this->actingAs($admin)->postJson('/api/rooms/allocate')->assertOk();
        $secondRun->assertJsonCount(0, 'allocated');
        $this->assertSame($firstSlotId, $resident->fresh()->slot->id);
    }

    public function test_resident_without_matching_room_is_placed_in_waiting(): void
    {
        config([
            'asrama.floors' => [5],
            'asrama.rooms_per_floor' => 1,
            'asrama.room_capacity' => 2,
        ]);
        $this->seed(RoomSeeder::class);

        $admin = User::factory()->admin()->create();
        User::factory()->resident()->profiled('64', Gender::Male, 'islam')->create();
        $waitingUser = User::factory()->resident()->profiled('64', Gender::Female, 'islam')->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/rooms/allocate')
            ->assertOk();

        $response->assertJsonCount(1, 'allocated');
        $response->assertJsonPath('waiting.0.id', $waitingUser->id);
    }

    public function test_summary_message_format_and_authorization(): void
    {
        $this->seed(RoomSeeder::class);
        $admin = User::factory()->admin()->create();
        $resident = User::factory()->resident()->profiled()->create();
        User::factory()->resident()->create(['religion' => null]);

        $this->postJson('/api/rooms/allocate')->assertUnauthorized();

        $this->actingAs($resident)
            ->postJson('/api/rooms/allocate')
            ->assertForbidden();

        $this->actingAs($admin)
            ->postJson('/api/rooms/allocate')
            ->assertOk()
            ->assertJsonPath(
                'message',
                '1 user dialokasikan. 1 profil belum lengkap. 0 user menunggu kamar yang sesuai.'
            );
    }
}
