<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\RoomAllocator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RoomQrTest extends TestCase
{
    use RefreshDatabase;

    private function batch(string $source): int
    {
        return DB::table('training_batches')->insertGetId(['source_id' => $source, 'name' => 'Angkatan '.$source, 'synced_at' => now()]);
    }

    private function member(int $batch): User
    {
        $user = User::factory()->create(['role' => 'user', 'angkatan' => 'batch-'.$batch, 'gender' => 'Laki-laki', 'agama' => 'Islam']);
        DB::table('training_participants')->insert(['source_id' => 'peserta-'.$user->id, 'batch_id' => $batch, 'user_id' => $user->id]);
        return $user;
    }

    private function generate(User $admin, int $batch, string $action = 'check-in'): string
    {
        $response = $this->actingAs($admin)->postJson('/api/asrama/kamar/qr', ['batch_id' => $batch, 'action' => $action])->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $image = $response->json('qr_image');
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $image);
        $payload = $response->json('qr_payload');
        $this->assertDatabaseHas('room_qr_sessions', ['token_hash' => hash('sha256', substr($payload, strlen('gojags-room:v1:')))]);
        return $payload;
    }

    public function test_scan_requires_session_csrf_valid_batch_and_qr_and_cannot_bypass_old_endpoints(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $batch = $this->batch('1');
        $user = $this->member($batch);
        $other = $this->member($this->batch('2'));
        app(RoomAllocator::class)->allocate();
        $this->postJson('/api/asrama/user/kamar/scan')->assertUnauthorized();
        $qr = $this->generate($admin, $batch);
        $this->postJson('/api/asrama/user/kamar/scan', ['qr_token' => $qr])->assertForbidden();
        $this->actingAs($user)->postJson('/api/asrama/kamar/qr', ['batch_id' => $batch, 'action' => 'check-in'])->assertForbidden();
        $this->postJson('/api/asrama/user/kamar/check-in')->assertUnprocessable();
        $this->postJson('/api/asrama/user/kamar/scan', ['qr_token' => 'invalid'])->assertUnprocessable();
        $this->postJson('/api/asrama/user/kamar/scan', ['qr_token' => 'gojags-room:v1:'.str_repeat('0', 64)])->assertUnprocessable();
        $this->actingAs($other)->postJson('/api/asrama/user/kamar/scan', ['qr_token' => $qr])->assertUnprocessable();
        $this->assertDatabaseCount('kamar_stays', 0);
        $this->actingAs($user);
        $this->app['env'] = 'local';
        $this->postJson('/api/asrama/user/kamar/scan', ['qr_token' => $qr])->assertStatus(419);
    }

    public function test_rotation_expiry_and_inactive_membership_reject_without_side_effects(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $batch = $this->batch('1');
        $user = $this->member($batch);
        app(RoomAllocator::class)->allocate($batch);
        $old = $this->generate($admin, $batch);
        $current = $this->generate($admin, $batch);
        $this->actingAs($user)->postJson('/api/asrama/user/kamar/scan', ['qr_token' => $old])->assertUnprocessable();
        DB::table('training_participants')->where('user_id', $user->id)->update(['active' => false]);
        $this->postJson('/api/asrama/user/kamar/scan', ['qr_token' => $current])->assertUnprocessable();
        DB::table('training_participants')->where('user_id', $user->id)->update(['active' => true]);
        $this->travel(15)->minutes();
        $this->postJson('/api/asrama/user/kamar/scan', ['qr_token' => $current])->assertUnprocessable();
        $this->assertDatabaseCount('kamar_stays', 0);
        $this->assertDatabaseHas('kamar_slots', ['user_id' => $user->id, 'checked_in_at' => null]);
    }

    public function test_qr_is_shared_by_batch_but_each_user_checks_in_once_and_checks_out_with_separate_qr(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $batch = $this->batch('1');
        $first = $this->member($batch);
        $second = $this->member($batch);
        app(RoomAllocator::class)->allocate($batch);
        $checkin = $this->generate($admin, $batch);
        $checkout = $this->generate($admin, $batch, 'check-out');
        $this->actingAs($first)->postJson('/api/asrama/user/kamar/scan', ['qr_token' => $checkout])->assertUnprocessable();
        $this->postJson('/api/asrama/user/kamar/check-out', ['qr_token' => $checkin])->assertUnprocessable();
        $this->postJson('/api/asrama/user/kamar/scan', ['qr_token' => $checkin, 'user_id' => $second->id, 'checked_in_at' => '2000-01-01'])->assertOk();
        $this->postJson('/api/asrama/user/kamar/scan', ['qr_token' => $checkin])->assertUnprocessable();
        $this->actingAs($second)->postJson('/api/asrama/user/kamar/scan', ['qr_token' => $checkin])->assertOk();
        $this->assertDatabaseCount('kamar_stays', 2);
        $this->actingAs($first)->postJson('/api/asrama/user/kamar/scan', ['qr_token' => $checkout])->assertOk();
        $this->postJson('/api/asrama/user/kamar/scan', ['qr_token' => $checkout])->assertUnprocessable();
        $this->assertDatabaseHas('kamar_slots', ['user_id' => $second->id]);
        $this->getJson('/api/asrama/user/kamar')->assertJsonPath('room', null)->assertJsonCount(1, 'history');
    }
}
