<?php

namespace App\Modules\ERPIntegration\Services;

use App\Modules\ERPIntegration\Jobs\SyncERPDataJob;
use App\Modules\ERPIntegration\Repositories\ERPIntegrationRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ERPIntegrationService
{
    public function __construct(
        protected ERPIntegrationRepositoryInterface $repository
    ) {}

    /**
     * List all ERP integrations
     */
    public function listIntegrations(array $filters = []): array
    {
        return $this->repository->list($filters);
    }

    /**
     * Sync data with ERP system
     */
    public function syncData(array $data): array
    {
        $syncId = uniqid('sync_', true);

        // Dispatch job to sync data asynchronously
        SyncERPDataJob::dispatch($syncId, $data)
            ->onQueue('erp-sync');

        // Store initial status
        Cache::put("erp_sync_status_{$syncId}", [
            'status' => 'pending',
            'started_at' => now(),
            'data' => $data,
        ], now()->addHours(24));

        Log::info('ERP sync initiated', [
            'sync_id' => $syncId,
            'data' => $data,
        ]);

        return [
            'sync_id' => $syncId,
            'status' => 'pending',
        ];
    }

    /**
     * Get sync status
     */
    public function getSyncStatus(string $syncId): array
    {
        $status = Cache::get("erp_sync_status_{$syncId}");

        if (!$status) {
            return [
                'status' => 'not_found',
                'message' => 'Sync ID not found',
            ];
        }

        return $status;
    }

    /**
     * Test ERP connection
     */
    public function testConnection(): array
    {
        try {
            // Attempt to connect to ERP system
            $connected = $this->repository->testConnection();

            return [
                'connected' => $connected,
                'message' => $connected ? 'Connection successful' : 'Connection failed',
                'timestamp' => now(),
            ];
        } catch (\Exception $e) {
            Log::error('ERP connection test failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'connected' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
                'timestamp' => now(),
            ];
        }
    }

    /**
     * Update sync status
     */
    public function updateSyncStatus(string $syncId, string $status, array $data = []): void
    {
        $currentStatus = Cache::get("erp_sync_status_{$syncId}", []);

        Cache::put("erp_sync_status_{$syncId}", array_merge($currentStatus, [
            'status' => $status,
            'updated_at' => now(),
        ] + $data), now()->addHours(24));
    }
}

