<?php

namespace Tests\Feature;

use App\Enums\Gender;
use App\Models\Room;
use App\Models\RoomSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Penempatan, pemindahan, dan pencabutan alokasi manual oleh petugas (keputusan pengelola no. 5).
 */
class RoomPlacementTest extends TestCase
{
    use RefreshDatabase;

    private function room(string $number, bool $active = true): Room
    {
        $room = Room::factory()->create([
            'number' => $number,
            'floor' => (int) substr($number, 0, 1),
            'is_active' => $active,
        ]);
        $room->slots()->createMany([['position' => 1], ['position' => 2]]);

        return $room;
    }

    private function place(User $resident, Room $room, int $position = 1, bool $checkedIn = false): void
    {
        $room->slots()->where('position', $position)->update([
            'user_id' => $resident->id,
            'checked_in_at' => $checkedIn ? now() : null,
        ]);
    }

    private function resident(string $cohort = '64', Gender $gender = Gender::Male, string $religion = 'islam'): User
    {
        return User::factory()->resident()->profiled($cohort, $gender, $religion)->create();
    }

    public function test_admin_can_place_a_resident_without_a_room(): void
    {
        $admin = User::factory()->admin()->create();
        $room = $this->room('501');
        $resident = $this->resident();

        $this->actingAs($admin)
            ->postJson("/api/rooms/{$room->id}/assign", ['user_id' => $resident->id])
            ->assertOk()
            ->assertJsonPath('message', "{$resident->name} ditempatkan di kamar 501.")
            ->assertJsonPath('warnings', []);

        $slot = RoomSlot::where('user_id', $resident->id)->firstOrFail();
        $this->assertSame($room->id, $slot->room_id);
        $this->assertNull($slot->checked_in_at);
    }

    public function test_placement_is_for_admins_only(): void
    {
        $room = $this->room('501');
        $resident = $this->resident();

        $this->postJson("/api/rooms/{$room->id}/assign", ['user_id' => $resident->id])->assertUnauthorized();

        $this->actingAs($resident)
            ->postJson("/api/rooms/{$room->id}/assign", ['user_id' => $resident->id])
            ->assertForbidden();
    }

