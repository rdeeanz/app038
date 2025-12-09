<?php

namespace Tests\Unit\Modules\ERPIntegration\Services;

use App\Modules\ERPIntegration\Services\ERPIntegrationService;
use App\Modules\ERPIntegration\Repositories\ERPIntegrationRepositoryInterface;
use Tests\TestCase;
use Mockery;

class ERPIntegrationServiceTest extends TestCase
{
    protected ERPIntegrationService $service;
    protected $repositoryMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repositoryMock = Mockery::mock(ERPIntegrationRepositoryInterface::class);
        $this->service = new ERPIntegrationService($this->repositoryMock);
    }

    public function test_can_list_integrations(): void
    {
        $expectedIntegrations = [
            [
                'id' => 1,
                'name' => 'SAP OData',
                'type' => 'odata',
                'status' => 'active',
            ],
        ];

        $this->repositoryMock
            ->shouldReceive('listIntegrations')
            ->once()
            ->andReturn($expectedIntegrations);

        $result = $this->service->listIntegrations();

        $this->assertEquals($expectedIntegrations, $result);
    }

    public function test_can_test_connection(): void
    {
        $this->repositoryMock
            ->shouldReceive('testConnection')
            ->once()
            ->with(1)
            ->andReturn([
                'connected' => true,
                'message' => 'Connection successful',
            ]);

        $result = $this->service->testConnection(1);

        $this->assertTrue($result['connected']);
        $this->assertEquals('Connection successful', $result['message']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

