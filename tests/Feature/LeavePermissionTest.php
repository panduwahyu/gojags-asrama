<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LeavePermissionTest extends TestCase
{
    use RefreshDatabase;

    private function form(): array
    {
        return ['waktu_keluar' => '2026-10-10T08:00', 'waktu_kembali' => '2026-10-10T18:00', 'alasan' => 'Mengunjungi keluarga'];
    }

    public function test_user_can_submit_and_edit_own_pending_leave_and_admin_can_approve_or_reject(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $other = User::factory()->create(['role' => 'user']);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user)->postJson('/api/asrama/user/izin-keluar', $this->form() + ['user_id' => $other->id, 'status' => 'Disetujui'])->assertCreated();
        $id = DB::table('izin_keluar')->value('id');
        $this->assertDatabaseHas('izin_keluar', ['id' => $id, 'user_id' => $user->id, 'status' => 'Diajukan', 'waktu_keluar' => '2026-10-10 01:00:00', 'waktu_kembali' => '2026-10-10 11:00:00']);
        $this->getJson('/api/asrama/user/izin-keluar')->assertJsonPath('records.data.0.waktu_keluar', '2026-10-10T01:00:00.000000Z')->assertJsonCount(1, 'records.data');
        $this->get('/user/kamar')->assertOk()->assertSee('Izin keluar')->assertSee('10/10/2026 08:00')->assertSee('Mengunjungi keluarga');
        $changed = array_replace($this->form(), ['alasan' => 'Keperluan keluarga', 'waktu_kembali' => '2026-10-10T19:00']);
        $this->patchJson('/api/asrama/user/izin-keluar/'.$id, $changed)->assertOk();
        $this->assertDatabaseHas('izin_keluar', ['id' => $id, 'alasan' => 'Keperluan keluarga', 'waktu_kembali' => '2026-10-10 12:00:00']);
        $this->actingAs($other)->getJson('/api/asrama/user/izin-keluar')->assertJsonCount(0, 'records.data');
        $this->patchJson('/api/asrama/user/izin-keluar/'.$id, $changed)->assertNotFound();
        $this->get('/user/kamar')->assertDontSee('Keperluan keluarga');
        $this->actingAs($admin)->get('/kamar')->assertOk()->assertSee('Keperluan keluarga')->assertSee('Setujui');
        $this->getJson('/api/asrama/izin-keluar')->assertJsonPath('records.data.0.user_name', $user->name);
        $this->patchJson('/api/asrama/izin-keluar/'.$id, ['status' => 'Disetujui', 'alasan' => 'forged'])->assertOk();
        $this->assertDatabaseHas('izin_keluar', ['id' => $id, 'status' => 'Disetujui', 'reviewed_by' => $admin->id, 'alasan' => 'Keperluan keluarga']);
        $this->patchJson('/api/asrama/izin-keluar/'.$id, ['status' => 'Ditolak'])->assertUnprocessable();
        $this->actingAs($user)->patchJson('/api/asrama/user/izin-keluar/'.$id, $this->form())->assertUnprocessable();
        $this->post('/user/izin-keluar', $this->form())->assertRedirect('/user/kamar');
        $second = DB::table('izin_keluar')->max('id');
        $this->actingAs($admin)->patch('/izin-keluar/'.$second, ['status' => 'Ditolak'])->assertRedirect('/kamar');
        $this->actingAs($user)->patch('/user/izin-keluar/'.$second, $this->form())->assertSessionHasErrors('status');
        $this->getJson('/api/asrama/user/izin-keluar?sort=id&direction=asc')->assertJsonPath('records.data.0.status', 'Disetujui')->assertJsonPath('records.data.1.status', 'Ditolak');
        $this->assertDatabaseCount('kamar_stays', 0);
        $this->assertDatabaseCount('kamar', 78);
    }

    public function test_roles_csrf_validation_and_readonly_final_status_are_enforced(): void
    {
        $this->getJson('/api/asrama/user/izin-keluar')->assertUnauthorized();
        $this->postJson('/api/asrama/user/izin-keluar', $this->form())->assertUnauthorized();
        $user = User::factory()->create(['role' => 'user']);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user)->getJson('/api/asrama/izin-keluar')->assertForbidden();
        $this->patchJson('/api/asrama/izin-keluar/1', ['status' => 'Disetujui'])->assertForbidden();
        foreach ([['waktu_kembali' => '2026-10-10T08:00'], ['waktu_kembali' => '2026-10-09T09:00'], ['waktu_keluar' => 'invalid'], ['alasan' => '   '], ['alasan' => str_repeat('x', 2001)]] as $invalid) {
            $this->postJson('/api/asrama/user/izin-keluar', array_replace($this->form(), $invalid))->assertUnprocessable();
        }
        $this->assertDatabaseCount('izin_keluar', 0);
        $this->actingAs($admin)->getJson('/api/asrama/user/izin-keluar')->assertForbidden();
        $this->postJson('/api/asrama/user/izin-keluar', $this->form())->assertForbidden();
        $this->patchJson('/api/asrama/user/izin-keluar/1', $this->form())->assertForbidden();
        $this->patchJson('/api/asrama/izin-keluar/1', ['status' => 'Diajukan'])->assertUnprocessable();
        $this->patchJson('/api/asrama/izin-keluar/999', ['status' => 'Disetujui'])->assertNotFound();
        $this->actingAs($user);
        $this->app->instance('env', 'production');
        $this->postJson('/api/asrama/user/izin-keluar', $this->form())->assertStatus(419);
    }

    public function test_search_sort_and_pagination_are_scoped_to_current_user(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $other = User::factory()->create(['role' => 'user']);
        foreach ([$user, $other] as $owner) {
            for ($i = 1; $i <= 12; $i++) {
                DB::table('izin_keluar')->insert(['user_id' => $owner->id, 'waktu_keluar' => '2026-10-10 01:00:00', 'waktu_kembali' => '2026-10-10 11:00:00', 'alasan' => sprintf('Alasan %02d', $i), 'status' => 'Diajukan', 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        $this->actingAs($user)->getJson('/api/asrama/user/izin-keluar?sort=alasan&direction=asc')->assertJsonCount(10, 'records.data')->assertJsonPath('records.total', 12)->assertJsonPath('records.last_page', 2)->assertJsonPath('records.data.0.alasan', 'Alasan 01');
        $this->getJson('/api/asrama/user/izin-keluar?sort=alasan&direction=asc&page=2')->assertJsonCount(2, 'records.data')->assertJsonPath('records.data.0.alasan', 'Alasan 11');
        $this->getJson('/api/asrama/user/izin-keluar?q=Alasan%2001&per_page=50')->assertJsonCount(1, 'records.data');
        foreach (['per_page=11', 'sort=user_id', 'direction=invalid', 'page=0'] as $invalid) $this->getJson('/api/asrama/user/izin-keluar?'.$invalid)->assertUnprocessable();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->getJson('/api/asrama/izin-keluar?per_page=100&sort=user_name&direction=asc')->assertJsonPath('records.total', 24)->assertJsonCount(24, 'records.data');
    }
}
