<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedbackModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_flash_feedback_is_rendered_as_modal_payload_and_escaped(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user)->withSession(['success' => 'Izin keluar berhasil diajukan.'])
            ->get('/user/kamar')->assertOk()->assertSee('data-feedback-modal', false)
            ->assertSee('Izin keluar berhasil diajukan.')->assertDontSee('class="notice" role="status"', false);
        $this->withSession(['success' => '</script><script>alert(1)</script>'])
            ->get('/user/kamar')->assertOk()->assertDontSee('</script><script>alert(1)</script>', false)
            ->assertSee('\\u003C', false);
    }

    public function test_login_validation_uses_feedback_modal_and_logout_includes_flash_message(): void
    {
        $this->post('/login', ['email' => 'invalid', 'password' => 'x'])->assertSessionHasErrors('email');
        $this->get('/login')->assertOk()->assertSee('feedback-payload')->assertSee('data-feedback-modal', false)
            ->assertDontSee('class="error" role="alert"', false);
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user)->post('/logout')->assertRedirect('/login')->assertSessionHas('success', 'Berhasil keluar.');
    }
}
