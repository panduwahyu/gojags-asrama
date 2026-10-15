<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResidentProfileValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_profile_requires_all_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $resident = User::factory()->resident()->create();

        $this->actingAs($admin)
            ->patchJson("/api/residents/{$resident->id}", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cohort', 'gender', 'religion']);
    }

    public function test_updating_profile_with_invalid_gender_fails_validation(): void
    {
        $admin = User::factory()->admin()->create();
        $resident = User::factory()->resident()->create();

        $this->actingAs($admin)
            ->patchJson("/api/residents/{$resident->id}", [
                'cohort' => '2026',
                'gender' => 'invalid-gender',
                'religion' => 'Islam',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['gender']);
    }
}
