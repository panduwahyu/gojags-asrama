<?php

namespace Tests\Feature;

use App\Enums\LaundryStatus;
use App\Models\LaundryOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaundryOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_resident_can_submit_laundry_order_and_set_count_bounds_are_enforced(): void
    {
        $resident = User::factory()->resident()->staying()->create();

        $this->actingAs($resident)
            ->postJson('/api/laundry-orders', [
                'items' => '2 kemeja, 1 celana',
                'set_count' => 2,
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Pengajuan laundry berhasil dikirim.');

        $this->assertDatabaseHas('laundry_orders', [
            'user_id' => $resident->id,
            'items' => '2 kemeja, 1 celana',
            'set_count' => 2,
            'status' => LaundryStatus::Pending->value,
            'cost' => null,
        ]);

        foreach ([0, 1001] as $invalidCount) {
            $this->actingAs($resident)
                ->postJson('/api/laundry-orders', [
                    'items' => 'Kaos',
                    'set_count' => $invalidCount,
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['set_count']);
        }
    }

    public function test_visibility_by_role_and_admin_status_update_with_cost_validation(): void
    {
        $admin = User::factory()->admin()->create();
        $firstResident = User::factory()->resident()->create();
        $secondResident = User::factory()->resident()->create();

        $order = LaundryOrder::factory()->for($firstResident)->create();
        LaundryOrder::factory()->for($secondResident)->create();

        $this->actingAs($firstResident)
            ->getJson('/api/laundry-orders')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $order->id);

        $this->actingAs($admin)
            ->getJson('/api/laundry-orders')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->actingAs($firstResident)
            ->patchJson("/api/laundry-orders/{$order->id}", [
                'status' => LaundryStatus::Done->value,
                'cost' => 15000,
            ])
            ->assertForbidden();

        // done tanpa cost -> 422
        $this->actingAs($admin)
            ->patchJson("/api/laundry-orders/{$order->id}", [
                'status' => LaundryStatus::Done->value,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cost']);

        // cost 3 desimal -> 422
        $this->actingAs($admin)
            ->patchJson("/api/laundry-orders/{$order->id}", [
                'status' => LaundryStatus::Done->value,
                'cost' => '15000.123',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cost']);

        // done + cost valid -> 200
        $this->actingAs($admin)
            ->patchJson("/api/laundry-orders/{$order->id}", [
                'status' => LaundryStatus::Done->value,
                'cost' => 15000,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Status berhasil diperbarui.');

        $order->refresh();
        $this->assertSame(LaundryStatus::Done, $order->status);
        $this->assertSame('15000.00', $order->cost);
    }

    public function test_guest_cannot_access_laundry_orders(): void
    {
        $this->getJson('/api/laundry-orders')->assertUnauthorized();
    }

    public function test_laundry_orders_sort_by_status_follows_workflow_order(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->resident()->create();
        $done = LaundryOrder::factory()->for($user)->create(['status' => LaundryStatus::Done, 'cost' => 10000]);
        $pending = LaundryOrder::factory()->for($user)->create(['status' => LaundryStatus::Pending, 'cost' => null]);

        $response = $this->actingAs($admin)
            ->getJson('/api/laundry-orders?sort=status&direction=asc')
            ->assertOk();

        $this->assertSame([$pending->id, $done->id], array_column($response->json('data'), 'id'));
    }

    public function test_admin_can_reset_cost_to_null_and_omitted_cost_preserves_existing_cost(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->resident()->create();
        $order = LaundryOrder::factory()->for($user)->create(['status' => LaundryStatus::Pending, 'cost' => 15000]);

        $this->actingAs($admin)
            ->patchJson("/api/laundry-orders/{$order->id}", ['status' => LaundryStatus::Pending->value])
            ->assertOk();
        $this->assertSame('15000.00', $order->fresh()->cost);

        $this->actingAs($admin)
            ->patchJson("/api/laundry-orders/{$order->id}", ['status' => LaundryStatus::Pending->value, 'cost' => null])
            ->assertOk();
        $this->assertNull($order->fresh()->cost);
    }

    public function test_submitting_laundry_order_ignores_status_and_cost(): void
    {
        $resident = User::factory()->resident()->staying()->create();

        $this->actingAs($resident)
            ->postJson('/api/laundry-orders', [
                'items' => 'Celana jeans',
                'set_count' => 1,
                'status' => LaundryStatus::Done->value,
                'cost' => 50000,
            ])
            ->assertCreated();

        $order = LaundryOrder::latest('id')->firstOrFail();
        $this->assertSame($resident->id, $order->user_id);
        $this->assertSame(LaundryStatus::Pending, $order->status);
        $this->assertNull($order->cost);
    }

    public function test_cost_must_be_within_zero_and_ten_billion(): void
    {
        $admin = User::factory()->admin()->create();
        $order = LaundryOrder::factory()->create();

        foreach ([-1, '10000000000'] as $invalid) {
            $this->actingAs($admin)
                ->patchJson("/api/laundry-orders/{$order->id}", [
                    'status' => LaundryStatus::Done->value,
                    'cost' => $invalid,
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['cost']);
        }

        $this->actingAs($admin)
            ->patchJson("/api/laundry-orders/{$order->id}", [
                'status' => LaundryStatus::Done->value,
                'cost' => '9999999999.99',
            ])
            ->assertOk();

        $this->assertSame('9999999999.99', $order->fresh()->cost);
    }

    public function test_completed_order_cannot_return_to_pending(): void
    {
        $admin = User::factory()->admin()->create();
        $order = LaundryOrder::factory()->done()->create();

        $this->actingAs($admin)
            ->patchJson("/api/laundry-orders/{$order->id}", ['status' => LaundryStatus::Pending->value])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => 'Pesanan yang sudah selesai tidak dapat dikembalikan ke status Diajukan.']);

        $this->assertSame(LaundryStatus::Done, $order->fresh()->status);
    }

    public function test_admin_cannot_set_cancelled_status_and_cancelled_order_is_immutable(): void
    {
        $admin = User::factory()->admin()->create();
        $pending = LaundryOrder::factory()->create();
        $cancelled = LaundryOrder::factory()->create(['status' => LaundryStatus::Cancelled]);

        $this->actingAs($admin)
            ->patchJson("/api/laundry-orders/{$pending->id}", ['status' => LaundryStatus::Cancelled->value])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);

        $this->actingAs($admin)
            ->patchJson("/api/laundry-orders/{$cancelled->id}", ['status' => LaundryStatus::Done->value, 'cost' => 10000])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => 'Pesanan yang sudah dibatalkan tidak dapat diubah.']);
    }

    public function test_resident_can_cancel_own_pending_order(): void
    {
        $resident = User::factory()->resident()->create();
        $order = LaundryOrder::factory()->for($resident)->create();

        $this->actingAs($resident)
            ->patchJson("/api/laundry-orders/{$order->id}/cancel")
            ->assertOk()
            ->assertJsonPath('message', 'Pesanan laundry dibatalkan.');

        $this->assertSame(LaundryStatus::Cancelled, $order->fresh()->status);
    }

    public function test_only_pending_orders_can_be_cancelled_and_only_by_their_owner(): void
    {
        $resident = User::factory()->resident()->create();
        $other = User::factory()->resident()->create();
        $admin = User::factory()->admin()->create();
        $done = LaundryOrder::factory()->for($resident)->done()->create();
        $pending = LaundryOrder::factory()->for($resident)->create();

        $this->patchJson("/api/laundry-orders/{$pending->id}/cancel")->assertUnauthorized();

        $this->actingAs($resident)
            ->patchJson("/api/laundry-orders/{$done->id}/cancel")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => 'Hanya pesanan berstatus Diajukan yang dapat dibatalkan.']);

        $this->actingAs($other)->patchJson("/api/laundry-orders/{$pending->id}/cancel")->assertNotFound();
        $this->actingAs($admin)->patchJson("/api/laundry-orders/{$pending->id}/cancel")->assertForbidden();
    }

    public function test_resident_who_is_not_staying_cannot_submit_laundry(): void
    {
        $notStaying = User::factory()->resident()->create();

        $this->actingAs($notStaying)
            ->postJson('/api/laundry-orders', ['items' => 'Kaos', 'set_count' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['room' => 'Fitur ini hanya untuk penghuni yang sedang menginap (sudah check-in).']);

        $this->assertSame(0, LaundryOrder::count());
    }
}
