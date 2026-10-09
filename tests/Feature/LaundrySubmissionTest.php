<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LaundrySubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_submits_private_laundry_and_admin_sets_cost_and_completion(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $other = User::factory()->create(['role' => 'user']);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user)->postJson('/api/asrama/user/laundry', ['detail' => 'Kemeja dan celana', 'jumlah_stel' => 3, 'user_id' => $other->id, 'biaya' => 1, 'status' => 'Selesai'])->assertCreated();
        $id = DB::table('laundry')->value('id');
        $this->assertDatabaseHas('laundry', ['id' => $id, 'user_id' => $user->id, 'name' => $user->name, 'detail' => 'Kemeja dan celana', 'jumlah_stel' => 3, 'status' => 'Diajukan', 'biaya' => null]);
        $this->getJson('/api/asrama/user/laundry')->assertJsonCount(1, 'records.data')->assertJsonPath('records.data.0.jumlah_stel', 3);
        $this->get('/user/laundry')->assertOk()->assertSee('Kemeja dan celana')->assertSee('Belum ditentukan');
        $this->actingAs($other)->getJson('/api/asrama/user/laundry?q=Kemeja')->assertJsonCount(0, 'records.data');
        $this->get('/user/laundry')->assertDontSee('Kemeja dan celana');
        $this->actingAs($admin)->get('/laundry')->assertOk()->assertSee($user->name)->assertSee('Jumlah stel')->assertSee('Biaya laundry');
        $this->getJson('/api/asrama/laundry?sort=jumlah_stel&direction=desc')->assertJsonPath('records.data.0.id', $id);
        $this->patchJson('/api/asrama/laundry/'.$id, ['status' => 'Selesai'])->assertUnprocessable()->assertJsonValidationErrors('biaya');
        foreach ([-1, 'invalid', 10000000000, '12.345'] as $cost) {
            $this->patchJson('/api/asrama/laundry/'.$id, ['status' => 'Selesai', 'biaya' => $cost])->assertUnprocessable()->assertJsonValidationErrors('biaya');
        }
        $this->patchJson('/api/asrama/laundry/'.$id, ['status' => 'Selesai', 'biaya' => '25000.50', 'user_id' => $other->id, 'jumlah_stel' => 999])->assertOk();
        $this->assertDatabaseHas('laundry', ['id' => $id, 'status' => 'Selesai', 'biaya' => 25000.50, 'user_id' => $user->id, 'jumlah_stel' => 3]);
        $this->actingAs($user)->get('/user/laundry')->assertSee('Selesai')->assertSee('Rp 25.000,50');
        $this->getJson('/api/asrama/user/laundry?sort=biaya&direction=asc')->assertJsonPath('records.data.0.status', 'Selesai');
        $this->assertDatabaseCount('kamar', 78);
    }

    public function test_laundry_roles_validation_csrf_and_pagination(): void
    {
        $this->postJson('/api/asrama/user/laundry', [])->assertUnauthorized();
        $this->getJson('/api/asrama/user/laundry')->assertUnauthorized();
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($admin)->postJson('/api/asrama/user/laundry', [])->assertForbidden();
        $this->getJson('/api/asrama/user/laundry')->assertForbidden();
        $this->actingAs($user)->patchJson('/api/asrama/laundry/1', ['status' => 'Selesai', 'biaya' => 0])->assertForbidden();
        $this->getJson('/api/asrama/laundry')->assertForbidden();
        foreach ([0, -1, 1.5, 1001, 'invalid'] as $count) $this->postJson('/api/asrama/user/laundry', ['detail' => 'Kemeja', 'jumlah_stel' => $count])->assertUnprocessable();
        $this->postJson('/api/asrama/user/laundry', ['detail' => '  ', 'jumlah_stel' => 1])->assertUnprocessable();
        $this->postJson('/api/asrama/user/laundry', ['detail' => str_repeat('x', 256), 'jumlah_stel' => 1])->assertUnprocessable();
        $this->assertDatabaseCount('laundry', 0);
        for ($i = 1; $i <= 12; $i++) $this->post('/user/laundry', ['detail' => 'Kemeja '.$i, 'jumlah_stel' => $i])->assertRedirect('/user/laundry');
        $this->getJson('/api/asrama/user/laundry?sort=jumlah_stel&direction=asc')->assertJsonCount(10, 'records.data')->assertJsonPath('records.last_page', 2)->assertJsonPath('records.data.0.jumlah_stel', 1);
        $this->getJson('/api/asrama/user/laundry?sort=jumlah_stel&direction=asc&page=2')->assertJsonCount(2, 'records.data')->assertJsonPath('records.data.0.jumlah_stel', 11);
        $this->getJson('/api/asrama/user/laundry?per_page=50&q=Kemeja%201')->assertJsonCount(4, 'records.data');
        $this->getJson('/api/asrama/user/laundry?sort=user_id')->assertUnprocessable();
        $this->getJson('/api/asrama/user/laundry?per_page=3')->assertUnprocessable();
        $id = DB::table('laundry')->value('id');
        $this->actingAs($admin)->patch('/laundry/'.$id, ['status' => 'Selesai', 'biaya' => 0])->assertRedirect();
        $this->assertDatabaseHas('laundry', ['id' => $id, 'biaya' => 0, 'status' => 'Selesai']);
        $this->actingAs($user);
        $this->app['env'] = 'local';
        $this->postJson('/api/asrama/user/laundry', ['detail' => 'Kemeja', 'jumlah_stel' => 1])->assertStatus(419);
    }
}
