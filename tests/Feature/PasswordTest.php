<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Ubah kata sandi sendiri (keputusan pengelola no. 19). Lupa sandi ditangani petugas (lihat ResidentAccountTest). */
class PasswordTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'current_password' => 'lama12345',
            'password' => 'baru12345',
            'password_confirmation' => 'baru12345',
        ], $overrides);
    }

    public function test_user_can_change_their_own_password(): void
    {
        foreach (['resident', 'admin'] as $role) {
            $user = User::factory()->{$role}()->create(['email' => "{$role}@example.id", 'password' => 'lama12345']);

            $this->actingAs($user)
                ->putJson('/api/password', $this->payload())
                ->assertOk()
                ->assertJsonPath('message', 'Kata sandi berhasil diperbarui.');

            $this->assertTrue(Hash::check('baru12345', $user->fresh()->password));
        }
    }

    public function test_current_password_must_match(): void
    {
        $user = User::factory()->resident()->create(['password' => 'lama12345']);

        $this->actingAs($user)
            ->putJson('/api/password', $this->payload(['current_password' => 'salah']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['current_password' => 'Kata sandi saat ini tidak sesuai.']);

        $this->assertTrue(Hash::check('lama12345', $user->fresh()->password));
    }

    public function test_new_password_rules_are_enforced(): void
    {
        $user = User::factory()->resident()->create(['password' => 'lama12345']);

        $this->actingAs($user)
            ->putJson('/api/password', $this->payload(['password' => 'pendek', 'password_confirmation' => 'pendek']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password' => 'Kata sandi baru minimal 8 karakter.']);

        $this->actingAs($user)
            ->putJson('/api/password', $this->payload(['password_confirmation' => 'tidaksama']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password' => 'Konfirmasi kata sandi baru tidak sesuai.']);

        $this->actingAs($user)
            ->putJson('/api/password', $this->payload(['password' => 'lama12345', 'password_confirmation' => 'lama12345']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password' => 'Kata sandi baru harus berbeda dari kata sandi saat ini.']);

        $this->actingAs($user)
            ->putJson('/api/password', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['current_password', 'password']);
    }

    public function test_guests_cannot_change_a_password(): void
    {
        $this->putJson('/api/password', $this->payload())->assertUnauthorized();
    }
}
