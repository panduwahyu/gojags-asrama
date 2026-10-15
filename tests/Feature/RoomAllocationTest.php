<?php

namespace Tests\Feature;

use App\Enums\Gender;
use App\Enums\RoomStatus;
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
                User::factory()->resident()->profiled('64', $gender, $religion)->create();
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
                $slots->map(fn (RoomSlot $slot) => $slot->user->religion)->unique()
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

    public function test_user_who_has_checked_out_is_allocated_again(): void
    {
        $this->seed(RoomSeeder::class);
        $admin = User::factory()->admin()->create();
        $returning = User::factory()->resident()->profiled()->create();
        Stay::factory()->for($returning)->for(Room::first())->checkedOut()->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/rooms/allocate')
            ->assertOk();

        $response->assertJsonCount(1, 'allocated');
        $response->assertJsonCount(0, 'waiting');
        $this->assertNotNull($returning->fresh()->slot);

        // Riwayat lama tetap ada; check-in berikutnya membuat riwayat baru.
        $this->actingAs($returning)->postJson('/api/my-room/check-in')->assertOk();
        $this->assertSame(2, $returning->stays()->count());
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

    public function test_slot_does_not_inherit_stale_checked_in_at(): void
    {
        $this->seed(RoomSeeder::class);
        $admin = User::factory()->admin()->create();
        $resident = User::factory()->resident()->profiled('64', Gender::Male, 'islam')->create();

        $slot = RoomSlot::whereHas('room', fn ($q) => $q->where('floor', 5))
            ->whereNull('user_id')
            ->first();
        $slot->update(['checked_in_at' => now()->subDay()]);

        $this->actingAs($admin)
            ->postJson('/api/rooms/allocate')
            ->assertOk();

        $slot->refresh();
        $this->assertSame($resident->id, $slot->user_id);
        $this->assertNull($slot->checked_in_at);
        $room = Room::withOccupancy()->findOrFail($slot->room_id);
        $this->assertSame(RoomStatus::Assigned, $room->status);

        $this->actingAs($resident)
            ->postJson('/api/my-room/check-in')
            ->assertOk();
    }

    public function test_new_cohort_assigned_to_highest_floor_with_available_slots_when_no_empty_floor(): void
    {
        $admin = User::factory()->admin()->create();

        $user5 = User::factory()->resident()->profiled('61', Gender::Male, 'islam')->create();
        $user4 = User::factory()->resident()->profiled('62', Gender::Male, 'islam')->create();
        $user3 = User::factory()->resident()->profiled('63', Gender::Male, 'islam')->create();

        $room5 = Room::factory()->create(['number' => '501', 'floor' => 5]);
        $room5->slots()->createMany([
            ['position' => 1, 'user_id' => $user5->id],
            ['position' => 2],
        ]);

        $room4 = Room::factory()->create(['number' => '401', 'floor' => 4]);
        $cohort62Male = User::factory()->resident()->profiled('62', Gender::Male, 'islam')->create();
        $room4->slots()->createMany([
            ['position' => 1, 'user_id' => $user4->id],
            ['position' => 2, 'user_id' => $cohort62Male->id],
        ]);

        $room3 = Room::factory()->create(['number' => '301', 'floor' => 3]);
        $cohort63Male = User::factory()->resident()->profiled('63', Gender::Male, 'islam')->create();
        $room3->slots()->createMany([
            ['position' => 1, 'user_id' => $user3->id],
            ['position' => 2, 'user_id' => $cohort63Male->id],
        ]);

        User::factory()->resident()->profiled('64', Gender::Male, 'islam')->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/rooms/allocate')
            ->assertOk();

        $this->assertSame(5, $response->json('allocated.0.floor'));
        $this->assertSame('501', $response->json('allocated.0.room'));
    }

    public function test_room_priority_on_same_floor(): void
    {
        $admin = User::factory()->admin()->create();

        $roomSame = Room::factory()->create(['number' => '501', 'floor' => 5]);
        $roomSame->slots()->createMany([['position' => 1], ['position' => 2]]);

        $roomDiff = Room::factory()->create(['number' => '502', 'floor' => 5]);
        $roomDiff->slots()->createMany([['position' => 1], ['position' => 2]]);

        $roomEmpty = Room::factory()->create(['number' => '503', 'floor' => 5]);
        $roomEmpty->slots()->createMany([['position' => 1], ['position' => 2]]);

        $occupantSame = User::factory()->resident()->profiled('64', Gender::Male, 'islam')->create();
        $occupantDiff = User::factory()->resident()->profiled('63', Gender::Male, 'islam')->create();

        $roomSame->slots()->where('position', 1)->update(['user_id' => $occupantSame->id]);
        $roomDiff->slots()->where('position', 1)->update(['user_id' => $occupantDiff->id]);

        $cand1 = User::factory()->resident()->profiled('64', Gender::Male, 'islam')->create(['name' => 'Cand 1']);
        $this->actingAs($admin)->postJson('/api/rooms/allocate')->assertOk();
        $this->assertSame($roomSame->id, $cand1->fresh()->slot->room_id);

        $cand2 = User::factory()->resident()->profiled('64', Gender::Male, 'islam')->create(['name' => 'Cand 2']);
        $this->actingAs($admin)->postJson('/api/rooms/allocate')->assertOk();
        $this->assertSame($roomDiff->id, $cand2->fresh()->slot->room_id);

        $cand3 = User::factory()->resident()->profiled('64', Gender::Male, 'islam')->create(['name' => 'Cand 3']);
        $this->actingAs($admin)->postJson('/api/rooms/allocate')->assertOk();
        $this->assertSame($roomEmpty->id, $cand3->fresh()->slot->room_id);
    }

    public function test_cohort_with_home_floor_five_overflows_to_floor_four_when_full(): void
    {
        $admin = User::factory()->admin()->create();

        $room5 = Room::factory()->create(['number' => '501', 'floor' => 5]);
        $room5->slots()->createMany([['position' => 1], ['position' => 2]]);

        $room4 = Room::factory()->create(['number' => '401', 'floor' => 4]);
        $room4->slots()->createMany([['position' => 1], ['position' => 2]]);

        $occ1 = User::factory()->resident()->profiled('64', Gender::Male, 'islam')->create();
        $occ2 = User::factory()->resident()->profiled('64', Gender::Male, 'islam')->create();
        $room5->slots()->where('position', 1)->update(['user_id' => $occ1->id]);
        $room5->slots()->where('position', 2)->update(['user_id' => $occ2->id]);

        User::factory()->resident()->profiled('64', Gender::Male, 'islam')->create();
        $response = $this->actingAs($admin)->postJson('/api/rooms/allocate')->assertOk();

        $this->assertSame(4, $response->json('allocated.0.floor'));
        $this->assertSame('401', $response->json('allocated.0.room'));
    }

    public function test_cohorts_are_processed_in_natural_sort_order(): void
    {
        $admin = User::factory()->admin()->create();

        $room5 = Room::factory()->create(['number' => '501', 'floor' => 5]);
        $room5->slots()->createMany([['position' => 1], ['position' => 2]]);

        $room4 = Room::factory()->create(['number' => '401', 'floor' => 4]);
        $room4->slots()->createMany([['position' => 1], ['position' => 2]]);

        User::factory()->resident()->profiled('10', Gender::Male, 'islam')->create(['name' => 'User Ten']);
        User::factory()->resident()->profiled('9', Gender::Male, 'islam')->create(['name' => 'User Nine']);

        $response = $this->actingAs($admin)->postJson('/api/rooms/allocate')->assertOk();

        $byName = collect($response->json('allocated'))->keyBy('name');
        $this->assertSame(5, $byName['User Nine']['floor']);
        $this->assertSame(4, $byName['User Ten']['floor']);
    }

    public function test_cohort_with_whitespace_is_normalized_to_same_cohort(): void
    {
        $admin = User::factory()->admin()->create();

        $room = Room::factory()->create(['number' => '501', 'floor' => 5]);
        $room->slots()->createMany([['position' => 1], ['position' => 2]]);

        User::factory()->resident()->profiled(' 64 ', Gender::Male, 'islam')->create(['name' => 'Spaced']);
        User::factory()->resident()->profiled('64', Gender::Male, 'islam')->create(['name' => 'Normal']);

        $response = $this->actingAs($admin)->postJson('/api/rooms/allocate')->assertOk();

        $this->assertCount(2, $response->json('allocated'));
        $this->assertSame('501', $response->json('allocated.0.room'));
        $this->assertSame('501', $response->json('allocated.1.room'));
    }

    public function test_allocation_succeeds_when_capacity_exceeds_physical_slots(): void
    {
        $admin = User::factory()->admin()->create();

        $room = Room::factory()->create(['number' => '501', 'floor' => 5]);
        $room->slots()->createMany([['position' => 1], ['position' => 2]]);

        config(['asrama.room_capacity' => 3]);

        User::factory()->resident()->profiled('64', Gender::Male, 'islam')->count(3)->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/rooms/allocate')
            ->assertOk();

        $this->assertCount(2, $response->json('allocated'));
        $this->assertCount(1, $response->json('waiting'));
    }

    public function test_allocation_skips_room_without_any_slots(): void
    {
        $admin = User::factory()->admin()->create();

        Room::factory()->create(['number' => '501', 'floor' => 5]);

        $roomWithSlots = Room::factory()->create(['number' => '502', 'floor' => 5]);
        $roomWithSlots->slots()->createMany([['position' => 1], ['position' => 2]]);

        User::factory()->resident()->profiled('64', Gender::Male, 'islam')->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/rooms/allocate')
            ->assertOk();

        $this->assertSame('502', $response->json('allocated.0.room'));
    }

    public function test_inactive_rooms_are_skipped(): void
    {
        $this->seed(RoomSeeder::class);
        $admin = User::factory()->admin()->create();
        Room::where('number', '501')->update(['is_active' => false]);
        $resident = User::factory()->resident()->profiled()->create();

        $response = $this->actingAs($admin)->postJson('/api/rooms/allocate')->assertOk();

        $response->assertJsonPath('allocated.0.room', '502');
        $this->assertDatabaseMissing('room_slots', ['user_id' => $resident->id, 'room_id' => Room::where('number', '501')->value('id')]);
    }

    public function test_religion_is_relaxed_before_leaving_the_resident_waiting(): void
    {
        $this->seed(RoomSeeder::class);
        $admin = User::factory()->admin()->create();
        Room::where('number', '!=', '501')->update(['is_active' => false]);
        $muslim = User::factory()->resident()->profiled('64', Gender::Male, 'islam')->create();
        Room::where('number', '501')->firstOrFail()->slots()->where('position', 1)->update(['user_id' => $muslim->id]);
        $christian = User::factory()->resident()->profiled('64', Gender::Male, 'kristen')->create();

        $response = $this->actingAs($admin)->postJson('/api/rooms/allocate')->assertOk();

        $response->assertJsonCount(1, 'allocated');
        $response->assertJsonPath('allocated.0.room', '501');
        $response->assertJsonPath('allocated.0.notes', ['Beda agama dengan teman sekamar.']);
        $this->assertNotNull($christian->fresh()->slot);
    }

    public function test_gender_is_never_relaxed(): void
    {
        $this->seed(RoomSeeder::class);
        $admin = User::factory()->admin()->create();
        Room::where('number', '!=', '501')->update(['is_active' => false]);
        $male = User::factory()->resident()->profiled('64', Gender::Male, 'islam')->create();
        Room::where('number', '501')->firstOrFail()->slots()->where('position', 1)->update(['user_id' => $male->id]);
        $female = User::factory()->resident()->profiled('64', Gender::Female, 'islam')->create();

        $response = $this->actingAs($admin)->postJson('/api/rooms/allocate')->assertOk();

        $response->assertJsonCount(0, 'allocated');
        $response->assertJsonPath('waiting.0.id', $female->id);
    }

    public function test_cohort_overflows_to_a_higher_floor_when_its_floor_and_below_are_full(): void
    {
        $this->seed(RoomSeeder::class);
        $admin = User::factory()->admin()->create();
        // Hanya kamar 301 (lantai 3) dan lantai 5 yang aktif; 301 sudah penuh oleh angkatan 64.
        Room::whereIn('floor', [4])->update(['is_active' => false]);
        Room::where('floor', 3)->where('number', '!=', '301')->update(['is_active' => false]);
        $room301 = Room::where('number', '301')->firstOrFail();
        foreach ([1, 2] as $position) {
            $member = User::factory()->resident()->profiled('64', Gender::Male, 'islam')->create();
            $room301->slots()->where('position', $position)->update(['user_id' => $member->id]);
        }
        $newcomer = User::factory()->resident()->profiled('64', Gender::Male, 'islam')->create();

        $response = $this->actingAs($admin)->postJson('/api/rooms/allocate')->assertOk();

        $response->assertJsonPath('allocated.0.floor', 5);
        $response->assertJsonPath('allocated.0.notes', ['Penempatan di luar lantai rombongan angkatan 64.']);
        $this->assertNotNull($newcomer->fresh()->slot);
    }

    public function test_floors_without_the_opposite_gender_are_preferred(): void
    {
        $this->seed(RoomSeeder::class);
        $admin = User::factory()->admin()->create();
        $woman = User::factory()->resident()->profiled('64', Gender::Female, 'islam')->create();
        $man = User::factory()->resident()->profiled('64', Gender::Male, 'islam')->create();

        $this->actingAs($admin)->postJson('/api/rooms/allocate')->assertOk();

        $this->assertNotSame(
            $woman->fresh()->slot->room->floor,
            $man->fresh()->slot->room->floor,
            'Laki-laki dan perempuan satu angkatan diutamakan berbeda lantai.'
        );
    }
}
