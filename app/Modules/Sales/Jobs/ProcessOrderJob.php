<?php

namespace App\Modules\Sales\Jobs;

use App\Modules\Sales\Repositories\SalesRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessOrderJob implements ShouldQueue
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
        public string $orderId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SalesRepositoryInterface $repository): void
    {
        try {
            Log::info('Processing order', [
                'order_id' => $this->orderId,
            ]);

            $order = $repository->find($this->orderId);

            if (!$order) {
                throw new \Exception("Order not found: {$this->orderId}");
            }

            // Process order logic here
            // e.g., update inventory, send notifications, etc.

            Log::info('Order processed successfully', [
                'order_id' => $this->orderId,
            ]);
        } catch (\Exception $e) {
            Log::error('Order processing failed', [
                'order_id' => $this->orderId,
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
        Log::error('Order processing job failed permanently', [
            'order_id' => $this->orderId,
            'error' => $exception->getMessage(),
        ]);
    }
}