    public function test_opposite_gender_is_refused_outright_even_when_confirmed(): void
    {
        $admin = User::factory()->admin()->create();
        $room = $this->room('501');
        $this->place($this->resident(gender: Gender::Male), $room);
        $woman = $this->resident(gender: Gender::Female);

        $this->actingAs($admin)
            ->postJson("/api/rooms/{$room->id}/assign", ['user_id' => $woman->id, 'confirm' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['room' => 'Laki-laki dan perempuan tidak boleh sekamar.']);

        $this->assertNull($woman->fresh()->slot);
    }

    public function test_inactive_full_and_incomplete_cases_are_refused(): void
    {
        $admin = User::factory()->admin()->create();
        $inactive = $this->room('501', active: false);
        $full = $this->room('502');
        $this->place($this->resident(), $full, 1);
        $this->place($this->resident(), $full, 2);
        $open = $this->room('503');

        $this->actingAs($admin)
            ->postJson("/api/rooms/{$inactive->id}/assign", ['user_id' => $this->resident()->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['room' => 'Kamar tidak aktif.']);

        $this->actingAs($admin)
            ->postJson("/api/rooms/{$full->id}/assign", ['user_id' => $this->resident()->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['room' => 'Kamar sudah penuh.']);

        $incomplete = User::factory()->resident()->create();
        $this->actingAs($admin)
            ->postJson("/api/rooms/{$open->id}/assign", ['user_id' => $incomplete->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['room' => 'Profil penghuni belum lengkap (angkatan, jenis kelamin, dan agama).']);
    }

    public function test_resident_who_already_has_a_room_must_be_moved_instead(): void
    {
        $admin = User::factory()->admin()->create();
        $current = $this->room('501');
        $other = $this->room('502');
        $resident = $this->resident();
        $this->place($resident, $current);

        $this->actingAs($admin)
            ->postJson("/api/rooms/{$other->id}/assign", ['user_id' => $resident->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['room' => 'Penghuni sudah memiliki kamar. Gunakan pemindahan kamar.']);
    }

    public function test_placing_an_admin_account_is_a_404(): void
    {
        $admin = User::factory()->admin()->create();
        $room = $this->room('501');

        $this->actingAs($admin)
            ->postJson("/api/rooms/{$room->id}/assign", ['user_id' => $admin->id])
            ->assertNotFound();
    }

    public function test_different_religion_and_cohort_ask_for_confirmation_before_saving(): void
    {
        $admin = User::factory()->admin()->create();
        $room = $this->room('501');
        $this->place($this->resident('64', Gender::Male, 'islam'), $room);
        $newcomer = $this->resident('65', Gender::Male, 'kristen');

        $this->actingAs($admin)
            ->postJson("/api/rooms/{$room->id}/assign", ['user_id' => $newcomer->id])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Penempatan perlu dikonfirmasi.')
            ->assertJsonPath('warnings', ['Beda agama dengan teman sekamar.', 'Beda angkatan dengan teman sekamar.']);

        $this->assertNull($newcomer->fresh()->slot, 'Tanpa konfirmasi tidak ada yang disimpan.');

        $this->actingAs($admin)
            ->postJson("/api/rooms/{$room->id}/assign", ['user_id' => $newcomer->id, 'confirm' => true])
            ->assertOk()
            ->assertJsonPath('warnings', ['Beda agama dengan teman sekamar.', 'Beda angkatan dengan teman sekamar.']);

        $this->assertSame($room->id, $newcomer->fresh()->slot->room_id);
    }

    public function test_placing_outside_the_cohort_floor_asks_for_confirmation(): void
    {
        $admin = User::factory()->admin()->create();
        $cohortRoom = $this->room('501');
        $elsewhere = $this->room('401');
        $this->place($this->resident('64'), $cohortRoom);
        $newcomer = $this->resident('64');

        $this->actingAs($admin)
            ->postJson("/api/rooms/{$elsewhere->id}/assign", ['user_id' => $newcomer->id])
            ->assertStatus(409)
            ->assertJsonPath('warnings', ['Penempatan di luar lantai rombongan angkatan 64.']);
    }

    public function test_admin_can_move_a_resident_who_has_not_checked_in(): void
    {
        $admin = User::factory()->admin()->create();
        $from = $this->room('501');
        $to = $this->room('502');
        $resident = $this->resident();
        $this->place($resident, $from);

        $this->actingAs($admin)
            ->postJson("/api/rooms/{$to->id}/move", ['user_id' => $resident->id])
            ->assertOk()
            ->assertJsonPath('message', "{$resident->name} dipindahkan ke kamar 502.");

        $this->assertSame($to->id, $resident->fresh()->slot->room_id);
        $this->assertSame(0, $from->slots()->whereNotNull('user_id')->count(), 'Slot lama dilepas.');
        $this->assertSame(1, RoomSlot::where('user_id', $resident->id)->count());
    }

    public function test_move_is_refused_after_check_in_for_the_same_room_and_without_a_room(): void
    {
        $admin = User::factory()->admin()->create();
        $from = $this->room('501');
        $to = $this->room('502');
        $checkedIn = $this->resident();
        $this->place($checkedIn, $from, 1, checkedIn: true);
        $assigned = $this->resident();
        $this->place($assigned, $to);
        $roomless = $this->resident();

        $this->actingAs($admin)
            ->postJson("/api/rooms/{$to->id}/move", ['user_id' => $checkedIn->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['room' => 'Penghuni yang sudah check-in tidak dapat dipindahkan. Lakukan check-out terlebih dahulu.']);

        $this->actingAs($admin)
            ->postJson("/api/rooms/{$to->id}/move", ['user_id' => $assigned->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['room' => 'Penghuni sudah berada di kamar ini.']);

        $this->actingAs($admin)
            ->postJson("/api/rooms/{$to->id}/move", ['user_id' => $roomless->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['room' => 'Penghuni belum memiliki kamar. Gunakan penempatan kamar.']);
    }

    public function test_move_keeps_the_old_room_when_confirmation_is_needed(): void
    {
        $admin = User::factory()->admin()->create();
        $from = $this->room('501');
        $to = $this->room('502');
        $resident = $this->resident('64', Gender::Male, 'islam');
        $this->place($resident, $from);
        $this->place($this->resident('64', Gender::Male, 'hindu'), $to);

        $this->actingAs($admin)
            ->postJson("/api/rooms/{$to->id}/move", ['user_id' => $resident->id])
            ->assertStatus(409)
            ->assertJsonPath('warnings', ['Beda agama dengan teman sekamar.']);

        $this->assertSame($from->id, $resident->fresh()->slot->room_id, 'Transaksi dibatalkan; kamar lama tetap.');
    }

    public function test_admin_can_unassign_a_resident_who_has_not_checked_in(): void
    {
        $admin = User::factory()->admin()->create();
        $room = $this->room('501');
        $resident = $this->resident();
        $this->place($resident, $room);

        $this->actingAs($admin)
            ->deleteJson("/api/residents/{$resident->id}/room")
            ->assertOk()
            ->assertJsonPath('message', 'Alokasi kamar dicabut.');

        $this->assertNull($resident->fresh()->slot);
    }

    public function test_unassign_is_refused_after_check_in_or_without_a_room(): void
    {
        $admin = User::factory()->admin()->create();
        $room = $this->room('501');
        $checkedIn = $this->resident();
        $this->place($checkedIn, $room, 1, checkedIn: true);

        $this->actingAs($admin)
            ->deleteJson("/api/residents/{$checkedIn->id}/room")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['room' => 'Penghuni sudah check-in. Lakukan check-out terlebih dahulu.']);

        $this->actingAs($admin)
            ->deleteJson("/api/residents/{$this->resident()->id}/room")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['room' => 'Penghuni belum memiliki kamar.']);

        $this->actingAs($admin)->deleteJson("/api/residents/{$admin->id}/room")->assertNotFound();
        $this->actingAs($this->resident())->deleteJson("/api/residents/{$checkedIn->id}/room")->assertForbidden();
    }
}
