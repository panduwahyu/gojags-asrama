<?php

namespace Tests\Feature;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintTest extends TestCase
{
    use RefreshDatabase;

    public function test_resident_can_submit_complaint_and_length_limits_are_enforced(): void
    {
        $resident = User::factory()->resident()->create();

        $this->actingAs($resident)
            ->postJson('/api/complaints', [
                'title' => 'Keran kamar mandi bocor',
                'body' => 'Air terus menetes sejak pagi.',
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Keluhan asrama berhasil dikirim.');

        $this->assertDatabaseHas('complaints', [
            'user_id' => $resident->id,
            'title' => 'Keran kamar mandi bocor',
            'status' => ComplaintStatus::Pending->value,
        ]);

        $this->actingAs($resident)
            ->postJson('/api/complaints', [
                'title' => str_repeat('a', 151),
                'body' => 'Isi singkat',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);

        $this->actingAs($resident)
            ->postJson('/api/complaints', [
                'title' => 'Judul singkat',
                'body' => str_repeat('b', 2001),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['body']);
    }

    public function test_visibility_by_role_and_idempotent_admin_resolve(): void
    {
        $firstAdmin = User::factory()->admin()->create();
        $secondAdmin = User::factory()->admin()->create();
        $firstResident = User::factory()->resident()->create();
        $secondResident = User::factory()->resident()->create();

        $complaint = Complaint::factory()->for($firstResident)->create();
        Complaint::factory()->for($secondResident)->create();

        $this->actingAs($firstResident)
            ->getJson('/api/complaints')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $complaint->id);

        $this->actingAs($firstAdmin)
            ->getJson('/api/complaints')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->actingAs($firstResident)
            ->patchJson("/api/complaints/{$complaint->id}/resolve")
            ->assertForbidden();

        $this->actingAs($firstAdmin)
            ->patchJson("/api/complaints/{$complaint->id}/resolve")
            ->assertOk()
            ->assertJsonPath('message', 'Keluhan ditandai Resolved.');

        $complaint->refresh();
        $firstResolvedAt = $complaint->resolved_at;
        $this->assertSame(ComplaintStatus::Resolved, $complaint->status);
        $this->assertSame($firstAdmin->id, $complaint->resolved_by);
        $this->assertNotNull($firstResolvedAt);

        $this->travel(5)->minutes();

        $this->actingAs($secondAdmin)
            ->patchJson("/api/complaints/{$complaint->id}/resolve")
            ->assertOk()
            ->assertJsonPath('message', 'Keluhan ditandai Resolved.');

        $complaint->refresh();
        $this->assertSame($firstAdmin->id, $complaint->resolved_by);
        $this->assertTrue($firstResolvedAt->equalTo($complaint->resolved_at));
    }

    public function test_guest_cannot_access_complaints(): void
    {
        $this->getJson('/api/complaints')->assertUnauthorized();
    }
}
