<?php

namespace Tests\Unit;

use App\Enums\ComplaintStatus;
use App\Enums\Gender;
use App\Enums\LaundryStatus;
use App\Enums\MealTime;
use App\Enums\PermitStatus;
use App\Enums\Religion;
use App\Enums\Role;
use App\Enums\RoomStatus;
use PHPUnit\Framework\TestCase;

class EnumLabelsTest extends TestCase
{
    public function test_role_enum_values_and_labels(): void
    {
        $this->assertSame('admin', Role::Admin->value);
        $this->assertSame('resident', Role::Resident->value);

        $this->assertSame('Admin', Role::Admin->label());
        $this->assertSame('Penghuni', Role::Resident->label());

        $this->assertNull(Role::tryFrom('unknown'));
    }

    public function test_gender_enum_values_and_labels(): void
    {
        $this->assertSame('male', Gender::Male->value);
        $this->assertSame('female', Gender::Female->value);

        $this->assertSame('Laki-laki', Gender::Male->label());
        $this->assertSame('Perempuan', Gender::Female->label());

        $this->assertNull(Gender::tryFrom('other'));
    }

    public function test_meal_time_enum_values_and_labels(): void
    {
        $this->assertSame('breakfast', MealTime::Breakfast->value);
        $this->assertSame('lunch', MealTime::Lunch->value);
        $this->assertSame('dinner', MealTime::Dinner->value);

        $this->assertSame('Pagi', MealTime::Breakfast->label());
        $this->assertSame('Siang', MealTime::Lunch->label());
        $this->assertSame('Malam', MealTime::Dinner->label());

        $this->assertNull(MealTime::tryFrom('supper'));
    }

    public function test_room_status_enum_values_and_labels(): void
    {
        $this->assertSame('empty', RoomStatus::Empty->value);
        $this->assertSame('assigned', RoomStatus::Assigned->value);
        $this->assertSame('occupied', RoomStatus::Occupied->value);
        $this->assertSame('full', RoomStatus::Full->value);
        $this->assertSame('inactive', RoomStatus::Inactive->value);

        $this->assertSame('Kosong', RoomStatus::Empty->label());
        $this->assertSame('Assigned', RoomStatus::Assigned->label());
        $this->assertSame('Terisi', RoomStatus::Occupied->label());
        $this->assertSame('Penuh', RoomStatus::Full->label());
        $this->assertSame('Nonaktif', RoomStatus::Inactive->label());
    }

    public function test_permit_status_enum_values_and_labels(): void
    {
        $this->assertSame('pending', PermitStatus::Pending->value);
        $this->assertSame('approved', PermitStatus::Approved->value);
        $this->assertSame('rejected', PermitStatus::Rejected->value);
        $this->assertSame('cancelled', PermitStatus::Cancelled->value);

        $this->assertSame('Diajukan', PermitStatus::Pending->label());
        $this->assertSame('Disetujui', PermitStatus::Approved->label());
        $this->assertSame('Ditolak', PermitStatus::Rejected->label());
        $this->assertSame('Dibatalkan', PermitStatus::Cancelled->label());
    }

    public function test_complaint_status_enum_values_and_labels(): void
    {
        $this->assertSame('pending', ComplaintStatus::Pending->value);
        $this->assertSame('in_progress', ComplaintStatus::InProgress->value);
        $this->assertSame('resolved', ComplaintStatus::Resolved->value);

        $this->assertSame('Diajukan', ComplaintStatus::Pending->label());
        $this->assertSame('Sedang ditangani', ComplaintStatus::InProgress->label());
        $this->assertSame('Resolved', ComplaintStatus::Resolved->label());
    }

    public function test_laundry_status_enum_values_and_labels(): void
    {
        $this->assertSame('pending', LaundryStatus::Pending->value);
        $this->assertSame('done', LaundryStatus::Done->value);
        $this->assertSame('cancelled', LaundryStatus::Cancelled->value);

        $this->assertSame('Diajukan', LaundryStatus::Pending->label());
        $this->assertSame('Selesai', LaundryStatus::Done->label());
        $this->assertSame('Dibatalkan', LaundryStatus::Cancelled->label());
    }

    public function test_religion_enum_has_the_six_official_religions(): void
    {
        $this->assertSame(
            ['islam', 'kristen', 'katolik', 'hindu', 'buddha', 'konghucu'],
            array_column(Religion::cases(), 'value')
        );
        $this->assertSame('Islam', Religion::Islam->label());
        $this->assertSame('Konghucu', Religion::Konghucu->label());
    }

    public function test_status_enums_list_cases_in_workflow_order(): void
    {
        $this->assertSame(['pending', 'in_progress', 'resolved'], array_column(ComplaintStatus::cases(), 'value'));
        $this->assertSame(['pending', 'done', 'cancelled'], array_column(LaundryStatus::cases(), 'value'));
        $this->assertSame(['pending', 'approved', 'rejected', 'cancelled'], array_column(PermitStatus::cases(), 'value'));
    }
}
