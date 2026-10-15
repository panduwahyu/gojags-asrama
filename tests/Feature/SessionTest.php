<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_session_returns_null_user_and_csrf_token_with_no_store_header(): void
    {
        $response = $this->getJson('/api/session')->assertOk();

        $this->assertNull($response->json('user'));
        $this->assertNotEmpty($response->json('csrfToken'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_valid_login_returns_user_with_role(): void
    {
        $user = User::factory()->resident()->create([
            'email' => 'resident@example.id',
            'password' => 'secret123',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'resident@example.id',
            'password' => 'secret123',
        ])->assertOk();

        $response->assertJsonPath('user.id', $user->id);
        $response->assertJsonPath('user.role', Role::Resident->value);
        $this->assertNotEmpty($response->json('csrfToken'));
    }

    public function test_invalid_login_returns_422_with_exact_message(): void
    {
        User::factory()->create([
            'email' => 'resident@example.id',
            'password' => 'secret123',
        ]);

        $this->postJson('/api/login', [
            'email' => 'resident@example.id',
            'password' => 'wrong-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email' => 'Email atau kata sandi tidak sesuai.',
            ]);
    }

    public function test_sixth_login_attempt_within_a_minute_returns_429(): void
    {
        $userB = User::factory()->resident()->create([
            'email' => 'b@example.id',
            'password' => 'secret123',
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/api/login', [
                'email' => 'a@example.id',
                'password' => 'wrong',
            ])->assertUnprocessable();
        }

        $this->postJson('/api/login', [
            'email' => 'A@Example.ID',
            'password' => 'wrong',
        ])->assertTooManyRequests();

        $this->postJson('/api/login', [
            'email' => 'b@example.id',
            'password' => 'secret123',
        ])->assertOk();
    }

    public function test_failed_logins_from_another_ip_do_not_lock_out_the_account_owner(): void
    {
        User::factory()->resident()->create([
            'email' => 'victim@example.id',
            'password' => 'secret123',
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->withHeader('X-Forwarded-For', '10.0.0.1')
                ->postJson('/api/login', ['email' => 'victim@example.id', 'password' => 'wrong'])
                ->assertUnprocessable();
        }

        $this->withHeader('X-Forwarded-For', '10.0.0.1')
            ->postJson('/api/login', ['email' => 'victim@example.id', 'password' => 'wrong'])
            ->assertTooManyRequests();

        $this->withHeader('X-Forwarded-For', '10.0.0.2')
            ->postJson('/api/login', ['email' => 'victim@example.id', 'password' => 'secret123'])
            ->assertOk();
    }

    public function test_many_emails_from_one_ip_hit_the_per_ip_limit(): void
    {
        config(['asrama.login_attempts_per_ip' => 3]);

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->withHeader('X-Forwarded-For', '10.0.0.3')
                ->postJson('/api/login', ['email' => "spray{$attempt}@example.id", 'password' => 'wrong'])
                ->assertUnprocessable();
        }

        $this->withHeader('X-Forwarded-For', '10.0.0.3')
            ->postJson('/api/login', ['email' => 'spray4@example.id', 'password' => 'wrong'])
            ->assertTooManyRequests();
    }

    public function test_logout_clears_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJsonPath('user', null);
    }

    public function test_logout_without_login_returns_401(): void
    {
        $this->postJson('/api/logout')->assertUnauthorized();
    }

    public function test_login_regenerates_the_session_id(): void
    {
        User::factory()->resident()->create(['email' => 'regen@example.id', 'password' => 'secret123']);
        $this->startSession();
        $before = session()->getId();

        $this->postJson('/api/login', ['email' => 'regen@example.id', 'password' => 'secret123'])->assertOk();

        $this->assertNotSame($before, session()->getId());
    }

    public function test_logout_invalidates_the_session_and_rotates_the_csrf_token(): void
    {
        $user = User::factory()->resident()->create();
        $this->actingAs($user)->startSession();
        $oldToken = session()->token();

        $this->postJson('/api/logout')->assertOk();

        $this->assertGuest();
        $this->assertNotSame($oldToken, session()->token());
    }

    public function test_api_responses_are_never_cached(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (['/api/dashboard', '/api/rooms', '/api/leave-permits'] as $url) {
            $cacheControl = $this->actingAs($admin)->getJson($url)->headers->get('Cache-Control');
            $this->assertStringContainsString('no-store', (string) $cacheControl, $url);
        }
    }

    public function test_unknown_api_route_returns_generic_indonesian_message(): void
    {
        $this->getJson('/api/tidak-ada')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Data atau halaman tidak ditemukan.']);

        $this->postJson('/api/session')
            ->assertStatus(405)
            ->assertExactJson(['message' => 'Metode permintaan tidak didukung.']);
    }
}
