<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AsramaApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_size_options_and_last_page_preserve_search(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        foreach (['laundry' => 'Diajukan', 'catering' => 'Terjadwal'] as $category => $status) {
            DB::table($category)->insert(array_map(fn ($number) => [
                'name' => 'Pesanan '.$number, 'detail' => 'Data', 'status' => $status,
                'date' => '2026-10-09', 'created_at' => now(), 'updated_at' => now(),
            ], range(1, 113)));
            foreach ([10, 50, 100] as $size) {
                $last = (int) ceil(113 / $size);
                $this->getJson('/api/asrama/'.$category.'?q=Pesanan&per_page='.$size)
                    ->assertOk()->assertJsonPath('records.per_page', $size)->assertJsonCount($size, 'records.data')
                    ->assertJsonPath('records.last_page', $last);
                $this->getJson('/api/asrama/'.$category.'?q=Pesanan&per_page='.$size.'&page='.$last)
                    ->assertOk()->assertJsonPath('records.current_page', $last)
                    ->assertJsonCount(113 - ($last - 1) * $size, 'records.data');
            }
        }
        $this->getJson('/api/asrama/kamar')->assertJsonCount(10, 'records.data')->assertJsonPath('records.last_page', 8);
        $this->getJson('/api/asrama/kamar?per_page=50')->assertJsonCount(50, 'records.data')->assertJsonPath('records.last_page', 2);
        $this->getJson('/api/asrama/kamar?per_page=100')->assertJsonCount(78, 'records.data')->assertJsonPath('records.last_page', 1);
        foreach ([0, 8, 1000, 'invalid'] as $size) {
            $this->getJson('/api/asrama/kamar?per_page='.$size)->assertUnprocessable()->assertJsonValidationErrors('per_page');
        }
        $this->get('/kamar?per_page=50&page=2')->assertOk()->assertSee('2/2')->assertSee('Awal')->assertSee('Akhir');
    }

    public function test_session_and_dashboard_are_available_as_json(): void
    {
        $this->getJson('/api/asrama/session')->assertOk()->assertJsonStructure(['csrfToken', 'user'])->assertJsonPath('user', null);
        $this->getJson('/api/asrama/dashboard')->assertUnauthorized();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->getJson('/api/asrama/dashboard')->assertOk()->assertJsonPath('counts.kamar', 78);
        $this->getJson('/api/asrama/kamar')->assertOk()->assertJsonPath('statuses.0', 'Kosong');
        $this->getJson('/api/asrama/unknown')->assertNotFound();
    }

    public function test_api_requires_login_and_validates_category_status(): void
    {
        $this->postJson('/api/asrama/laundry', [])->assertUnauthorized();
        $this->patchJson('/api/asrama/laundry/1', [])->assertUnauthorized();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        foreach (['laundry' => 'Diajukan', 'catering' => 'Terjadwal'] as $category => $status) {
            $this->postJson('/api/asrama/'.$category, $category === 'catering' ? [
                'menu' => 1, 'penyedia' => 'Keterangan', 'waktu_catering' => '2026-10-09T12:00',
            ] : [
                'name' => 'Data '.$category, 'detail' => 'Keterangan', 'date' => '2026-10-09', 'status' => $status,
            ])->assertCreated();
            $this->getJson('/api/asrama/'.$category.'?q='.($category === 'catering' ? 'Menu' : 'Data'))->assertJsonPath('records.data.0.name', $category === 'catering' ? 'Menu 1' : 'Data '.$category);
        }
        foreach (['laundry', 'catering'] as $category) {
            $this->assertDatabaseCount($category, 1);
        }
        $this->getJson('/api/asrama/dashboard')->assertJsonPath('counts', ['kamar' => 78, 'laundry' => 1, 'catering' => 1]);
        $this->getJson('/api/asrama/laundry?q=TidakAda')->assertJsonCount(0, 'records.data');
        $id = DB::table('laundry')->value('id');
        $this->patchJson('/api/asrama/laundry/'.$id, ['status' => 'Selesai', 'biaya' => 25000])->assertOk();
        $this->assertDatabaseHas('laundry', ['id' => $id, 'status' => 'Selesai']);
        $this->assertDatabaseHas('kamar', ['status' => 'Kosong']);
        $this->assertDatabaseHas('catering', ['id' => $id, 'status' => 'Terjadwal']);
        $this->patchJson('/api/asrama/unknown/'.$id, ['status' => 'Selesai'])->assertNotFound();
        $this->patchJson('/api/asrama/laundry/'.$id, ['status' => 'Terisi'])->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->patchJson('/api/asrama/laundry/999', ['status' => 'Terisi'])->assertNotFound();
    }

    public function test_api_login_and_logout_use_session(): void
    {
        $user = User::factory()->create(['password' => 'test-password']);
        $this->postJson('/api/asrama/login', ['email' => $user->email, 'password' => 'incorrect'])->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->postJson('/api/asrama/login', ['email' => $user->email, 'password' => 'test-password'])->assertOk()->assertJsonPath('user.email', $user->email);
        $this->getJson('/api/asrama/session')->assertJsonPath('user.id', $user->id);
        $this->postJson('/api/asrama/logout')->assertOk();
        $this->assertGuest();
    }

    public function test_api_login_rejects_missing_csrf_token(): void
    {
        // Laravel skips CSRF in the testing environment; enable its real behavior here.
        $this->app['env'] = 'local';
        $this->postJson('/api/asrama/login', ['email' => 'test@example.com', 'password' => 'test-password'])->assertStatus(419);
    }
}
