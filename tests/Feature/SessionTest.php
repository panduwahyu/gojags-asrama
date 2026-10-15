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
}
