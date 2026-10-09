<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CateringRatingTest extends TestCase
{
    use RefreshDatabase;

    private function form(int $menu = 1): array
    {
        return ['menu' => $menu, 'penyedia' => 'Dapur Gojags', 'waktu_catering' => '2026-10-10T12:30'];
    }

    public function test_admin_creates_menu_and_users_rate_it_once_each_with_shared_results(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $first = User::factory()->create(['role' => 'user']);
        $second = User::factory()->create(['role' => 'user']);
        $this->actingAs($admin)->postJson('/api/asrama/catering', $this->form())->assertCreated();
        $id = DB::table('catering')->value('id');
        $this->assertDatabaseHas('catering', ['id' => $id, 'menu' => 1, 'penyedia' => 'Dapur Gojags', 'waktu_catering' => '2026-10-10 05:30:00']);
        $this->getJson('/api/asrama/catering')->assertJsonPath('records.data.0.waktu_catering', '2026-10-10T05:30:00.000000Z')->assertJsonPath('records.data.0.rating_average', null)->assertJsonPath('records.data.0.rating_count', 0);
        $this->get('/catering')->assertOk()->assertSee('10/10/2026 12:30 WIB')->assertSee('Nomor menu');
        $this->actingAs($first)->postJson('/api/asrama/user/catering/'.$id.'/rating', ['rating' => 5, 'user_id' => $second->id])->assertOk();
        $this->assertDatabaseHas('catering_ratings', ['catering_id' => $id, 'user_id' => $first->id, 'rating' => 5]);
        $this->getJson('/api/asrama/user/catering')->assertJsonPath('records.data.0.my_rating', 5)->assertJsonPath('records.data.0.rating_count', 1)->assertJsonPath('records.data.0.rating_average', 5);
        $this->actingAs($second)->getJson('/api/asrama/user/catering')->assertJsonPath('records.data.0.my_rating', null);
        $this->post('/user/catering/'.$id.'/rating', ['rating' => 3])->assertRedirect('/user/catering');
        $this->get('/user/catering')->assertOk()->assertSee('4,00 / 5')->assertSee('3 / 5')->assertDontSee('Tambah menu catering');
        $this->actingAs($first)->postJson('/api/asrama/user/catering/'.$id.'/rating', ['rating' => 4])->assertOk();
        $this->assertDatabaseCount('catering_ratings', 2);
        $this->getJson('/api/asrama/user/catering')->assertJsonPath('records.data.0.rating_average', 3.5)->assertJsonPath('records.data.0.my_rating', 4)->assertJsonPath('records.data.0.rating_count', 2);
        $this->actingAs($admin)->getJson('/api/asrama/catering')->assertJsonPath('records.data.0.rating_average', 3.5)->assertJsonPath('records.data.0.rating_count', 2)->assertJsonPath('records.data.0.my_rating', null);
        $this->get('/catering')->assertSee('3,50 / 5')->assertDontSee('Simpan rating');
        $this->assertDatabaseCount('laundry', 0);
    }

    public function test_role_validation_csrf_search_sort_and_pagination(): void
    {
        $this->getJson('/api/asrama/user/catering')->assertUnauthorized();
        $this->postJson('/api/asrama/user/catering/1/rating', ['rating' => 5])->assertUnauthorized();
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($admin)->postJson('/api/asrama/user/catering/1/rating', ['rating' => 5])->assertForbidden();
        $this->getJson('/api/asrama/user/catering')->assertForbidden();
        foreach ([['menu' => 0], ['menu' => 1.5], ['menu' => 10001], ['penyedia' => '  '], ['penyedia' => str_repeat('x', 256)], ['waktu_catering' => 'invalid']] as $invalid) {
            $this->postJson('/api/asrama/catering', array_replace($this->form(), $invalid))->assertUnprocessable();
        }
        for ($i = 1; $i <= 12; $i++) $this->post('/catering', $this->form($i))->assertRedirect('/catering');
        $this->actingAs($user)->postJson('/api/asrama/catering', $this->form())->assertForbidden();
        $this->getJson('/api/asrama/catering')->assertForbidden();
        $id = DB::table('catering')->where('menu', 12)->value('id');
        foreach ([0, 6, -1, 2.5, 'invalid'] as $rating) $this->postJson('/api/asrama/user/catering/'.$id.'/rating', ['rating' => $rating])->assertUnprocessable();
        $this->assertDatabaseCount('catering_ratings', 0);
        $this->postJson('/api/asrama/user/catering/99999/rating', ['rating' => 5])->assertNotFound();
        $this->postJson('/api/asrama/user/catering/'.$id.'/rating', ['rating' => 5])->assertOk();
        $this->getJson('/api/asrama/user/catering?sort=rating_average&direction=desc')->assertJsonPath('records.data.0.menu', 12)->assertJsonCount(10, 'records.data')->assertJsonPath('records.last_page', 2);
        $this->getJson('/api/asrama/user/catering?sort=menu&direction=asc&page=2')->assertJsonCount(2, 'records.data')->assertJsonPath('records.data.0.menu', 11);
        $this->getJson('/api/asrama/user/catering?q=Menu%201&per_page=50')->assertJsonCount(4, 'records.data');
        $this->getJson('/api/asrama/user/catering?sort=user_id')->assertUnprocessable();
        $this->getJson('/api/asrama/user/catering?direction=invalid')->assertUnprocessable();
        $this->getJson('/api/asrama/user/catering?per_page=11')->assertUnprocessable();
        $this->app['env'] = 'local';
        $this->postJson('/api/asrama/user/catering/'.$id.'/rating', ['rating' => 5])->assertStatus(419);
    }
}
