<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Room;
use App\Models\RoomSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeders_create_rooms_slots_and_demo_accounts_idempotently(): void
    {
        $this->seed();

        $this->assertSame(78, Room::count());
        $this->assertSame(156, RoomSlot::count());
        $this->assertSame(1, User::where('role', Role::Admin)->count());
        $this->assertSame(11, User::where('role', Role::Resident)->count());

        $user1 = User::where('email', 'user1@example.id')->firstOrFail();
        $this->assertNull($user1->religion);

        $user1->update(['religion' => 'islam']);
        $this->seed();

        $this->assertSame(78, Room::count());
        $this->assertSame(156, RoomSlot::count());
        $this->assertSame(1, User::where('role', Role::Admin)->count());
        $this->assertSame(11, User::where('role', Role::Resident)->count());
        $this->assertSame('islam', $user1->fresh()->religion);
    }

    public function test_demo_accounts_are_not_seeded_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->artisan('db:seed', ['--force' => true]);

        $this->assertSame(78, Room::count());
        $this->assertSame(0, User::count());
    }
}
