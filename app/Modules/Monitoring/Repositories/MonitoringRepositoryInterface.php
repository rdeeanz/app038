<?php

namespace App\Modules\Monitoring\Repositories;

interface MonitoringRepositoryInterface
{
    /**
     * Get application logs
     */
    public function getLogs(array $filters = []): array;
}

