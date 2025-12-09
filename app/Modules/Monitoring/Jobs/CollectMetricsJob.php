<?php

namespace App\Modules\Monitoring\Jobs;

use App\Modules\Monitoring\Services\MonitoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CollectMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Execute the job.
     */
    public function handle(MonitoringService $monitoringService): void
    {
        try {
            $metrics = $monitoringService->getMetrics([]);

            // Store metrics in cache
            Cache::put('system_metrics', $metrics, now()->addMinutes(5));

            Log::debug('Metrics collected', [
                'timestamp' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to collect metrics', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

