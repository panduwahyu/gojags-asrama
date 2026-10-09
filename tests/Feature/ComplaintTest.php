<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ComplaintTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_submits_private_complaint_and_admin_resolves_it_with_server_time(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $other = User::factory()->create(['role' => 'user']);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->travelTo(now()->setDate(2026, 10, 10)->setTime(1, 0));
        $this->actingAs($user)->postJson('/api/asrama/user/keluhan', ['judul' => 'Kran bocor', 'isi' => 'Kran kamar 501 bocor.', 'status' => 'Resolved', 'user_id' => $other->id])->assertCreated();
        $id = DB::table('keluhan_asrama')->value('id');
        $this->assertDatabaseHas('keluhan_asrama', ['id' => $id, 'user_id' => $user->id, 'status' => 'Diajukan', 'resolved_at' => null]);
        $this->get('/user/kamar')->assertOk()->assertSee('Keluhan asrama')->assertSee('Kran bocor')->assertSee('10/10/2026 08:00 WIB')->assertDontSee('Tandai Resolved');
        $this->actingAs($other)->getJson('/api/asrama/user/keluhan')->assertJsonCount(0, 'records.data');
        $this->get('/user/kamar')->assertDontSee('Kran bocor');
        $this->actingAs($admin)->get('/kamar')->assertOk()->assertSee('Kran bocor')->assertSee('Tandai Resolved');
        $this->getJson('/api/asrama/keluhan')->assertJsonPath('records.data.0.user_name', $user->name)->assertJsonPath('records.data.0.created_at', '2026-10-10T01:00:00.000000Z');
        $this->travel(1)->hours();
        $this->patchJson('/api/asrama/keluhan/'.$id, ['status' => 'Resolved', 'isi' => 'forged', 'resolved_at' => '2000-01-01'])->assertOk();
        $this->assertDatabaseHas('keluhan_asrama', ['id' => $id, 'resolved_by' => $admin->id, 'status' => 'Resolved', 'isi' => 'Kran kamar 501 bocor.', 'resolved_at' => '2026-10-10 02:00:00']);
        $this->travel(1)->hours();
        $this->patch('/keluhan/'.$id, ['status' => 'Resolved'])->assertRedirect('/kamar');
        $this->assertDatabaseHas('keluhan_asrama', ['id' => $id, 'resolved_at' => '2026-10-10 02:00:00']);
        $this->actingAs($user)->getJson('/api/asrama/user/keluhan')->assertJsonPath('records.data.0.status', 'Resolved')->assertJsonPath('records.data.0.resolved_at', '2026-10-10T02:00:00.000000Z');
        $this->get('/user/kamar')->assertSee('10/10/2026 09:00 WIB')->assertSee('Resolved');
        $this->post('/user/keluhan', ['judul' => 'Lampu', 'isi' => 'Lampu mati'])->assertRedirect('/user/kamar');
        $this->travelBack();
    }

    public function test_roles_validation_csrf_and_scoped_search_pagination_sorting(): void
    {
        $this->getJson('/api/asrama/user/keluhan')->assertUnauthorized();
        $this->postJson('/api/asrama/user/keluhan', [])->assertUnauthorized();
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $other = User::factory()->create(['role' => 'user']);
        $this->actingAs($admin)->postJson('/api/asrama/user/keluhan', [])->assertForbidden();
        $this->getJson('/api/asrama/user/keluhan')->assertForbidden();
        $this->patchJson('/api/asrama/keluhan/999', ['status' => 'Resolved'])->assertNotFound();
        $this->patchJson('/api/asrama/keluhan/999', ['status' => 'Diajukan'])->assertUnprocessable();
        $this->actingAs($user)->getJson('/api/asrama/keluhan')->assertForbidden();
        $this->patchJson('/api/asrama/keluhan/1', ['status' => 'Resolved'])->assertForbidden();
        foreach ([['judul' => ' ', 'isi' => 'Isi'], ['judul' => 'Judul', 'isi' => ' '], ['judul' => str_repeat('x', 151), 'isi' => 'Isi'], ['judul' => 'Judul', 'isi' => str_repeat('x', 2001)]] as $invalid) $this->postJson('/api/asrama/user/keluhan', $invalid)->assertUnprocessable();
        foreach ([$user, $other] as $owner) for ($i = 1; $i <= 12; $i++) DB::table('keluhan_asrama')->insert(['user_id' => $owner->id, 'judul' => sprintf('Keluhan %02d', $i), 'isi' => 'Perlu ditangani', 'status' => 'Diajukan', 'created_at' => now(), 'updated_at' => now()]);
        $this->getJson('/api/asrama/user/keluhan?sort=judul&direction=asc')->assertJsonPath('records.total', 12)->assertJsonCount(10, 'records.data')->assertJsonPath('records.data.0.judul', 'Keluhan 01');
        $this->getJson('/api/asrama/user/keluhan?sort=judul&direction=asc&page=2')->assertJsonCount(2, 'records.data')->assertJsonPath('records.data.0.judul', 'Keluhan 11');
        $this->getJson('/api/asrama/user/keluhan?q=Keluhan%2001&per_page=50')->assertJsonCount(1, 'records.data');
        foreach (['per_page=3', 'sort=user_id', 'direction=invalid', 'page=0'] as $invalid) $this->getJson('/api/asrama/user/keluhan?'.$invalid)->assertUnprocessable();
        $this->actingAs($admin)->getJson('/api/asrama/keluhan?per_page=100&sort=user_name&direction=asc')->assertJsonPath('records.total', 24);
        $id = DB::table('keluhan_asrama')->value('id');
        $this->app['env'] = 'local';
        $this->patchJson('/api/asrama/keluhan/'.$id, ['status' => 'Resolved'])->assertStatus(419);
        $this->actingAs($user)->postJson('/api/asrama/user/keluhan', ['judul' => 'Judul', 'isi' => 'Isi'])->assertStatus(419);
    }
}
