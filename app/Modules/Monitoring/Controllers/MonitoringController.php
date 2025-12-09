<?php

namespace App\Modules\Monitoring\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Monitoring\Services\MonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonitoringController extends Controller
{
    public function __construct(
        protected MonitoringService $monitoringService
    ) {}

    /**
     * Get system health status
     */
    public function health(): JsonResponse
    {
        $health = $this->monitoringService->getHealthStatus();

        return response()->json([
            'data' => $health,
        ]);
    }

    /**
     * Get system metrics
     */
    public function metrics(Request $request): JsonResponse
    {
        $metrics = $this->monitoringService->getMetrics($request->all());

        return response()->json([
            'data' => $metrics,
        ]);
    }

    /**
     * Get application logs
     */
    public function logs(Request $request): JsonResponse
    {
        $logs = $this->monitoringService->getLogs($request->all());

        return response()->json([
            'data' => $logs,
        ]);
    }

    /**
     * Get queue status
     */
    public function queueStatus(): JsonResponse
    {
        $status = $this->monitoringService->getQueueStatus();

        return response()->json([
            'data' => $status,
        ]);
    }

    /**
     * Get database status
     */
    public function databaseStatus(): JsonResponse
    {
        $status = $this->monitoringService->getDatabaseStatus();

        return response()->json([
            'data' => $status,
        ]);
    }
}

