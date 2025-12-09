<?php

namespace App\Modules\ERPIntegration\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ERPIntegration\Requests\SyncRequest;
use App\Modules\ERPIntegration\Services\ERPIntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ERPIntegrationController extends Controller
{
    public function __construct(
        protected ERPIntegrationService $erpService
    ) {}

    /**
     * Display a listing of ERP integrations
     */
    public function index(Request $request): JsonResponse
    {
        $integrations = $this->erpService->listIntegrations($request->all());

        return response()->json([
            'data' => $integrations,
        ]);
    }

    /**
     * Sync data with ERP system
     */
    public function sync(SyncRequest $request): JsonResponse
    {
        $result = $this->erpService->syncData($request->validated());

        return response()->json([
            'message' => 'Sync initiated successfully',
            'data' => $result,
        ], 202);
    }

    /**
     * Get sync status
     */
    public function syncStatus(string $syncId): JsonResponse
    {
        $status = $this->erpService->getSyncStatus($syncId);

        return response()->json([
            'data' => $status,
        ]);
    }

    /**
     * Test ERP connection
     */
    public function testConnection(Request $request): JsonResponse
    {
        $integrationId = $request->input('integration_id');
        $result = $this->erpService->testConnection($integrationId);

        return response()->json([
            'connected' => $result['connected'] ?? false,
            'message' => $result['message'] ?? 'Connection test completed',
        ]);
    }
}

