<?php

namespace Tests\Integration\Modules\ERPIntegration;

use App\Modules\ERPIntegration\Services\ERPIntegrationService;
use App\Modules\ERPIntegration\Repositories\ERPIntegrationRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ERPIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected ERPIntegrationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        
        $repository = new ERPIntegrationRepository();
        $this->service = new ERPIntegrationService($repository);
    }

    public function test_can_initiate_sync(): void
    {
        $syncData = [
            'type' => 'products',
            'endpoint' => '/api/products',
            'priority' => 'normal',
        ];

        $result = $this->service->syncData($syncData);

        $this->assertArrayHasKey('sync_id', $result);
        $this->assertNotEmpty($result['sync_id']);

        Queue::assertPushed(\App\Modules\ERPIntegration\Jobs\SyncERPDataJob::class);
    }

    public function test_can_get_sync_status(): void
    {
        $syncData = [
            'type' => 'products',
            'endpoint' => '/api/products',
        ];

        $sync = $this->service->syncData($syncData);
        $syncId = $sync['sync_id'];

        $status = $this->service->getSyncStatus($syncId);

        $this->assertArrayHasKey('status', $status);
    }
}

