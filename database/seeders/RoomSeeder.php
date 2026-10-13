<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $floors = config('asrama.floors');
        $roomsPerFloor = (int) config('asrama.rooms_per_floor');
        $capacity = (int) config('asrama.room_capacity');

        foreach ($floors as $floor) {
            for ($index = 1; $index <= $roomsPerFloor; $index++) {
                $room = Room::firstOrCreate(
                    ['number' => (string) ($floor * 100 + $index)],
                    ['floor' => $floor]
                );

                for ($position = 1; $position <= $capacity; $position++) {
                    $room->slots()->firstOrCreate(['position' => $position]);
                }
            }
        }
    }
}
