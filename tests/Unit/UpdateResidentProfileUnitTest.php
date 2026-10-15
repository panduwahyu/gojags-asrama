<?php

namespace Tests\Unit;

use App\Actions\UpdateResidentProfile;
use App\Enums\Gender;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class UpdateResidentProfileUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalizes_cohort_and_religion(): void
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
        $this->assertSame('islam', $resident->religion);
    }

    public function test_throws_validation_exception_on_whitespace_only_cohort(): void
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

    public function test_aborts_404_when_user_is_admin(): void
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
}
