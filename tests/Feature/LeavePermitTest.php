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

    private const UTC_FORMAT = 'Y-m-d\TH:i:s.v\Z';

    /** Izin 4 jam yang mulai $days hari dari sekarang (jauh dari izin bawaan factory agar tidak bertabrakan). */
    private function validPayload(array $overrides = [], int $days = 10): array
    {
        $leavesAt = now()->addDays($days)->utc();

        return array_merge([
            'leaves_at' => $leavesAt->format(self::UTC_FORMAT),
            'returns_at' => $leavesAt->copy()->addHours(4)->format(self::UTC_FORMAT),
            'reason' => 'Keperluan medis di klinik',
        ], $overrides);
    }

    private function utc(int $days, int $hours = 0): string
    {
        return now()->addDays($days)->addHours($hours)->utc()->format(self::UTC_FORMAT);
    }

    public function test_resident_can_submit_leave_permit_and_starts_pending(): void
    {
        $resident = User::factory()->resident()->staying()->create();

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
        $resident = User::factory()->resident()->staying()->create();

        $this->actingAs($resident)
            ->postJson('/api/leave-permits', $this->validPayload([
                'leaves_at' => $this->utc(10),
                'returns_at' => $this->utc(10),
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
        $resident = User::factory()->resident()->staying()->create();
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

        // Jendela waktu lain agar tidak bertabrakan dengan hasil suntingan di atas.
        $this->actingAs($resident)
            ->patchJson("/api/leave-permits/{$approved->id}", $this->validPayload([], 20))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'status' => 'Hanya izin berstatus Diajukan yang dapat diedit.',
            ]);

        $this->actingAs($resident)
            ->patchJson("/api/leave-permits/{$otherPermit->id}", $this->validPayload([], 20))
            ->assertNotFound();
    }

    public function test_admin_can_review_pending_permit_and_resident_cannot_review(): void
    {
        $admin = User::factory()->admin()->create();
        $resident = User::factory()->resident()->create();
        $permit = LeavePermit::factory()->for($resident)->create();

        $this->actingAs($resident)
            ->patchJson("/api/leave-permits/{$permit->id}/review", ['status' => PermitStatus::Approved->value])
            ->assertForbidden();

        $this->actingAs($admin)
            ->patchJson("/api/leave-permits/{$permit->id}/review", ['status' => PermitStatus::Pending->value])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);

        $this->actingAs($admin)
            ->patchJson("/api/leave-permits/{$permit->id}/review", ['status' => PermitStatus::Approved->value])
            ->assertOk()
            ->assertJsonPath('message', 'Izin keluar disetujui.');

        $permit->refresh();
        $this->assertSame(PermitStatus::Approved, $permit->status);
        $this->assertSame($admin->id, $permit->reviewed_by);
        $this->assertNotNull($permit->reviewed_at);
    }

    public function test_rejecting_a_permit_requires_a_written_reason(): void
    {
        $admin = User::factory()->admin()->create();
        $permit = LeavePermit::factory()->create();

        $this->actingAs($admin)
            ->patchJson("/api/leave-permits/{$permit->id}/review", ['status' => PermitStatus::Rejected->value])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rejection_reason' => 'Alasan penolakan wajib diisi.']);

        $this->actingAs($admin)
            ->patchJson("/api/leave-permits/{$permit->id}/review", [
                'status' => PermitStatus::Rejected->value,
                'rejection_reason' => 'Bentrok dengan jadwal kelas',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Izin keluar ditolak.');

        $this->assertSame('Bentrok dengan jadwal kelas', $permit->fresh()->rejection_reason);
        $this->actingAs($permit->user)
            ->getJson('/api/leave-permits')
            ->assertJsonPath('data.0.rejection_reason', 'Bentrok dengan jadwal kelas');
    }

    public function test_admin_can_change_a_decision_and_the_reason_follows_the_new_status(): void
    {
        $admin = User::factory()->admin()->create();
        $permit = LeavePermit::factory()->approved()->create();

        $this->actingAs($admin)
            ->patchJson("/api/leave-permits/{$permit->id}/review", [
                'status' => PermitStatus::Rejected->value,
                'rejection_reason' => 'Kegiatan mendadak',
            ])
            ->assertOk();
        $this->assertSame(PermitStatus::Rejected, $permit->fresh()->status);

        $this->actingAs($admin)
            ->patchJson("/api/leave-permits/{$permit->id}/review", ['status' => PermitStatus::Approved->value])
            ->assertOk();

        $permit->refresh();
        $this->assertSame(PermitStatus::Approved, $permit->status);
        $this->assertNull($permit->rejection_reason);
    }

    public function test_cancelled_permit_cannot_be_reviewed(): void
    {
        $admin = User::factory()->admin()->create();
        $permit = LeavePermit::factory()->create(['status' => PermitStatus::Cancelled]);

        $this->actingAs($admin)
            ->patchJson("/api/leave-permits/{$permit->id}/review", ['status' => PermitStatus::Approved->value])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => 'Izin yang sudah dibatalkan tidak dapat ditinjau.']);
    }

    public function test_resident_can_cancel_own_pending_permit_only(): void
    {
        $resident = User::factory()->resident()->create();
        $other = User::factory()->resident()->create();
        $pending = LeavePermit::factory()->for($resident)->create();
        $approved = LeavePermit::factory()->for($resident)->approved()->create();
        $otherPermit = LeavePermit::factory()->for($other)->create();

        $this->patchJson("/api/leave-permits/{$pending->id}/cancel")->assertUnauthorized();

        $this->actingAs($resident)
            ->patchJson("/api/leave-permits/{$pending->id}/cancel")
            ->assertOk()
            ->assertJsonPath('message', 'Pengajuan izin dibatalkan.');
        $this->assertSame(PermitStatus::Cancelled, $pending->fresh()->status);

        $this->actingAs($resident)
            ->patchJson("/api/leave-permits/{$approved->id}/cancel")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => 'Hanya izin berstatus Diajukan yang dapat dibatalkan.']);

        $this->actingAs($resident)->patchJson("/api/leave-permits/{$otherPermit->id}/cancel")->assertNotFound();
    }

    public function test_leaves_at_cannot_be_in_the_past(): void
    {
        $resident = User::factory()->resident()->staying()->create();

        $this->actingAs($resident)
            ->postJson('/api/leave-permits', $this->validPayload([
                'leaves_at' => $this->utc(-1),
                'returns_at' => $this->utc(0, 4),
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['leaves_at' => 'Waktu keluar tidak boleh di masa lalu.']);
    }

    public function test_overlapping_active_permits_are_rejected(): void
    {
        $resident = User::factory()->resident()->staying()->create();
        LeavePermit::factory()->for($resident)->create([
            'leaves_at' => now()->addDays(5),
            'returns_at' => now()->addDays(5)->addHours(6),
        ]);
        LeavePermit::factory()->for($resident)->approved()->create([
            'leaves_at' => now()->addDays(7),
            'returns_at' => now()->addDays(7)->addHours(6),
        ]);

        foreach ([[5, 3], [7, 5]] as [$days, $hours]) {
            $this->actingAs($resident)
                ->postJson('/api/leave-permits', $this->validPayload([
                    'leaves_at' => $this->utc($days, $hours),
                    'returns_at' => $this->utc($days, $hours + 4),
                ]))
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['leaves_at' => 'Waktu izin bertabrakan dengan izin lain yang masih aktif.']);
        }

        // Bersebelahan (mulai tepat saat izin lain kembali) bukan tabrakan.
        $this->actingAs($resident)
            ->postJson('/api/leave-permits', $this->validPayload([
                'leaves_at' => $this->utc(5, 6),
                'returns_at' => $this->utc(5, 10),
            ]))
            ->assertCreated();
    }

    public function test_rejected_and_cancelled_permits_do_not_block_new_ones(): void
    {
        $resident = User::factory()->resident()->staying()->create();
        foreach ([PermitStatus::Rejected, PermitStatus::Cancelled] as $status) {
            LeavePermit::factory()->for($resident)->create([
                'status' => $status,
                'leaves_at' => now()->addDays(5),
                'returns_at' => now()->addDays(5)->addHours(6),
            ]);
        }

        $this->actingAs($resident)
            ->postJson('/api/leave-permits', $this->validPayload([
                'leaves_at' => $this->utc(5, 1),
                'returns_at' => $this->utc(5, 3),
            ]))
            ->assertCreated();
    }

    public function test_editing_a_permit_does_not_conflict_with_itself(): void
    {
        $resident = User::factory()->resident()->staying()->create();
        $permit = LeavePermit::factory()->for($resident)->create([
            'leaves_at' => now()->addDays(5),
            'returns_at' => now()->addDays(5)->addHours(6),
        ]);

        $this->actingAs($resident)
            ->patchJson("/api/leave-permits/{$permit->id}", $this->validPayload([
                'leaves_at' => $this->utc(5, 1),
                'returns_at' => $this->utc(5, 3),
            ]))
            ->assertOk();
    }

    public function test_resident_who_is_not_staying_cannot_submit_or_edit_permits(): void
    {
        $notStaying = User::factory()->resident()->create();
        $permit = LeavePermit::factory()->for($notStaying)->create();
        $message = 'Fitur ini hanya untuk penghuni yang sedang menginap (sudah check-in).';

        $this->actingAs($notStaying)
            ->postJson('/api/leave-permits', $this->validPayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['room' => $message]);

        $this->actingAs($notStaying)
            ->patchJson("/api/leave-permits/{$permit->id}", $this->validPayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['room' => $message]);
    }

    public function test_submitting_leave_permit_ignores_status_user_id_and_reviewed_by(): void
    {
        $resident = User::factory()->resident()->staying()->create();
        $other = User::factory()->resident()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($resident)
            ->postJson('/api/leave-permits', $this->validPayload([
                'status' => PermitStatus::Approved->value,
                'user_id' => $other->id,
                'reviewed_by' => $admin->id,
            ]))
            ->assertCreated();

        $permit = LeavePermit::latest('id')->firstOrFail();
        $this->assertSame($resident->id, $permit->user_id);
        $this->assertSame(PermitStatus::Pending, $permit->status);
        $this->assertNull($permit->reviewed_by);
    }

    public function test_timestamps_are_returned_as_iso_8601_utc(): void
    {
        $resident = User::factory()->resident()->create();
        LeavePermit::factory()->for($resident)->create();

        $row = $this->actingAs($resident)->getJson('/api/leave-permits')->assertOk()->json('data.0');

        foreach (['leaves_at', 'returns_at'] as $field) {
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?Z$/', $row[$field], $field);
        }
    }
}
