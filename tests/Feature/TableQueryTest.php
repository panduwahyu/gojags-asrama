<?php

namespace Tests\Feature;

use App\Enums\PermitStatus;
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
                ->status('status', PermitStatus::class)
                ->sort(['reason'])
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

    public function test_search_by_enum_label_matches_cases_and_ignores_raw_values(): void
    {
        $user = User::factory()->create();
        $pending = LeavePermit::factory()->for($user)->create(['status' => PermitStatus::Pending, 'reason' => 'Alpha']);
        $approved = LeavePermit::factory()->for($user)->create(['status' => PermitStatus::Approved, 'reason' => 'Beta']);

        $diajukan = $this->getJson('/api/test-permits?q=Diajukan')->assertOk();
        $this->assertSame([$pending->id], array_column($diajukan->json('data'), 'id'));

        $setuju = $this->getJson('/api/test-permits?q=setuju')->assertOk();
        $this->assertSame([$approved->id], array_column($setuju->json('data'), 'id'));

        $raw = $this->getJson('/api/test-permits?q=pending')->assertOk();
        $this->assertEmpty($raw->json('data'));
    }

    public function test_sort_by_status_follows_workflow_case_order(): void
    {
        $user = User::factory()->create();
        $approved = LeavePermit::factory()->for($user)->create(['status' => PermitStatus::Approved]);
        $pending = LeavePermit::factory()->for($user)->create(['status' => PermitStatus::Pending]);
        $rejected = LeavePermit::factory()->for($user)->create(['status' => PermitStatus::Rejected]);

        $asc = $this->getJson('/api/test-permits?sort=status&direction=asc')->assertOk();
        $this->assertSame([$pending->id, $approved->id, $rejected->id], array_column($asc->json('data'), 'id'));

        $desc = $this->getJson('/api/test-permits?sort=status&direction=desc')->assertOk();
        $this->assertSame([$rejected->id, $approved->id, $pending->id], array_column($desc->json('data'), 'id'));
    }

    public function test_search_escapes_like_wildcards(): void
    {
        $user = User::factory()->create();
        $withUnderscore = LeavePermit::factory()->for($user)->create(['reason' => 'alasan_satu']);
        $withOtherChar = LeavePermit::factory()->for($user)->create(['reason' => 'alasanXdua']);

        $underscoreQuery = $this->getJson('/api/test-permits?q=alasan_')->assertOk();
        $this->assertSame([$withUnderscore->id], array_column($underscoreQuery->json('data'), 'id'));

        $percentQuery = $this->getJson('/api/test-permits?q=%25')->assertOk();
        $this->assertEmpty($percentQuery->json('data'));
    }
}
