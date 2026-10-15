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

    public function test_admin_can_mark_a_complaint_in_progress_with_a_response(): void
    {
        $admin = User::factory()->admin()->create();
        $complaint = Complaint::factory()->create();

        $this->actingAs($admin)
            ->patchJson("/api/complaints/{$complaint->id}/progress", ['response' => 'Teknisi akan datang besok pagi.'])
            ->assertOk()
            ->assertJsonPath('message', 'Keluhan ditandai Sedang ditangani.');

        $complaint->refresh();
        $this->assertSame(ComplaintStatus::InProgress, $complaint->status);
        $this->assertSame('Teknisi akan datang besok pagi.', $complaint->response);

        $this->actingAs($complaint->user)
            ->getJson('/api/complaints')
            ->assertJsonPath('data.0.status', 'in_progress')
            ->assertJsonPath('data.0.status_label', 'Sedang ditangani')
            ->assertJsonPath('data.0.response', 'Teknisi akan datang besok pagi.');
    }

    public function test_progress_without_a_response_keeps_the_existing_one(): void
    {
        $admin = User::factory()->admin()->create();
        $complaint = Complaint::factory()->create(['response' => 'Balasan awal']);

        $this->actingAs($admin)->patchJson("/api/complaints/{$complaint->id}/progress")->assertOk();

        $this->assertSame('Balasan awal', $complaint->fresh()->response);
    }

    public function test_resolving_can_carry_a_response_and_works_from_in_progress(): void
    {
        $admin = User::factory()->admin()->create();
        $complaint = Complaint::factory()->create(['status' => ComplaintStatus::InProgress]);

        $this->actingAs($admin)
            ->patchJson("/api/complaints/{$complaint->id}/resolve", ['response' => 'AC sudah diganti.'])
            ->assertOk()
            ->assertJsonPath('message', 'Keluhan ditandai Resolved.');

        $complaint->refresh();
        $this->assertSame(ComplaintStatus::Resolved, $complaint->status);
        $this->assertSame('AC sudah diganti.', $complaint->response);
        $this->assertSame($admin->id, $complaint->resolved_by);
    }

    public function test_resolved_complaint_must_be_reopened_before_it_is_handled_again(): void
    {
        $admin = User::factory()->admin()->create();
        $complaint = Complaint::factory()->create(['status' => ComplaintStatus::Resolved]);

        $this->actingAs($admin)
            ->patchJson("/api/complaints/{$complaint->id}/progress")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => 'Keluhan yang sudah Resolved harus dibuka kembali sebelum ditangani lagi.']);
    }

    public function test_owner_and_admin_can_reopen_a_resolved_complaint(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->resident()->create();

        foreach ([$owner, $admin] as $actor) {
            $complaint = Complaint::factory()->for($owner)->create([
                'status' => ComplaintStatus::Resolved,
                'resolved_by' => $admin->id,
                'resolved_at' => now(),
            ]);

            $this->actingAs($actor)
                ->patchJson("/api/complaints/{$complaint->id}/reopen")
                ->assertOk()
                ->assertJsonPath('message', 'Keluhan dibuka kembali.');

            $complaint->refresh();
            $this->assertSame(ComplaintStatus::Pending, $complaint->status);
            $this->assertNull($complaint->resolved_by);
            $this->assertNull($complaint->resolved_at);
        }
    }

    public function test_only_resolved_complaints_can_be_reopened_and_not_by_other_residents(): void
    {
        $owner = User::factory()->resident()->create();
        $stranger = User::factory()->resident()->create();
        $pending = Complaint::factory()->for($owner)->create();
        $resolved = Complaint::factory()->for($owner)->create(['status' => ComplaintStatus::Resolved]);

        $this->patchJson("/api/complaints/{$resolved->id}/reopen")->assertUnauthorized();

        $this->actingAs($owner)
            ->patchJson("/api/complaints/{$pending->id}/reopen")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => 'Hanya keluhan berstatus Resolved yang dapat dibuka kembali.']);

        $this->actingAs($stranger)->patchJson("/api/complaints/{$resolved->id}/reopen")->assertNotFound();
        $this->assertSame(ComplaintStatus::Resolved, $resolved->fresh()->status);
    }

    public function test_residents_cannot_progress_or_resolve_complaints(): void
    {
        $resident = User::factory()->resident()->create();
        $complaint = Complaint::factory()->for($resident)->create();

        $this->actingAs($resident)->patchJson("/api/complaints/{$complaint->id}/progress")->assertForbidden();
        $this->actingAs($resident)->patchJson("/api/complaints/{$complaint->id}/resolve")->assertForbidden();
    }

    public function test_response_length_is_limited(): void
    {
        $admin = User::factory()->admin()->create();
        $complaint = Complaint::factory()->create();

        $this->actingAs($admin)
            ->patchJson("/api/complaints/{$complaint->id}/progress", ['response' => str_repeat('a', 2001)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['response']);
    }
}
