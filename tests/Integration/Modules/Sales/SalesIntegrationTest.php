<?php

namespace Tests\Integration\Modules\Sales;

use App\Modules\Sales\Services\SalesService;
use App\Modules\Sales\Repositories\SalesRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected SalesService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $repository = new SalesRepository();
        $this->service = new SalesService($repository);
    }

    public function test_can_create_and_retrieve_order(): void
    {
        $orderData = [
            'customer_id' => 1,
            'order_number' => 'ORD-001',
            'total' => 100.00,
            'status' => 'pending',
        ];

        $order = $this->service->createOrder($orderData);

        $this->assertNotNull($order);
        $this->assertArrayHasKey('id', $order);
        $this->assertEquals('ORD-001', $order['order_number']);

        $retrieved = $this->service->getOrder($order['id']);

        $this->assertEquals($order['id'], $retrieved['id']);
        $this->assertEquals($order['order_number'], $retrieved['order_number']);
    }

    public function test_can_list_orders(): void
    {
        // Create multiple orders
        for ($i = 1; $i <= 5; $i++) {
            $this->service->createOrder([
                'customer_id' => $i,
                'order_number' => "ORD-00{$i}",
                'total' => 100.00 * $i,
                'status' => 'pending',
            ]);
        }

        $orders = $this->service->listOrders(['limit' => 10]);

        $this->assertCount(5, $orders);
    }
}

