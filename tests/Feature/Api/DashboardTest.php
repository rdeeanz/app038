<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_access_dashboard_with_authentication(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/dashboard/data');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'stats' => [
                    'total_orders',
                    'total_revenue',
                    'active_integrations',
                    'low_stock_count',
                ],
            ]);
    }

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->getJson('/api/dashboard/data');

        $response->assertStatus(401);
    }
}

