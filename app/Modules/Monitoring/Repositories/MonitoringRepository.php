<?php

namespace App\Modules\Monitoring\Repositories;

use Illuminate\Support\Facades\File;

class MonitoringRepository implements MonitoringRepositoryInterface
{
    /**
     * Get application logs
     */
    public function getLogs(array $filters = []): array
    {
        $logPath = storage_path('logs/laravel.log');

        if (!File::exists($logPath)) {
            return [];
        }

        $lines = File::lines($logPath);
        $limit = $filters['limit'] ?? 100;

        return $lines->take($limit)->toArray();
    }
}

