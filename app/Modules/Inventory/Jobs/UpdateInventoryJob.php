<?php

namespace App\Modules\Inventory\Jobs;

use App\Modules\Inventory\Repositories\InventoryRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateInventoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $productId,
        public array $data
    ) {}

    /**
     * Execute the job.
     */
    public function handle(InventoryRepositoryInterface $repository): void
    {
        try {
            Log::info('Updating inventory', [
                'product_id' => $this->productId,
                'data' => $this->data,
            ]);

            // Additional inventory processing logic
            // e.g., update related records, send notifications, etc.

            Log::info('Inventory updated successfully', [
                'product_id' => $this->productId,
            ]);
        } catch (\Exception $e) {
            Log::error('Inventory update failed', [
                'product_id' => $this->productId,
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
        Log::error('Inventory update job failed permanently', [
            'product_id' => $this->productId,
            'error' => $exception->getMessage(),
        ]);
    }
}

