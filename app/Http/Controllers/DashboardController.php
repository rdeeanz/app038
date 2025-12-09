<?php

namespace App\Http\Controllers;

use App\Modules\Sales\Services\SalesService;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\ERPIntegration\Services\SapConnectorFactory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected SalesService $salesService,
        protected InventoryService $inventoryService
    ) {}

    /**
     * Display the dashboard
     */
    public function index(Request $request): Response
    {
        // Get statistics
        $stats = [
            'total_orders' => $this->salesService->getStatistics()['total_orders'] ?? 0,
            'total_revenue' => $this->salesService->getStatistics()['total_revenue'] ?? 0,
            'active_integrations' => 1, // Placeholder
            'low_stock_count' => count($this->inventoryService->getLowStockAlerts()),
        ];

        // Get recent orders
        $recentOrders = $this->salesService->listOrders([
            'limit' => 10,
        ]);

        // Get integration status
        $integrationStatus = SapConnectorFactory::testAllConnections();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'integrationStatus' => $integrationStatus,
        ]);
    }

    /**
     * Get dashboard data via API (for Axios requests)
     */
    public function data(Request $request)
    {
        $stats = [
            'total_orders' => $this->salesService->getStatistics()['total_orders'] ?? 0,
            'total_revenue' => $this->salesService->getStatistics()['total_revenue'] ?? 0,
            'active_integrations' => 1,
            'low_stock_count' => count($this->inventoryService->getLowStockAlerts()),
        ];

        return response()->json([
            'stats' => $stats,
        ]);
    }
}

