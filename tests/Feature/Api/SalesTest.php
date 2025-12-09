<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SalesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_can_list_orders(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('sales.view');

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/sales/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);
    }

    public function test_can_create_order(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('sales.create');

        Sanctum::actingAs($user);

        $orderData = [
            'customer_id' => 1,
            'order_number' => 'ORD-001',
            'total' => 100.00,
            'status' => 'pending',
        ];

        $response = $this->postJson('/api/sales/orders', $orderData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'order_number',
                    'total',
                    'status',
                ],
            ]);

        Queue::assertPushed(\App\Modules\Sales\Jobs\ProcessOrderJob::class);
    }

    public function test_can_get_order_statistics(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('sales.view');

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/sales/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_orders',
                    'total_revenue',
                ],
            ]);
    }
}

