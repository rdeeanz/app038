<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Requests\CreateProductRequest;
use App\Modules\Inventory\Requests\UpdateStockRequest;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Display a listing of products
     */
    public function index(Request $request): JsonResponse
    {
        $products = $this->inventoryService->listProducts($request->all());

        return response()->json([
            'data' => $products,
        ]);
    }

    /**
     * Store a newly created product
     */
    public function store(CreateProductRequest $request): JsonResponse
    {
        $product = $this->inventoryService->createProduct($request->validated());

        return response()->json([
            'message' => 'Product created successfully',
            'data' => $product,
        ], 201);
    }

    /**
     * Display the specified product
     */
    public function show(string $id): JsonResponse
    {
        $product = $this->inventoryService->getProduct($id);

        return response()->json([
            'data' => $product,
        ]);
    }

    /**
     * Update stock levels
     */
    public function updateStock(UpdateStockRequest $request, string $id): JsonResponse
    {
        $product = $this->inventoryService->updateStock($id, $request->validated());

        return response()->json([
            'message' => 'Stock updated successfully',
            'data' => $product,
        ]);
    }

    /**
     * Get low stock alerts
     */
    public function lowStock(Request $request): JsonResponse
    {
        $alerts = $this->inventoryService->getLowStockAlerts($request->all());

        return response()->json([
            'data' => $alerts,
        ]);
    }

    /**
     * Get inventory statistics
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->inventoryService->getStatistics();

        return response()->json([
            'data' => $stats,
        ]);
    }
}

