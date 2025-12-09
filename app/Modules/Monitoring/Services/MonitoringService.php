<?php

namespace App\Modules\Monitoring\Services;

use App\Modules\Monitoring\Jobs\CollectMetricsJob;
use App\Modules\Monitoring\Repositories\MonitoringRepositoryInterface;
use App\Services\ConnectionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class MonitoringService
{
    public function __construct(
        protected MonitoringRepositoryInterface $repository
    ) {}

    /**
     * Get system health status
     */
    public function getHealthStatus(): array
    {
        $connections = ConnectionService::testAllConnections();

        return [
            'status' => collect($connections)->every(fn ($status) => $status === true) ? 'healthy' : 'degraded',
            'timestamp' => now(),
            'connections' => $connections,
            'uptime' => $this->getUptime(),
        ];
    }

    /**
     * Get system metrics
     */
    public function getMetrics(array $filters = []): array
    {
        // Dispatch job to collect metrics asynchronously
        CollectMetricsJob::dispatch()
            ->onQueue('monitoring');

        return Cache::remember('system_metrics', 60, function () {
            return [
                'memory_usage' => $this->getMemoryUsage(),
                'cpu_usage' => $this->getCpuUsage(),
                'disk_usage' => $this->getDiskUsage(),
                'active_connections' => $this->getActiveConnections(),
                'timestamp' => now(),
            ];
        });
    }

    /**
     * Get application logs
     */
    public function getLogs(array $filters = []): array
    {
        return $this->repository->getLogs($filters);
    }

    /**
     * Get queue status
     */
    public function getQueueStatus(): array
    {
        return [
            'pending' => Queue::size(),
            'failed' => DB::table('failed_jobs')->count(),
            'connections' => config('queue.connections'),
        ];
    }

    /**
     * Get database status
     */
    public function getDatabaseStatus(): array
    {
        try {
            $connection = ConnectionService::getDatabaseConnection();
            $status = 'connected';

            return [
                'status' => $status,
                'connection' => $connection->getName(),
                'timestamp' => now(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'disconnected',
                'error' => $e->getMessage(),
                'timestamp' => now(),
            ];
        }
    }

    /**
     * Get system uptime
     */
    protected function getUptime(): int
    {
        return time() - (Cache::get('system_start_time', time()));
    }

    /**
     * Get memory usage
     */
    protected function getMemoryUsage(): array
    {
        return [
            'used' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => ini_get('memory_limit'),
        ];
    }

    /**
     * Get CPU usage (simplified)
     */
    protected function getCpuUsage(): array
    {
        return [
            'load' => sys_getloadavg(),
        ];
    }

    /**
     * Get disk usage
     */
    protected function getDiskUsage(): array
    {
        $total = disk_total_space('/');
        $free = disk_free_space('/');

        return [
            'total' => $total,
            'free' => $free,
            'used' => $total - $free,
            'percentage' => $total > 0 ? (($total - $free) / $total) * 100 : 0,
        ];
    }

    /**
     * Get active connections
     */
    protected function getActiveConnections(): int
    {
        try {
            return DB::table('information_schema.processlist')
                ->where('db', config('database.connections.pgsql.database'))
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }
}

