<?php

namespace Tests\Unit;

use App\Models\CateringMenu;
use Carbon\Carbon;
use Tests\TestCase;

class CateringMenuUnitTest extends TestCase
{
    public function test_rating_closes_at_end_of_day_wib(): void
    {
        $menu = new CateringMenu([
            'served_at' => Carbon::parse('2026-10-15 05:00:00', 'UTC'), // 12:00 WIB
        ]);

        $closesAt = $menu->ratingClosesAt();

        // Dalam WIB harus pukul 23:59:59 pada tanggal 2026-10-15
        $this->assertSame(
            '2026-10-15 23:59:59',
            $closesAt->copy()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s')
        );

        // Dalam UTC harus 16:59:59 pada 2026-10-15 (WIB - 7 jam)
        $this->assertSame(
            '2026-10-15 16:59:59',
            $closesAt->format('Y-m-d H:i:s')
        );
    }

    public function test_can_be_rated_window(): void
    {
        // 2026-10-15 12:00 WIB = 05:00 UTC
        $menu = new CateringMenu([
            'served_at' => Carbon::parse('2026-10-15 05:00:00', 'UTC'),
        ]);

        // Sebelum dihidangkan (11:59 WIB / 04:59 UTC)
        Carbon::setTestNow(Carbon::parse('2026-10-15 04:59:59', 'UTC'));
        $this->assertFalse($menu->canBeRated());

        // Saat dihidangkan (12:00 WIB / 05:00 UTC)
        Carbon::setTestNow(Carbon::parse('2026-10-15 05:00:00', 'UTC'));
        $this->assertTrue($menu->canBeRated());

        // Sore hari (18:00 WIB / 11:00 UTC)
        Carbon::setTestNow(Carbon::parse('2026-10-15 11:00:00', 'UTC'));
        $this->assertTrue($menu->canBeRated());

        // Menjelang tengah malam (23:59 WIB / 16:59 UTC)
        Carbon::setTestNow(Carbon::parse('2026-10-15 16:59:50', 'UTC'));
        $this->assertTrue($menu->canBeRated());

        // Lewat tengah malam (00:00:05 WIB besoknya / 17:00:05 UTC)
        Carbon::setTestNow(Carbon::parse('2026-10-15 17:00:05', 'UTC'));
        $this->assertFalse($menu->canBeRated());

        Carbon::setTestNow();
    }
}
