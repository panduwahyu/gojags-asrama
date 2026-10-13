<?php

namespace Tests\Feature;

use App\Enums\PermitStatus;
use App\Models\LeavePermit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeavePermitTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'leaves_at' => '2026-10-14T08:00:00.000Z',
            'returns_at' => '2026-10-14T12:00:00.000Z',
            'reason' => 'Keperluan medis di klinik',
        ], $overrides);
    }

    public function test_resident_can_submit_leave_permit_and_starts_pending(): void
    {
        $resident = User::factory()->resident()->create();

        $this->actingAs($resident)
            ->postJson('/api/leave-permits', $this->validPayload())
            ->assertCreated()
            ->assertJsonPath('message', 'Izin keluar berhasil diajukan.');

        $this->assertDatabaseHas('leave_permits', [
            'user_id' => $resident->id,
            'reason' => 'Keperluan medis di klinik',
            'status' => PermitStatus::Pending->value,
        ]);
    }

    public function test_returns_at_must_be_after_leaves_at_and_timestamps_require_z_suffix(): void
    {
        $resident = User::factory()->resident()->create();

        $this->actingAs($resident)
            ->postJson('/api/leave-permits', $this->validPayload([
                'leaves_at' => '2026-10-14T12:00:00.000Z',
                'returns_at' => '2026-10-14T12:00:00.000Z',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'returns_at' => 'Waktu kembali harus setelah waktu keluar.',
            ]);

        $this->actingAs($resident)
            ->postJson('/api/leave-permits', $this->validPayload([
                'leaves_at' => '2026-10-13T10:00',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['leaves_at']);
    }

    public function test_visibility_search_and_user_name_sorting_by_role(): void
    {
        $admin = User::factory()->admin()->create();
        $zaki = User::factory()->resident()->create(['name' => 'Zaki Pratama']);
        $andi = User::factory()->resident()->create(['name' => 'Andi Wijaya']);

        LeavePermit::factory()->for($zaki)->create(['reason' => 'Urusan Zaki']);
        LeavePermit::factory()->for($andi)->create(['reason' => 'Urusan Andi']);

        $this->actingAs($zaki)
            ->getJson('/api/leave-permits')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user_name', 'Zaki Pratama');

        $adminList = $this->actingAs($admin)
            ->getJson('/api/leave-permits?sort=user_name&direction=asc')
            ->assertOk();

        $this->assertSame(
            ['Andi Wijaya', 'Zaki Pratama'],
            array_column($adminList->json('data'), 'user_name')
        );

        $searched = $this->actingAs($admin)
            ->getJson('/api/leave-permits?q=Andi')
            ->assertOk();

        $this->assertCount(1, $searched->json('data'));
        $this->assertSame('Andi Wijaya', $searched->json('data.0.user_name'));
    }

    public function test_resident_can_edit_own_pending_permit_only(): void
    {
        $resident = User::factory()->resident()->create();
        $other = User::factory()->resident()->create();
        $pending = LeavePermit::factory()->for($resident)->create();
        $approved = LeavePermit::factory()->for($resident)->approved()->create();
        $otherPermit = LeavePermit::factory()->for($other)->create();

        $this->actingAs($resident)
            ->patchJson("/api/leave-permits/{$pending->id}", $this->validPayload([
                'reason' => 'Alasan diperbarui',
            ]))
            ->assertOk()
            ->assertJsonPath('message', 'Pengajuan izin berhasil diperbarui.');

        $this->assertSame('Alasan diperbarui', $pending->fresh()->reason);

        $this->actingAs($resident)
            ->patchJson("/api/leave-permits/{$approved->id}", $this->validPayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'status' => 'Hanya izin berstatus Diajukan yang dapat diedit.',
            ]);

        $this->actingAs($resident)
            ->patchJson("/api/leave-permits/{$otherPermit->id}", $this->validPayload())
            ->assertNotFound();
    }

    public function test_admin_can_review_pending_permit_once_and_resident_cannot_review(): void
    {
        $admin = User::factory()->admin()->create();
        $resident = User::factory()->resident()->create();
        $first = LeavePermit::factory()->for($resident)->create();
        $second = LeavePermit::factory()->for($resident)->create();

        $this->actingAs($resident)
            ->patchJson("/api/leave-permits/{$first->id}/review", [
                'status' => PermitStatus::Approved->value,
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->patchJson("/api/leave-permits/{$first->id}/review", [
                'status' => PermitStatus::Pending->value,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);

        $this->actingAs($admin)
            ->patchJson("/api/leave-permits/{$first->id}/review", [
                'status' => PermitStatus::Approved->value,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Izin keluar disetujui.');

        $first->refresh();
        $this->assertSame(PermitStatus::Approved, $first->status);
        $this->assertSame($admin->id, $first->reviewed_by);
        $this->assertNotNull($first->reviewed_at);

        $this->actingAs($admin)
            ->patchJson("/api/leave-permits/{$first->id}/review", [
                'status' => PermitStatus::Rejected->value,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'status' => 'Pengajuan ini sudah diproses.',
            ]);

        $this->actingAs($admin)
            ->patchJson("/api/leave-permits/{$second->id}/review", [
                'status' => PermitStatus::Rejected->value,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Izin keluar ditolak.');
    }
}
