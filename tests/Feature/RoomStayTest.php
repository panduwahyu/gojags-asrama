<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\RoomAllocator;
use App\Services\RoomStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RoomStayTest extends TestCase
{
    use RefreshDatabase;

    private function resident(): User
    {
        $user = User::factory()->create(['role' => 'user', 'angkatan' => '64', 'gender' => 'Laki-laki', 'agama' => 'islam']);
        if (!DB::table('training_batches')->where('source_id', '64')->exists()) DB::table('training_batches')->insert(['source_id' => '64', 'name' => 'Angkatan 64', 'synced_at' => now()]);
        DB::table('training_participants')->insert(['source_id' => 'participant-'.$user->id, 'batch_id' => DB::table('training_batches')->where('source_id', '64')->value('id'), 'user_id' => $user->id]);
        return $user;
    }

    private function qr(string $action): array
    {
        $token = bin2hex(random_bytes(32));
        $admin = User::where('role', 'admin')->first() ?? User::factory()->create(['role' => 'admin']);
        $batchId = DB::table('training_batches')->where('source_id', '64')->value('id');
        if (!$batchId) $batchId = DB::table('training_batches')->insertGetId(['source_id' => '64', 'name' => 'Angkatan 64', 'synced_at' => now()]);
        DB::table('room_qr_sessions')->insert(['batch_id' => $batchId, 'created_by' => $admin->id, 'action' => $action, 'token_hash' => hash('sha256', $token), 'expires_at' => now()->addMinutes(15)]);
        return ['qr_token' => 'gojags-room:v1:'.$token];
    }

    public function test_two_residents_transition_through_assigned_full_occupied_and_empty_with_private_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $first = $this->resident();
        $second = $this->resident();
        app(RoomAllocator::class)->allocate();
        $room = DB::table('kamar_slots')->where('user_id', $first->id)->value('kamar_id');
        $this->assertDatabaseHas('kamar', ['id' => $room, 'status' => 'Assigned']);
        $this->actingAs($admin)->getJson('/api/asrama/kamar?q=not-found')->assertJsonPath('monitoring.Assigned', 1)->assertJsonPath('monitoring.Kosong', 77)->assertJsonCount(0, 'records.data');
        $this->travelTo(now()->setDate(2026, 10, 9)->setTime(4, 0, 0));
        $this->actingAs($first)->postJson('/api/asrama/user/kamar/check-in', $this->qr('check-in') + ['user_id' => $second->id, 'checked_in_at' => '2000-01-01'])->assertOk();
        $this->assertDatabaseHas('kamar', ['id' => $room, 'status' => 'Assigned']);
        $this->assertDatabaseHas('kamar_slots', ['user_id' => $second->id, 'checked_in_at' => null]);
        $this->postJson('/api/asrama/user/kamar/check-in', $this->qr('check-in'))->assertUnprocessable();
        $this->getJson('/api/asrama/user/kamar')->assertJsonPath('room.checked_in_at', '2026-10-09T04:00:00.000000Z')->assertJsonCount(1, 'history');
        $this->get('/user/kamar')->assertOk()->assertSee('09/10/2026 11:00:00')->assertSee('WIB');
        $this->actingAs($second)->postJson('/api/asrama/user/kamar/check-in', $this->qr('check-in'))->assertOk();
        $this->assertDatabaseHas('kamar', ['id' => $room, 'status' => 'Penuh']);
        $this->assertSame(1, RoomStatus::monitoring()['Penuh']);
        $this->travel(1)->hours();
        $this->actingAs($first)->postJson('/api/asrama/user/kamar/check-out', $this->qr('check-out'))->assertOk();
        $this->assertDatabaseHas('kamar', ['id' => $room, 'status' => 'Terisi']);
        $this->getJson('/api/asrama/user/kamar')->assertJsonPath('room', null)->assertJsonCount(1, 'history')->assertJsonPath('history.0.checked_out_at', '2026-10-09T05:00:00.000000Z');
        $this->postJson('/api/asrama/user/kamar/check-out', $this->qr('check-out'))->assertUnprocessable();
        $this->actingAs($second)->getJson('/api/asrama/user/kamar')->assertJsonCount(1, 'history')->assertJsonPath('history.0.checked_out_at', null);
        $this->postJson('/api/asrama/user/kamar/check-out', $this->qr('check-out'))->assertOk();
        $this->assertDatabaseHas('kamar', ['id' => $room, 'status' => 'Kosong']);
        $this->assertSame(78, RoomStatus::monitoring()['Kosong']);
        $this->assertCount(0, app(RoomAllocator::class)->allocate()['allocated']);
        $this->assertDatabaseCount('kamar_stays', 2);
        $this->assertSame(0, DB::table('kamar_slots')->whereNotNull('user_id')->count());
        $this->travelBack();
    }

    public function test_single_resident_is_assigned_then_occupied_and_cannot_check_out_before_check_in(): void
    {
        $user = $this->resident();
        app(RoomAllocator::class)->allocate();
        $this->actingAs($user)->postJson('/api/asrama/user/kamar/check-out', $this->qr('check-out'))->assertUnprocessable();
        $this->post('/user/kamar/check-in', $this->qr('check-in'))->assertRedirect('/user/kamar');
        $this->assertSame(1, RoomStatus::monitoring()['Terisi']);
        $this->post('/user/kamar/check-out', $this->qr('check-out'))->assertRedirect('/user/kamar');
        $this->assertSame(78, RoomStatus::monitoring()['Kosong']);
        $this->get('/user/kamar')->assertSee('Belum ada alokasi kamar')->assertSee('Riwayat check-in dan check-out');
    }

    public function test_user_service_routes_are_isolated_and_check_in_requires_assignment_and_csrf(): void
    {
        $this->getJson('/api/asrama/user/kamar')->assertUnauthorized();
        $this->postJson('/api/asrama/user/kamar/check-in', $this->qr('check-in'))->assertUnauthorized();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/user/kamar')->assertForbidden();
        $this->postJson('/api/asrama/user/kamar/check-in', $this->qr('check-in'))->assertForbidden();
        $user = $this->resident();
        $this->actingAs($user)->getJson('/api/asrama/user/kamar')->assertOk()->assertJsonPath('room', null)->assertJsonCount(0, 'history');
        $this->postJson('/api/asrama/user/kamar/check-in', $this->qr('check-in'))->assertUnprocessable();
        foreach (['kamar', 'laundry', 'catering'] as $service) {
            $this->get('/user/'.$service)->assertOk()->assertSee('/user/kamar')->assertSee('/user/laundry')->assertSee('/user/catering')->assertDontSee('Total kamar');
            $this->get('/'.$service)->assertForbidden();
        }
        $this->get('/user/laundry')->assertSee('Ajukan laundry')->assertSee('Jumlah stel');
        $this->get('/user/catering')->assertSee('Menu dan rating catering')->assertSee('Rata-rata rating');
        app(RoomAllocator::class)->allocate();
        $this->app['env'] = 'local';
        $this->postJson('/api/asrama/user/kamar/check-in', $this->qr('check-in'))->assertStatus(419);
        $this->assertDatabaseCount('kamar_stays', 0);
    }
}
