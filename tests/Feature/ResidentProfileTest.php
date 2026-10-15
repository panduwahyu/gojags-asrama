<?php

namespace Tests\Feature;

use App\Actions\UpdateResidentProfile;
use App\Enums\Gender;
use App\Enums\Religion;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class ResidentProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_and_update_resident_profile_with_normalized_values(): void
    {
        $admin = User::factory()->admin()->create();
        $resident = User::factory()->resident()->create([
            'cohort' => null,
            'gender' => null,
            'religion' => null,
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/residents/{$resident->id}", [
                'cohort' => '  2026   A  ',
                'gender' => Gender::Male->value,
                'religion' => '  ISLAM  ',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Profil alokasi berhasil disimpan.');

        $this->assertDatabaseHas('users', [
            'id' => $resident->id,
            'cohort' => '2026 a',
            'gender' => Gender::Male->value,
            'religion' => 'islam',
        ]);

        $this->actingAs($admin)
            ->getJson('/api/residents')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $resident->id)
            ->assertJsonPath('data.0.cohort', '2026 a')
            ->assertJsonPath('data.0.religion', 'islam')
            ->assertJsonPath('data.0.room_id', null);
    }

    public function test_whitespace_only_cohort_returns_422_with_domain_message(): void
    {
        $admin = User::factory()->admin()->create();
        $resident = User::factory()->resident()->create();

        $this->actingAs($admin)
            ->patchJson("/api/residents/{$resident->id}", [
                'cohort' => '   ',
                'gender' => Gender::Male->value,
                'religion' => 'islam',
            ])
            ->assertUnprocessable()
            ->assertSee('Angkatan dan agama wajib diisi.');
    }

    public function test_profile_of_a_resident_with_a_room_can_still_be_corrected(): void
    {
        $admin = User::factory()->admin()->create();
        $resident = User::factory()->resident()->profiled()->create();
        $room = Room::factory()->create();
        $room->slots()->create(['position' => 1, 'user_id' => $resident->id]);

        $this->actingAs($admin)
            ->patchJson("/api/residents/{$resident->id}", [
                'cohort' => '65',
                'gender' => Gender::Male->value,
                'religion' => 'kristen',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Profil alokasi berhasil disimpan.')
            ->assertJsonPath('warnings', []);

        $this->assertSame('65', $resident->fresh()->cohort);
        $this->assertSame(Religion::Kristen, $resident->fresh()->religion);
        $this->assertDatabaseHas('room_slots', ['room_id' => $room->id, 'user_id' => $resident->id]);
    }

    public function test_correcting_a_profile_warns_when_it_no_longer_matches_the_roommate(): void
    {
        $admin = User::factory()->admin()->create();
        $resident = User::factory()->resident()->profiled('64', Gender::Male, 'islam')->create();
        $roommate = User::factory()->resident()->profiled('64', Gender::Male, 'islam')->create();
        $room = Room::factory()->create();
        $room->slots()->create(['position' => 1, 'user_id' => $resident->id]);
        $room->slots()->create(['position' => 2, 'user_id' => $roommate->id]);

        $this->actingAs($admin)
            ->patchJson("/api/residents/{$resident->id}", [
                'cohort' => '65',
                'gender' => Gender::Female->value,
                'religion' => 'hindu',
            ])
            ->assertOk()
            ->assertJsonPath('warnings', [
                'Jenis kelamin berbeda dengan teman sekamar.',
                'Beda agama dengan teman sekamar.',
                'Beda angkatan dengan teman sekamar.',
            ]);

        // Kamar tidak dibatalkan dan data tetap tersimpan.
        $this->assertDatabaseHas('room_slots', ['room_id' => $room->id, 'user_id' => $resident->id]);
        $this->assertSame(Gender::Female, $resident->fresh()->gender);
    }

    public function test_religion_must_be_one_of_the_six_official_religions(): void
    {
        $admin = User::factory()->admin()->create();
        $resident = User::factory()->resident()->create();

        $this->actingAs($admin)
            ->patchJson("/api/residents/{$resident->id}", ['cohort' => '64', 'gender' => 'male', 'religion' => 'agnostik'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['religion']);

        foreach (['Islam', 'KRISTEN', ' katolik ', 'hindu', 'Buddha', 'konghucu'] as $religion) {
            $this->actingAs($admin)
                ->patchJson("/api/residents/{$resident->id}", ['cohort' => '64', 'gender' => 'male', 'religion' => $religion])
                ->assertOk();
        }
    }

    public function test_cohort_may_only_contain_letters_digits_and_spaces(): void
    {
        $admin = User::factory()->admin()->create();
        $resident = User::factory()->resident()->create();

        foreach (['64!', 'A/B', '64-65'] as $invalid) {
            $this->actingAs($admin)
                ->patchJson("/api/residents/{$resident->id}", ['cohort' => $invalid, 'gender' => 'male', 'religion' => 'islam'])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['cohort' => 'Angkatan hanya boleh berisi huruf, angka, dan spasi.']);
        }
    }

    public function test_updating_admin_user_returns_404(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patchJson("/api/residents/{$otherAdmin->id}", [
                'cohort' => '64',
                'gender' => Gender::Male->value,
                'religion' => 'islam',
            ])
            ->assertNotFound();
    }

    public function test_unknown_gender_returns_422(): void
    {
        $admin = User::factory()->admin()->create();
        $resident = User::factory()->resident()->create();

        $this->actingAs($admin)
            ->patchJson("/api/residents/{$resident->id}", [
                'cohort' => '64',
                'gender' => 'unknown',
                'religion' => 'islam',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['gender']);
    }

    public function test_resident_and_guest_cannot_access_resident_endpoints(): void
    {
        $resident = User::factory()->resident()->create();

        $this->getJson('/api/residents')->assertUnauthorized();

        $this->actingAs($resident)
            ->getJson('/api/residents')
            ->assertForbidden();
    }

    public function test_updating_profile_requires_all_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $resident = User::factory()->resident()->create();

        $this->actingAs($admin)
            ->patchJson("/api/residents/{$resident->id}", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cohort', 'gender', 'religion']);
    }

    public function test_update_resident_profile_action_normalizes_cohort_and_religion(): void
    {
        $resident = User::factory()->resident()->create();
        $action = new UpdateResidentProfile;

        $action->handle($resident, [
            'cohort' => "  64 \t\n A  ",
            'gender' => Gender::Male,
            'religion' => '  ISLAM  ',
        ]);

        $resident->refresh();
        $this->assertSame('64 a', $resident->cohort);
        $this->assertSame(Religion::Islam, $resident->religion);
    }

    public function test_update_resident_profile_action_throws_validation_exception_on_whitespace_only_cohort(): void
    {
        $resident = User::factory()->resident()->create();
        $action = new UpdateResidentProfile;

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Angkatan dan agama wajib diisi.');

        $action->handle($resident, [
            'cohort' => "   \t  ",
            'gender' => Gender::Female,
            'religion' => 'Katolik',
        ]);
    }

    public function test_update_resident_profile_action_aborts_404_when_user_is_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $action = new UpdateResidentProfile;

        $this->expectException(NotFoundHttpException::class);

        $action->handle($admin, [
            'cohort' => '2026',
            'gender' => Gender::Male,
            'religion' => 'Islam',
        ]);
    }

    public function test_cohort_length_limit_is_enforced(): void
    {
        $admin = User::factory()->admin()->create();
        $resident = User::factory()->resident()->create();
        $valid = ['cohort' => '64', 'gender' => Gender::Male->value, 'religion' => 'islam'];

        $this->actingAs($admin)
            ->patchJson("/api/residents/{$resident->id}", [...$valid, 'cohort' => str_repeat('a', 101)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cohort']);

        $this->actingAs($admin)
            ->patchJson("/api/residents/{$resident->id}", [...$valid, 'cohort' => str_repeat('a', 100)])
            ->assertOk();
    }
}
