<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class RoomStatus
{
    public static function assignedSql(): string
    {
        return '(SELECT COUNT(*) FROM kamar_slots WHERE kamar_slots.kamar_id = kamar.id AND user_id IS NOT NULL)';
    }

    public static function checkedInSql(): string
    {
        return '(SELECT COUNT(*) FROM kamar_slots WHERE kamar_slots.kamar_id = kamar.id AND user_id IS NOT NULL AND checked_in_at IS NOT NULL)';
    }

    public static function statusSql(): string
    {
        $assigned = self::assignedSql();
        $checked = self::checkedInSql();
        return "CASE WHEN $assigned = 0 THEN 'Kosong' WHEN $assigned > $checked THEN 'Assigned' WHEN $checked >= 2 THEN 'Penuh' ELSE 'Terisi' END";
    }

    public static function monitoring(): array
    {
        $expression = self::statusSql();
        $states = DB::table('kamar')->selectRaw($expression.' AS computed_status');
        $counts = DB::query()->fromSub($states, 'room_states')->selectRaw('computed_status, COUNT(*) AS total')->groupBy('computed_status')->pluck('total', 'computed_status')->all();
        return array_replace(['Kosong' => 0, 'Assigned' => 0, 'Terisi' => 0, 'Penuh' => 0], $counts);
    }

    public static function sync(int $id): void
    {
        $status = DB::table('kamar')->where('id', $id)->selectRaw(self::statusSql().' AS computed_status')->value('computed_status');
        DB::table('kamar')->where('id', $id)->update(['status' => $status, 'updated_at' => now()]);
    }
}
