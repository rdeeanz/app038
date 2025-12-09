<?php

namespace Tests\Unit\Modules\ERPIntegration\Services;

use App\Modules\ERPIntegration\Services\YamlMappingService;
use Tests\TestCase;
use Mockery;

class YamlMappingServiceTest extends TestCase
{
    protected YamlMappingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new YamlMappingService();
    }

    public function test_can_transform_data_with_simple_mapping(): void
    {
        $mapping = [
            'target' => 'sap_order',
            'fields' => [
                'order_number' => [
                    'source' => 'order_number',
                ],
                'order_date' => [
                    'source' => 'order_date',
                ],
            ],
        ];

        $sourceData = [
            'order_number' => 'ORD-001',
            'order_date' => '2024-01-15',
        ];

        $result = $this->service->transform($sourceData, $mapping);

        $this->assertEquals('ORD-001', $result['order_number']);
        $this->assertEquals('2024-01-15', $result['order_date']);
    }

    public function test_can_transform_data_with_nested_mapping(): void
    {
        $mapping = [
            'target' => 'sap_order',
            'fields' => [
                'order_number' => [
                    'source' => 'order.order_number',
                ],
                'customer' => [
                    'source' => 'order.customer.name',
                ],
            ],
        ];

        $sourceData = [
            'order' => [
                'order_number' => 'ORD-001',
                'customer' => [
                    'name' => 'John Doe',
                ],
            ],
        ];

        $result = $this->service->transform($sourceData, $mapping);

        $this->assertEquals('ORD-001', $result['order_number']);
        $this->assertEquals('John Doe', $result['customer']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

