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
        $resident = User::factory()->resident()->create();

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
}
