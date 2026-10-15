<?php

namespace Tests\Feature;

use App\Models\CateringMenu;
use App\Models\Complaint;
use App\Models\LaundryOrder;
use App\Models\LeavePermit;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/** Ekspor data tabel ke Excel dan PDF (keputusan pengelola no. 19). */
class ExportTest extends TestCase
{
    use RefreshDatabase;

    /** @return list<list<string>> isi lembar pertama berkas xlsx hasil unduhan */
    private function sheet($response): array
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($path, $response->streamedContent());
        $rows = IOFactory::load($path)->getActiveSheet()->toArray(null, true, true, false);
        unlink($path);

        return $rows;
    }

    public function test_every_table_exports_to_xlsx_with_its_headings(): void
    {
        $admin = User::factory()->admin()->create();
        $resident = User::factory()->resident()->profiled()->create();
        LeavePermit::factory()->for($resident)->create();
        Complaint::factory()->for($resident)->create();
        LaundryOrder::factory()->for($resident)->create();
        CateringMenu::factory()->create();
        Room::factory()->create();

        $expected = [
            'leave-permits' => 'Nama',
            'complaints' => 'Pelapor',
            'laundry-orders' => 'Pemesan',
            'catering-menus' => 'No. menu',
            'rooms' => 'Kamar',
            'residents' => 'Nama',
        ];

        foreach ($expected as $path => $firstHeading) {
            $response = $this->actingAs($admin)->get("/api/{$path}/export?format=xlsx");

            $response->assertOk();
            $this->assertStringContainsString('spreadsheetml', $response->headers->get('Content-Type'), $path);
            $this->assertStringContainsString('.xlsx', $response->headers->get('Content-Disposition'), $path);
            $rows = $this->sheet($response);
            $this->assertSame($firstHeading, $rows[0][0], $path);
            $this->assertCount(2, $rows, "{$path}: judul kolom + satu baris data");
        }
    }

    public function test_every_table_exports_to_pdf(): void
    {
        $admin = User::factory()->admin()->create();
        LaundryOrder::factory()->create(['items' => 'Kemeja putih']);

        foreach (['leave-permits', 'complaints', 'laundry-orders', 'catering-menus', 'rooms', 'residents'] as $path) {
            $response = $this->actingAs($admin)->get("/api/{$path}/export?format=pdf");

            $response->assertOk();
            $this->assertSame('application/pdf', $response->headers->get('Content-Type'), $path);
            $this->assertStringStartsWith('%PDF', $response->streamedContent(), $path);
        }
    }

    public function test_export_follows_search_and_sort(): void
    {
        $admin = User::factory()->admin()->create();
        LaundryOrder::factory()->create(['items' => 'Kemeja putih']);
        LaundryOrder::factory()->create(['items' => 'Celana panjang']);
        LaundryOrder::factory()->create(['items' => 'Kemeja batik']);

        $rows = $this->sheet($this->actingAs($admin)->get('/api/laundry-orders/export?format=xlsx&q=Kemeja&sort=items&direction=asc'));

        $this->assertSame(['Kemeja batik', 'Kemeja putih'], array_column(array_slice($rows, 1), 1));
    }

    public function test_resident_exports_only_their_own_rows(): void
    {
        $mine = User::factory()->resident()->create();
        $other = User::factory()->resident()->create();
        LaundryOrder::factory()->for($mine)->create(['items' => 'Milik saya']);
        LaundryOrder::factory()->for($other)->create(['items' => 'Milik orang lain']);

        $rows = $this->sheet($this->actingAs($mine)->get('/api/laundry-orders/export?format=xlsx'));

        $this->assertSame(['Milik saya'], array_column(array_slice($rows, 1), 1));
    }

    public function test_room_and_resident_exports_are_admin_only(): void
    {
        $resident = User::factory()->resident()->create();

        $this->actingAs($resident)->get('/api/rooms/export?format=xlsx')->assertForbidden();
        $this->actingAs($resident)->get('/api/residents/export?format=pdf')->assertForbidden();
    }

    public function test_guests_cannot_export_and_the_format_is_validated(): void
    {
        $this->getJson('/api/laundry-orders/export?format=xlsx')->assertUnauthorized();

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->getJson('/api/laundry-orders/export')->assertUnprocessable()->assertJsonValidationErrors(['format']);
        $this->actingAs($admin)->getJson('/api/laundry-orders/export?format=docx')->assertUnprocessable()->assertJsonValidationErrors(['format']);
    }

    public function test_cells_that_look_like_formulas_stay_plain_text(): void
    {
        $admin = User::factory()->admin()->create();
        LaundryOrder::factory()->create(['items' => '=HYPERLINK("http://x","klik")']);

        $rows = $this->sheet($this->actingAs($admin)->get('/api/laundry-orders/export?format=xlsx'));

        $this->assertSame('=HYPERLINK("http://x","klik")', $rows[1][1]);
    }

    public function test_pdf_escapes_html_in_cells(): void
    {
        $admin = User::factory()->admin()->create();
        LaundryOrder::factory()->create(['items' => '<script>alert(1)</script>']);

        $content = $this->actingAs($admin)->get('/api/laundry-orders/export?format=pdf')->streamedContent();

        $this->assertStringStartsWith('%PDF', $content);
        $this->assertStringNotContainsString('<script>', $content);
    }
}
