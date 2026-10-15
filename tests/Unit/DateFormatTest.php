<?php

namespace Tests\Unit;

use App\Support\DateFormat;
use PHPUnit\Framework\TestCase;

class DateFormatTest extends TestCase
{
    public function test_date_format_utc_constant(): void
    {
        $this->assertSame('date_format:Y-m-d\TH:i:s.v\Z', DateFormat::UTC);
    }
}
