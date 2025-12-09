<?php

namespace App\Modules\ERPIntegration\Jobs;

use App\Modules\ERPIntegration\Services\ERPIntegrationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncERPDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $syncId,
        public array $data
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ERPIntegrationService $erpService): void
    {
        try {
            $erpService->updateSyncStatus($this->syncId, 'processing');

            Log::info('Starting ERP sync', [
                'sync_id' => $this->syncId,
            ]);

            // Simulate sync process
            // In real implementation, this would call the repository methods
            sleep(2);

            $erpService->updateSyncStatus($this->syncId, 'completed', [
                'completed_at' => now(),
                'records_synced' => count($this->data),
            ]);

            Log::info('ERP sync completed', [
                'sync_id' => $this->syncId,
            ]);
        } catch (\Exception $e) {
            $erpService->updateSyncStatus($this->syncId, 'failed', [
                'error' => $e->getMessage(),
                'failed_at' => now(),
            ]);

            Log::error('ERP sync failed', [
                'sync_id' => $this->syncId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ERP sync job failed permanently', [
            'sync_id' => $this->syncId,
            'error' => $exception->getMessage(),
        ]);
    }
}

