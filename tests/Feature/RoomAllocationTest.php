<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\RoomAllocator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RoomAllocationTest extends TestCase
{
    use RefreshDatabase;

    private function resident(array $attributes = []): User
    {
        return User::factory()->create($attributes + [
            'role' => 'user', 'angkatan' => '2026', 'gender' => 'Laki-laki', 'agama' => 'Islam',
        ]);
    }

    public function test_inventory_has_78_empty_rooms_and_two_slots_per_room(): void
    {
        $this->assertDatabaseCount('kamar', 78);
        $this->assertDatabaseCount('kamar_slots', 156);
        $this->assertFalse(Schema::hasColumn('kamar', 'detail'));
        foreach ([3, 4, 5] as $floor) {
            $this->assertSame(26, DB::table('kamar')->where('lantai', $floor)->where('status', 'Kosong')->count());
            $this->assertDatabaseHas('kamar', ['name' => (string) ($floor * 100 + 1), 'lantai' => $floor]);
        }
    }

    public function test_same_cohort_shares_floor_and_status_follows_occupancy(): void
    {
        for ($i = 0; $i < 3; $i++) $this->resident();
        $result = app(RoomAllocator::class)->allocate();
        $this->assertCount(3, $result['allocated']);
        $this->assertSame([5], array_values(array_unique(array_column($result['allocated'], 'lantai'))));
        $this->assertSame(2, DB::table('kamar')->where('status', 'Assigned')->count());
        $this->assertSame(76, DB::table('kamar')->where('status', 'Kosong')->count());
        $before = DB::table('kamar_slots')->pluck('user_id', 'id')->all();
        $this->assertCount(0, app(RoomAllocator::class)->allocate()['allocated']);
        $this->assertSame($before, DB::table('kamar_slots')->pluck('user_id', 'id')->all());
    }

    public function test_gender_and_religion_are_never_mixed_in_a_room(): void
    {
        foreach (['Laki-laki', 'Perempuan'] as $gender) {
            foreach (['Islam', 'Kristen'] as $religion) {
                $this->resident(['gender' => $gender, 'agama' => $religion]);
                $this->resident(['gender' => $gender, 'agama' => ' '.strtoupper($religion).' ']);
            }
        }
        $this->assertCount(8, app(RoomAllocator::class)->allocate()['allocated']);
        $this->assertSame(4, DB::table('kamar')->where('status', 'Assigned')->count());
        $groups = DB::table('kamar_slots')->join('users', 'users.id', '=', 'user_id')->get()->groupBy('kamar_id');
        foreach ($groups as $people) {
            $this->assertCount(2, $people);
            $this->assertCount(1, $people->pluck('gender')->unique());
            $this->assertCount(1, $people->pluck('agama')->map(fn ($value) => strtolower(trim($value)))->unique());
        }
    }

    public function test_new_cohort_uses_separate_floor_and_full_floor_spills_down(): void
    {
        for ($i = 0; $i < 53; $i++) $this->resident(['angkatan' => '2025']);
        $other = $this->resident(['angkatan' => '2026']);
        $result = app(RoomAllocator::class)->allocate();
        $this->assertCount(54, $result['allocated']);
        $first = collect($result['allocated'])->reject(fn ($user) => $user['id'] === $other->id);
        $this->assertCount(52, $first->where('lantai', 5));
        $this->assertCount(1, $first->where('lantai', 4));
        $this->assertSame(3, collect($result['allocated'])->firstWhere('id', $other->id)['lantai']);
    }

    public function test_capacity_is_156_and_missing_profiles_and_admins_are_excluded(): void
    {
        User::factory()->create(['role' => 'admin']);
        $missing = User::factory()->create(['role' => 'user']);
        for ($i = 0; $i < 158; $i++) $this->resident();
        $result = app(RoomAllocator::class)->allocate();
        $this->assertCount(156, $result['allocated']);
        $this->assertCount(2, $result['waiting']);
        $this->assertSame([$missing->id], array_column($result['incomplete'], 'id'));
        $this->assertSame(78, DB::table('kamar')->where('status', 'Assigned')->count());
        $this->assertSame(156, DB::table('kamar_slots')->whereNotNull('user_id')->count());
    }

    public function test_admin_can_complete_profile_and_allocate_but_user_cannot(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $profile = ['angkatan' => ' 2026 ', 'gender' => 'Perempuan', 'agama' => ' ISLAM '];
        $this->postJson('/api/asrama/kamar/allocate')->assertUnauthorized();
        $this->actingAs($user)->postJson('/api/asrama/kamar/allocate')->assertForbidden();
        $this->patchJson('/api/asrama/allocation-users/'.$user->id, $profile)->assertForbidden();
        $this->actingAs($admin)->patchJson('/api/asrama/allocation-users/'.$user->id, [])->assertUnprocessable();
        $this->patchJson('/api/asrama/allocation-users/'.$admin->id, $profile)->assertNotFound();
        $this->patchJson('/api/asrama/allocation-users/'.$user->id, $profile)->assertOk();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'angkatan' => '2026', 'agama' => 'islam']);
        $this->postJson('/api/asrama/kamar/allocate')->assertOk()->assertJsonCount(1, 'allocated');
        $this->patchJson('/api/asrama/allocation-users/'.$user->id, $profile)->assertUnprocessable();
        $this->getJson('/api/asrama/kamar?q=501')->assertOk()->assertJsonPath('records.data.0.penghuni_count', 1)->assertJsonPath('records.data.0.status', 'Assigned')->assertJsonPath('records.data.0.penghuni.0.name', $user->name);
        $this->get('/kamar?q=501')->assertOk()->assertSee($user->name)->assertSee('Assigned');
        $this->postJson('/api/asrama/kamar', [])->assertStatus(405);
        $this->patchJson('/api/asrama/kamar/1', ['status' => 'Kosong'])->assertNotFound();
    }
}
