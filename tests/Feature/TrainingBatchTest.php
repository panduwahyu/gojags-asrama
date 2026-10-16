<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TrainingBatchTest extends TestCase
{
    use RefreshDatabase;

    private function fake(array $responses): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::preventStrayRequests();
        Http::fake($responses);
    }

    private function payload(): array
    {
        return ['data' => collect(range(1, 3))->map(fn ($batch) => [
            'id' => 'batch-'.$batch, 'name' => 'Peserta Pelatihan Angkatan '.$batch,
            'participants' => collect(range(1, 10))->map(fn ($person) => [
                'id' => 'peserta-'.$batch.'-'.$person, 'name' => 'Peserta '.$batch.'-'.$person,
                'email' => 'peserta.'.$batch.'.'.$person.'@example.test', 'angkatan' => 'Angkatan '.$batch,
                'gender' => $person <= 6 ? 'Laki-laki' : 'Perempuan', 'agama' => $person % 5 === 0 ? 'Kristen' : 'Islam',
            ])->all(),
        ])->all()];
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.api_gojags.url' => 'https://training.example.test', 'services.api_gojags.token' => 'test-token']);
        Http::preventStrayRequests();
    }

    public function test_sync_is_admin_only_and_repeatable_without_changing_passwords(): void
    {
        $this->fake(['training.example.test/*' => Http::response($this->payload())]);
        $this->postJson('/api/asrama/kamar/sync')->assertUnauthorized();
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user)->postJson('/api/asrama/kamar/sync')->assertForbidden();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->postJson('/api/asrama/kamar/sync')->assertOk()->assertJsonCount(3, 'batches')->assertJsonPath('batches.0.participants_count', 10);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer test-token') && $request->url() === 'https://training.example.test/api/batches');
        $passwords = DB::table('users')->pluck('password', 'id')->all();
        $this->postJson('/api/asrama/kamar/sync')->assertOk();
        $this->assertDatabaseCount('training_batches', 3);
        $this->assertDatabaseCount('training_participants', 30);
        $this->assertDatabaseCount('users', 32);
        $this->assertSame($passwords, DB::table('users')->pluck('password', 'id')->all());
        $this->get('/kamar')->assertOk()->assertSee('Sinkronisasi data peserta')->assertSee('Peserta Pelatihan Angkatan 3');
    }

    public function test_allocation_requires_batch_and_only_allocates_selected_members(): void
    {
        $this->fake(['training.example.test/*' => Http::response($this->payload())]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->postJson('/api/asrama/kamar/sync')->assertOk();
        $this->postJson('/api/asrama/kamar/allocate')->assertUnprocessable();
        $this->postJson('/api/asrama/kamar/allocate', ['batch_id' => 9999])->assertUnprocessable();
        $batches = DB::table('training_batches')->orderBy('id')->pluck('id');
        $this->postJson('/api/asrama/kamar/allocate', ['batch_id' => $batches[0]])->assertOk()->assertJsonCount(10, 'allocated');
        $before = DB::table('kamar_slots')->whereNotNull('user_id')->pluck('user_id', 'id')->all();
        $selectedIds = DB::table('training_participants')->where('batch_id', $batches[0])->pluck('user_id')->all();
        $this->assertEqualsCanonicalizing($selectedIds, array_values($before));
        $this->postJson('/api/asrama/kamar/allocate', ['batch_id' => $batches[0]])->assertOk()->assertJsonCount(0, 'allocated');
        $this->postJson('/api/asrama/kamar/allocate', ['batch_id' => $batches[1]])->assertOk()->assertJsonCount(10, 'allocated');
        foreach ($before as $slot => $user) $this->assertDatabaseHas('kamar_slots', ['id' => $slot, 'user_id' => $user]);
        $this->postJson('/api/asrama/kamar/allocate', ['batch_id' => $batches[2]])->assertOk()->assertJsonCount(10, 'allocated');
        $this->assertSame(30, DB::table('kamar_slots')->whereNotNull('user_id')->count());
        $people = DB::table('kamar_slots')->join('users', 'users.id', '=', 'user_id')->whereNotNull('user_id')->get()->groupBy('kamar_id');
        foreach ($people as $room) {
            $this->assertLessThanOrEqual(2, $room->count());
            $this->assertCount(1, $room->pluck('gender')->unique());
            $this->assertCount(1, $room->pluck('agama')->unique());
        }
    }

    public function test_failed_or_invalid_sync_does_not_create_partial_data_or_claim_existing_accounts(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->fake(['*' => Http::response([], 503)]);
        $this->postJson('/api/asrama/kamar/sync')->assertUnprocessable();
        $invalid = $this->payload();
        $invalid['data'][2]['participants'][9]['gender'] = 'invalid';
        $this->fake(['*' => Http::response($invalid)]);
        $this->postJson('/api/asrama/kamar/sync')->assertUnprocessable();
        $duplicate = $this->payload();
        $duplicate['data'][2]['participants'][9]['id'] = $duplicate['data'][0]['participants'][0]['id'];
        $this->fake(['*' => Http::response($duplicate)]);
        $this->postJson('/api/asrama/kamar/sync')->assertUnprocessable();
        User::factory()->create(['email' => 'peserta.3.10@example.test', 'role' => 'admin']);
        $this->fake(['*' => Http::response($this->payload())]);
        $this->postJson('/api/asrama/kamar/sync')->assertUnprocessable();
        $this->assertDatabaseCount('training_batches', 0);
        $this->assertDatabaseCount('training_participants', 0);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_removed_participant_is_excluded_and_occupied_profile_cannot_be_changed(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->fake(['*' => Http::response($this->payload())]);
        $this->postJson('/api/asrama/kamar/sync')->assertOk();
        $payload = $this->payload();
        array_pop($payload['data'][0]['participants']);
        $this->fake(['*' => Http::response($payload)]);
        $this->postJson('/api/asrama/kamar/sync')->assertOk();
        $batchId = DB::table('training_batches')->where('source_id', 'batch-1')->value('id');
        $this->postJson('/api/asrama/kamar/allocate', ['batch_id' => $batchId])->assertOk()->assertJsonCount(9, 'allocated');
        $this->postJson('/api/asrama/kamar/sync')->assertOk();
        $payload['data'][0]['participants'][0]['agama'] = 'Hindu';
        $this->fake(['*' => Http::response($payload)]);
        $this->postJson('/api/asrama/kamar/sync')->assertUnprocessable();
        $this->assertDatabaseHas('users', ['email' => 'peserta.1.1@example.test', 'agama' => 'Islam']);
        $this->assertDatabaseHas('training_participants', ['source_id' => 'peserta-1-10', 'active' => false]);
    }
}
