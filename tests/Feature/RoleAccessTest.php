<?php

namespace Tests\Feature;

use App\Models\CateringMenu;
use App\Models\LaundryOrder;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public static function adminEndpointsProvider(): array
    {
        return [
            ['GET', '/api/dashboard'],
            ['GET', '/api/rooms'],
            ['POST', '/api/rooms/allocate'],
            ['GET', '/api/residents'],
            ['PATCH', '/api/residents/1'],
            ['PATCH', '/api/leave-permits/1/review'],
            ['PATCH', '/api/complaints/1/resolve'],
            ['PATCH', '/api/laundry-orders/1'],
            ['POST', '/api/catering-menus'],
            ['PATCH', '/api/catering-menus/1'],
        ];
    }

    public static function residentEndpointsProvider(): array
    {
        return [
            ['GET', '/api/my-room'],
            ['POST', '/api/my-room/check-in'],
            ['POST', '/api/my-room/check-out'],
            ['POST', '/api/leave-permits'],
            ['PATCH', '/api/leave-permits/1'],
            ['POST', '/api/complaints'],
            ['POST', '/api/laundry-orders'],
            ['PUT', '/api/catering-menus/1/rating'],
        ];
    }

    #[DataProvider('adminEndpointsProvider')]
    public function test_admin_endpoints_reject_resident_with_403_and_guest_with_401(
        string $method,
        string $uri
    ): void {
        $this->json($method, $uri)->assertUnauthorized();

        $resident = User::factory()->resident()->create();
        $this->actingAs($resident)
            ->json($method, $uri)
            ->assertForbidden()
            ->assertJsonPath('message', 'Anda tidak memiliki izin untuk halaman ini.');
    }

    #[DataProvider('residentEndpointsProvider')]
    public function test_resident_endpoints_reject_admin_with_403_and_guest_with_401(
        string $method,
        string $uri
    ): void {
        $this->json($method, $uri)->assertUnauthorized();

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)
            ->json($method, $uri)
            ->assertForbidden()
            ->assertJsonPath('message', 'Anda tidak memiliki izin untuk halaman ini.');
    }

    public function test_post_without_csrf_token_returns_419(): void
    {
        $this->app['env'] = 'local';

        $this->postJson('/api/login', [
            'email' => 'admin@example.id',
            'password' => 'admin123',
        ])->assertStatus(419);
    }

    public function test_dashboard_returns_three_counts_for_admin(): void
    {
        $admin = User::factory()->admin()->create();
        Room::factory()->count(3)->create();
        LaundryOrder::factory()->count(2)->create();
        CateringMenu::factory()->count(4)->create();

        $this->actingAs($admin)
            ->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('data', [
                'rooms' => 3,
                'laundry_orders' => 2,
                'catering_menus' => 4,
            ]);
    }
}
