<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AsramaTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_is_a_standalone_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Gojags Asrama')->assertDontSee('<aside', false)->assertDontSee('<footer', false);
        foreach (['/admin', '/user', '/kamar', '/laundry', '/catering', '/pengumuman', '/checkin'] as $path) {
            $this->get($path)->assertRedirect('/login');
        }
    }

    public function test_guests_cannot_modify_records(): void
    {
        $this->post('/laundry', [])->assertRedirect('/login');
        $this->post('/kamar/allocate')->assertRedirect('/login');
        $this->assertDatabaseCount('kamar', 78);
    }

    public function test_rooms_are_fixed_and_cannot_be_created_or_manually_marked(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->post('/kamar', ['name' => 'Extra'])->assertStatus(405);
        $this->patch('/kamar/1', ['status' => 'Penuh'])->assertNotFound();
        $this->get('/kamar')->assertOk()->assertSee('Alokasikan kamar otomatis')->assertDontSee('Tambah kamar')->assertDontSee('name="detail"', false);
        $this->get('/kamar?q=501')->assertSee('501')->assertDontSee('<strong>526</strong>', false);
        $this->assertDatabaseCount('kamar', 78);
    }

    public function test_login_and_logout(): void
    {
        $user = User::factory()->create(['password' => 'test-password']);
        $this->post('/login', ['email' => $user->email, 'password' => 'incorrect'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/login', ['email' => $user->email, 'password' => 'test-password'])->assertRedirect('/user');
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }
}
