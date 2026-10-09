<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TableSortingTest extends TestCase
{
    use RefreshDatabase;

    public function test_rooms_sort_all_columns_before_pagination_and_stack_occupants(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        foreach (['302' => ['Zed'], '303' => ['Alex', 'Budi']] as $name => $people) {
            $room = DB::table('kamar')->where('name', $name)->value('id');
            foreach ($people as $index => $person) {
                $user = User::factory()->create(['name' => $person]);
                DB::table('kamar_slots')->where('kamar_id', $room)->where('nomor', $index + 1)->update(['user_id' => $user->id, 'checked_in_at' => now()]);
            }
        }
        $this->getJson('/api/asrama/kamar?sort=name&direction=asc')->assertOk()->assertJsonPath('records.data.0.name', '301');
        $this->getJson('/api/asrama/kamar?sort=name&direction=asc&page=2')->assertJsonPath('records.data.0.name', '311');
        $this->getJson('/api/asrama/kamar?sort=name&direction=desc')->assertJsonPath('records.data.0.name', '526');
        $this->getJson('/api/asrama/kamar?sort=lantai&direction=asc')->assertJsonPath('records.data.0.lantai', 3);
        $this->getJson('/api/asrama/kamar?sort=lantai&direction=desc')->assertJsonPath('records.data.0.lantai', 5);
        foreach (['status', 'penghuni_count'] as $column) {
            $this->getJson('/api/asrama/kamar?sort='.$column.'&direction=desc')->assertJsonPath('records.data.0.name', '303')->assertJsonPath('records.data.0.status', 'Penuh');
            $this->getJson('/api/asrama/kamar?sort='.$column.'&direction=asc')->assertJsonPath('records.data.0.status', 'Kosong');
        }
        $this->getJson('/api/asrama/kamar?sort=penghuni&direction=desc')->assertJsonPath('records.data.0.name', '302')->assertJsonPath('records.data.1.name', '303');
        $this->getJson('/api/asrama/kamar?q=50&sort=name&direction=asc&per_page=50')->assertJsonPath('records.data.0.name', '501')->assertJsonPath('records.per_page', 50)->assertJsonCount(9, 'records.data');
        $this->get('/kamar?q=303')->assertOk()->assertSee('<div class="resident-name">Alex<small>', false)->assertSee('<div class="resident-name">Budi<small>', false)->assertDontSee('Alex, Budi');
    }

    public function test_services_sort_each_header_and_reject_unknown_columns_or_directions(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        foreach (['laundry', 'catering'] as $category) {
            $this->get('/'.$category)->assertOk()
                ->assertDontSee("@include('partials.sortable-header'", false)
                ->assertSee($category === 'catering' ? 'Rata-rata rating' : 'Perbarui status');
            DB::table($category)->insert([
                ['name' => 'Alpha', 'detail' => 'Zulu', 'date' => '2026-10-20', 'status' => 'Selesai'],
                ['name' => 'Zulu', 'detail' => 'Alpha', 'date' => '2026-10-01', 'status' => 'Diproses'],
            ]);
            foreach (['name' => 'Alpha', 'detail' => 'Zulu', 'date' => 'Zulu', 'status' => 'Zulu'] as $column => $expected) {
                $this->getJson('/api/asrama/'.$category.'?sort='.$column.'&direction=asc')->assertOk()->assertJsonPath('records.data.0.name', $expected);
                $this->getJson('/api/asrama/'.$category.'?sort='.$column.'&direction=desc')->assertOk()->assertJsonPath('records.data.0.name', $expected === 'Alpha' ? 'Zulu' : 'Alpha');
            }
            $this->getJson('/api/asrama/'.$category.'?sort=lantai')->assertUnprocessable()->assertJsonValidationErrors('sort');
        }
        $this->getJson('/api/asrama/kamar?sort=name;DROP TABLE users')->assertUnprocessable()->assertJsonValidationErrors('sort');
        $this->getJson('/api/asrama/kamar?direction=invalid')->assertUnprocessable()->assertJsonValidationErrors('direction');
    }
}
