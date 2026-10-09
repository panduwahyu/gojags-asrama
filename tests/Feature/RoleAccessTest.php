<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AsramaAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_services_but_cannot_access_user_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $this->get('/')->assertRedirect('/admin');
        $this->get('/admin')->assertOk()->assertSee('Total kamar')->assertSee('Logout')->assertDontSee('class="support"', false);
        foreach (['/kamar', '/laundry', '/catering', '/pengumuman', '/checkin'] as $path) {
            $this->get($path)->assertOk();
        }
        $this->get('/user')->assertForbidden();
        $this->getJson('/api/asrama/welcome')->assertForbidden();
    }

    public function test_user_only_sees_personal_welcome_and_cannot_access_admin_data(): void
    {
        $user = User::factory()->create(['role' => 'user', 'name' => 'Pandu Wahyu Aji']);
        $this->actingAs($user);
        $this->get('/')->assertRedirect('/user');
        $this->get('/user')->assertOk()->assertSee('Pandu Wahyu Aji')->assertSee('Selamat datang di')->assertSee('Logout')->assertDontSee('Total kamar')->assertDontSee('class="stats"', false);
        $this->getJson('/api/asrama/welcome')->assertOk()->assertJsonPath('user.name', $user->name)->assertJsonMissingPath('counts');
        foreach (['/admin', '/kamar', '/laundry', '/catering', '/pengumuman', '/checkin'] as $path) {
            $this->get($path)->assertForbidden();
        }
        foreach (['dashboard', 'kamar', 'laundry', 'catering'] as $path) {
            $this->getJson('/api/asrama/'.$path)->assertForbidden();
        }
        $this->postJson('/api/asrama/kamar/allocate', ['role' => 'admin'])->assertForbidden();
        $this->patchJson('/api/asrama/allocation-users/1', ['status' => 'Terisi'])->assertForbidden();
        $this->assertDatabaseCount('kamar', 78);
    }

    public function test_requested_accounts_are_seeded_with_hashed_passwords_and_correct_login_destinations(): void
    {
        $this->seed(AsramaAccountSeeder::class);
        $this->seed(AsramaAccountSeeder::class);
        $this->assertDatabaseCount('users', 2);
        foreach ([
            ['admin@example.id', 'admin123', 'Admin Gojags', 'admin'],
            ['user@example.id', 'user123', 'Pandu Wahyu Aji', 'user'],
        ] as [$email, $password, $name, $role]) {
            $account = User::where('email', $email)->firstOrFail();
            $this->assertSame($name, $account->name);
            $this->assertSame($role, $account->role);
            $this->assertTrue(Hash::check($password, $account->password));
            $this->post('/login', ['email' => $email, 'password' => $password, 'role' => 'admin'])->assertRedirect('/'.$role);
            $this->get('/login')->assertRedirect('/'.$role);
            $this->getJson('/api/asrama/session')->assertJsonPath('user.role', $role);
            $this->post('/logout')->assertRedirect('/login');
            $this->assertGuest();
        }
    }
}
