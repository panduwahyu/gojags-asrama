<?php

namespace Tests\Unit;

use App\Actions\CheckOut;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CheckOutActionUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_throws_validation_exception_when_not_checked_in(): void
    {
        $resident = User::factory()->resident()->create();
        $room = Room::factory()->create();
        $room->slots()->create([
            'position' => 1,
            'user_id' => $resident->id,
            'checked_in_at' => null,
        ]);

        $action = new CheckOut;

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Anda harus check-in sebelum check-out.');

        $action->handle($resident);
    }
}
