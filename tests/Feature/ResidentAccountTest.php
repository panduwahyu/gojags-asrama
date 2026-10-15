<?php

namespace Tests\Feature;

use App\Actions\ImportResidents;
use App\Enums\Gender;
use App\Enums\Religion;
use App\Enums\Role;
use App\Models\RoomSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Pendaftaran akun penghuni (satu per satu dan impor Excel/CSV), reset sandi, dan check-out
 * oleh petugas (keputusan pengelola no. 6, 8, 9, dan 19).
 */
class ResidentAccountTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.id',
            'cohort' => '  64   A ',
            'gender' => 'male',
            'religion' => ' Islam ',
        ], $overrides);
    }

    private function csv(array $rows, string $header = 'Nama,Email,Angkatan,Jenis Kelamin,Agama'): UploadedFile
    {
        $lines = array_map(fn (array $row) => implode(',', $row), $rows);

        return UploadedFile::fake()->createWithContent('penghuni.csv', implode("\n", [$header, ...$lines]));
    }

    private function import(User $admin, UploadedFile $file)
    {
        return $this->actingAs($admin)->post('/api/residents/import', ['file' => $file], ['Accept' => 'application/json']);
    }

    public function test_admin_can_register_a_resident_one_by_one_with_a_generated_password(): void
    {
        $response = $this->actingAs($this->admin())
            ->postJson('/api/residents', $this->payload())
            ->assertCreated()
            ->assertJsonPath('message', 'Akun penghuni berhasil dibuat.')
            ->assertJsonPath('data.email', 'budi@example.id');

        $password = $response->json('data.password');
        $this->assertSame(10, strlen($password));

        $resident = User::where('email', 'budi@example.id')->firstOrFail();
        $this->assertSame(Role::Resident, $resident->role);
        $this->assertSame('64 a', $resident->cohort);
        $this->assertSame(Gender::Male, $resident->gender);
        $this->assertSame(Religion::Islam, $resident->religion);

        $this->app['auth']->forgetGuards();
        $this->postJson('/api/login', ['email' => 'budi@example.id', 'password' => $password])->assertOk();
    }

    public function test_registering_a_resident_validates_the_input(): void
    {
        $admin = $this->admin();
        User::factory()->create(['email' => 'budi@example.id']);

        $this->actingAs($admin)
            ->postJson('/api/residents', $this->payload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email' => 'Email sudah terdaftar.']);

        $this->actingAs($admin)
            ->postJson('/api/residents', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'cohort', 'gender', 'religion']);

        $this->actingAs($admin)
            ->postJson('/api/residents', $this->payload(['email' => 'lain@example.id', 'religion' => 'agnostik', 'cohort' => '64!']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['religion', 'cohort']);
    }

    public function test_only_admins_can_register_residents(): void
    {
        $this->postJson('/api/residents', $this->payload())->assertUnauthorized();

        $this->actingAs(User::factory()->resident()->create())
            ->postJson('/api/residents', $this->payload())
            ->assertForbidden();
    }

    public function test_admin_can_import_residents_from_a_csv_file(): void
    {
        $response = $this->import($this->admin(), $this->csv([
            ['Andi', 'andi@example.id', '64', 'L', 'Islam'],
            ['Sari', 'sari@example.id', '64 A', 'Perempuan', 'kristen'],
        ]))
            ->assertCreated()
            ->assertJsonPath('message', '2 akun penghuni berhasil diimpor.')
            ->assertJsonCount(2, 'data');

        $sari = User::where('email', 'sari@example.id')->firstOrFail();
        $this->assertSame(Gender::Female, $sari->gender);
        $this->assertSame(Religion::Kristen, $sari->religion);
        $this->assertSame('64 a', $sari->cohort);
        $this->assertSame(Role::Resident, $sari->role);

        $this->app['auth']->forgetGuards();
        $this->postJson('/api/login', ['email' => 'andi@example.id', 'password' => $response->json('data.0.password')])->assertOk();
    }

    public function test_admin_can_import_residents_from_an_excel_file(): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['Nama', 'Email', 'Angkatan', 'Jenis Kelamin', 'Agama'],
            ['Dewi', 'dewi@example.id', '2026', 'P', 'Hindu'],
            ['Eko', 'eko@example.id', '2026', 'L', 'Buddha'],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        (new Xlsx($spreadsheet))->save($path);
        $file = new UploadedFile($path, 'penghuni.xlsx', null, null, true);

        $this->import($this->admin(), $file)->assertCreated()->assertJsonCount(2, 'data');

        $this->assertSame(Religion::Hindu, User::where('email', 'dewi@example.id')->firstOrFail()->religion);
        $this->assertSame(Religion::Buddha, User::where('email', 'eko@example.id')->firstOrFail()->religion);
    }

    public function test_import_is_all_or_nothing_and_reports_each_bad_row(): void
    {
        $admin = $this->admin();
        User::factory()->create(['email' => 'ada@example.id']);

        $response = $this->import($admin, $this->csv([
            ['Valid', 'valid@example.id', '64', 'L', 'Islam'],
            ['Email Ada', 'ada@example.id', '64', 'L', 'Islam'],
            ['Gender Salah', 'gs@example.id', '64', 'X', 'Islam'],
            ['Agama Salah', 'as@example.id', '64', 'L', 'Alien'],
            ['Kembar', 'valid@example.id', '64', 'L', 'Islam'],
        ]))->assertUnprocessable();

        $errors = $response->json('errors.file');
        $this->assertContains('Baris 3: Email sudah terdaftar.', $errors);
        $this->assertContains('Baris 4: Jenis kelamin harus L/P (atau Laki-laki/Perempuan).', $errors);
        $this->assertContains('Baris 5: Agama harus salah satu dari: Islam, Kristen, Katolik, Hindu, Buddha, Konghucu.', $errors);
        $this->assertContains('Baris 6: Email sama dengan baris 2.', $errors);
        $this->assertSame(2, User::count(), 'Hanya admin dan akun yang sudah ada; tidak ada akun baru.');
    }

    public function test_import_requires_the_expected_columns_and_some_data(): void
    {
        $admin = $this->admin();

        $this->import($admin, $this->csv([['Andi', 'andi@example.id']], header: 'Nama,Email'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file' => 'Kolom wajib tidak ditemukan. Baris pertama harus berisi: Nama, Email, Angkatan, Jenis Kelamin, Agama.']);

        $this->import($admin, $this->csv([]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file' => 'Berkas tidak berisi data penghuni.']);

        $this->actingAs($admin)
            ->post('/api/residents/import', [], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file' => 'Pilih berkas Excel atau CSV terlebih dahulu.']);
    }

    public function test_import_is_limited_to_five_hundred_rows(): void
    {
        $rows = array_map(fn ($n) => ["Penghuni {$n}", "p{$n}@example.id", '64', 'L', 'Islam'], range(1, ImportResidents::MAX_ROWS + 1));

        $this->import($this->admin(), $this->csv($rows))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file' => 'Jumlah baris melebihi batas 500 penghuni per impor.']);
    }

    public function test_only_admins_can_import(): void
    {
        $file = $this->csv([['Andi', 'andi@example.id', '64', 'L', 'Islam']]);

        $this->actingAs(User::factory()->resident()->create())
            ->post('/api/residents/import', ['file' => $file], ['Accept' => 'application/json'])
            ->assertForbidden();
    }

    public function test_admin_can_reset_a_residents_password(): void
    {
        $resident = User::factory()->resident()->create(['email' => 'lupa@example.id', 'password' => 'lama12345']);

        $response = $this->actingAs($this->admin())
            ->postJson("/api/residents/{$resident->id}/reset-password")
            ->assertOk()
            ->assertJsonPath('message', 'Kata sandi penghuni diatur ulang.');

        $new = $response->json('data.password');
        $this->app['auth']->forgetGuards();
        $this->postJson('/api/login', ['email' => 'lupa@example.id', 'password' => 'lama12345'])->assertUnprocessable();
        $this->postJson('/api/login', ['email' => 'lupa@example.id', 'password' => $new])->assertOk();
    }

    public function test_reset_password_is_for_residents_and_admins_only(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->postJson("/api/residents/{$admin->id}/reset-password")->assertNotFound();
        $this->actingAs(User::factory()->resident()->create())
            ->postJson("/api/residents/{$admin->id}/reset-password")
            ->assertForbidden();
    }

    public function test_resident_list_exposes_account_and_room_details(): void
    {
        $resident = User::factory()->resident()->staying()->profiled()->create();

        $this->actingAs($this->admin())
            ->getJson('/api/residents')
            ->assertOk()
            ->assertJsonPath('data.0.email', $resident->email)
            ->assertJsonPath('data.0.room_number', $resident->slot->room->number)
            ->assertJsonPath('data.0.can_cancel_check_out', false)
            ->assertJsonPath('data.0.religion', 'islam');
    }

    public function test_admin_can_check_a_resident_out_on_their_behalf(): void
    {
        $admin = $this->admin();
        $resident = User::factory()->resident()->staying()->create();

        $this->actingAs($admin)
            ->postJson("/api/residents/{$resident->id}/check-out")
            ->assertOk()
            ->assertJsonPath('message', 'Check-out berhasil. Slot kamar telah dilepaskan.');

        $this->assertNull($resident->fresh()->slot);
        $this->assertNotNull($resident->stays()->firstOrFail()->checked_out_at);

        $this->actingAs($admin)->postJson("/api/residents/{$admin->id}/check-out")->assertNotFound();
        $this->actingAs($resident)->postJson("/api/residents/{$resident->id}/check-out")->assertForbidden();
    }

    public function test_admin_can_cancel_a_mistaken_check_out(): void
    {
        $admin = $this->admin();
        $resident = User::factory()->resident()->staying()->create();
        $slotId = $resident->slot->id;
        $checkedInAt = $resident->slot->checked_in_at;

        $this->actingAs($admin)->postJson("/api/residents/{$resident->id}/check-out")->assertOk();
        $this->actingAs($admin)
            ->getJson('/api/residents')
            ->assertJsonPath('data.0.can_cancel_check_out', true);

        $this->actingAs($admin)
            ->postJson("/api/residents/{$resident->id}/cancel-check-out")
            ->assertOk()
            ->assertJsonPath('message', 'Check-out dibatalkan. Penghuni kembali ke kamarnya.');

        $slot = RoomSlot::findOrFail($slotId);
        $this->assertSame($resident->id, $slot->user_id);
        $this->assertTrue($checkedInAt->equalTo($slot->checked_in_at));
        $this->assertNull($resident->stays()->firstOrFail()->checked_out_at);
    }

    public function test_cancelling_a_check_out_fails_when_the_slot_was_taken_or_nothing_to_cancel(): void
    {
        $admin = $this->admin();
        $resident = User::factory()->resident()->staying()->create();
        $slot = $resident->slot;
        $this->actingAs($admin)->postJson("/api/residents/{$resident->id}/check-out")->assertOk();

        // Slot diambil penghuni lain.
        $other = User::factory()->resident()->create();
        $slot->refresh()->update(['user_id' => $other->id]);

        $this->actingAs($admin)
            ->postJson("/api/residents/{$resident->id}/cancel-check-out")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['room' => 'Slot kamar sebelumnya sudah ditempati penghuni lain.']);

        // Tidak pernah check-in/out.
        $this->actingAs($admin)
            ->postJson("/api/residents/{$other->id}/cancel-check-out")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['room' => 'Penghuni masih memiliki kamar.']);

        $fresh = User::factory()->resident()->create();
        $this->actingAs($admin)
            ->postJson("/api/residents/{$fresh->id}/cancel-check-out")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['room' => 'Tidak ada check-out yang dapat dibatalkan.']);
    }
}
