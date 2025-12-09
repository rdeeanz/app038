<?php

namespace Tests\Unit\Modules\Sales;

use App\Modules\Sales\Services\SalesService;
use App\Modules\Sales\Repositories\SalesRepositoryInterface;
use Tests\TestCase;
use Mockery;

class SalesServiceTest extends TestCase
{
    protected SalesService $service;
    protected $repositoryMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repositoryMock = Mockery::mock(SalesRepositoryInterface::class);
        $this->service = new SalesService($this->repositoryMock);
    }

    public function test_can_create_order(): void
    {
        $orderData = [
            'customer_id' => 1,
            'total' => 100.00,
            'status' => 'pending',
        ];

        $expectedOrder = array_merge($orderData, ['id' => 1]);

        $this->repositoryMock
            ->shouldReceive('create')
            ->once()
            ->with($orderData)
            ->andReturn($expectedOrder);

        $result = $this->service->createOrder($orderData);

        $this->assertEquals($expectedOrder, $result);
        $this->assertEquals(1, $result['id']);
    }

    public function test_can_get_order_statistics(): void
    {
        $expectedStats = [
            'total_orders' => 100,
            'total_revenue' => 50000.00,
        ];

        $this->repositoryMock
            ->shouldReceive('getStatistics')
            ->once()
            ->andReturn($expectedStats);

        $result = $this->service->getStatistics();

        $this->assertEquals($expectedStats, $result);
        $this->assertArrayHasKey('total_orders', $result);
        $this->assertArrayHasKey('total_revenue', $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

