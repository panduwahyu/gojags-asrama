<?php

namespace Tests\Feature;

use App\Models\LeavePermit;
use App\Models\User;
use App\Support\TableQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TableQueryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/api/test-permits', function (Request $request) {
            return TableQuery::for(LeavePermit::query())
                ->search(['reason'])
                ->sort(['reason', 'status'])
                ->paginate($request);
        });
    }

    public function test_invalid_per_page_returns_422(): void
    {
        $this->getJson('/api/test-permits?per_page=7')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);
    }

    public function test_unwhitelisted_sort_column_returns_422(): void
    {
        $this->getJson('/api/test-permits?sort=password')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sort']);
    }

    public function test_search_query_exceeding_100_characters_returns_422(): void
    {
        $longQuery = str_repeat('a', 101);

        $this->getJson("/api/test-permits?q={$longQuery}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['q']);
    }

    public function test_default_sort_is_id_descending(): void
    {
        $user = User::factory()->create();
        $first = LeavePermit::factory()->for($user)->create();
        $second = LeavePermit::factory()->for($user)->create();

        $response = $this->getJson('/api/test-permits')->assertOk();

        $this->assertSame([$second->id, $first->id], array_column($response->json('data'), 'id'));
    }

    public function test_non_id_sort_uses_id_as_secondary_tiebreaker(): void
    {
        $user = User::factory()->create();
        $first = LeavePermit::factory()->for($user)->create(['reason' => 'Alasan sama']);
        $second = LeavePermit::factory()->for($user)->create(['reason' => 'Alasan sama']);

        $asc = $this->getJson('/api/test-permits?sort=reason&direction=asc')->assertOk();
        $this->assertSame([$first->id, $second->id], array_column($asc->json('data'), 'id'));

        $desc = $this->getJson('/api/test-permits?sort=reason&direction=desc')->assertOk();
        $this->assertSame([$second->id, $first->id], array_column($desc->json('data'), 'id'));
    }
}
