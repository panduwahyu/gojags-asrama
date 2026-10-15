<?php

namespace Tests\Unit;

use App\Actions\CheckIn;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CheckInActionUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_throws_validation_exception_when_resident_has_no_room_slot(): void
    {
        $resident = User::factory()->resident()->create();
        $action = new CheckIn;

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Anda belum memiliki alokasi kamar.');

        $action->handle($resident);
    }

    public function test_throws_validation_exception_when_already_checked_in(): void
    {
        $resident = User::factory()->resident()->create();
        $room = Room::factory()->create();
        $room->slots()->create([
            'position' => 1,
            'user_id' => $resident->id,
            'checked_in_at' => now(),
        ]);

        $action = new CheckIn;

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Anda sudah check-in.');

        $action->handle($resident);
    }
}
