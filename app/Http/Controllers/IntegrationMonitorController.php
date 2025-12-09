<?php

namespace App\Http\Controllers;

use App\Modules\ERPIntegration\Services\ERPIntegrationService;
use App\Modules\ERPIntegration\Services\SapConnectorFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class IntegrationMonitorController extends Controller
{
    public function __construct(
        protected ERPIntegrationService $erpService
    ) {}

    /**
     * Display the integration monitor
     */
    public function index(Request $request): Response
    {
        // Get integrations list
        $integrations = $this->erpService->listIntegrations();

        // Get sync history from cache
        $syncHistory = $this->getSyncHistory();

        return Inertia::render('IntegrationMonitor', [
            'integrations' => $integrations,
            'syncHistory' => $syncHistory,
        ]);
    }

    /**
     * Get sync history from cache
     */
    protected function getSyncHistory(): array
    {
        $history = [];
        $keys = Cache::get('erp_sync_keys', []);

        foreach ($keys as $syncId) {
            $status = Cache::get("erp_sync_status_{$syncId}");
            if ($status) {
                $history[] = [
                    'sync_id' => $syncId,
                    'type' => $status['data']['type'] ?? 'unknown',
                    'status' => $status['status'] ?? 'pending',
                    'records_synced' => $status['records_synced'] ?? 0,
                    'started_at' => $status['started_at'] ?? null,
                    'completed_at' => $status['completed_at'] ?? null,
                ];
            }
        }

        // Sort by started_at descending
        usort($history, fn($a, $b) => strtotime($b['started_at'] ?? '') - strtotime($a['started_at'] ?? ''));

        return array_slice($history, 0, 50); // Last 50 syncs
    }
}

