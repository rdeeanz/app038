<?php

namespace App\Modules\Sales\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sales\Requests\CreateOrderRequest;
use App\Modules\Sales\Requests\UpdateOrderRequest;
use App\Modules\Sales\Services\SalesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalesController extends Controller
{
    public function __construct(
        protected SalesService $salesService
    ) {}

    /**
     * Display a listing of orders
     */
    public function index(Request $request): JsonResponse
    {
        $orders = $this->salesService->listOrders($request->all());

        return response()->json([
            'data' => $orders,
        ]);
    }

    /**
     * Store a newly created order
     */
    public function store(CreateOrderRequest $request): JsonResponse
    {
        $order = $this->salesService->createOrder($request->validated());

        return response()->json([
            'message' => 'Order created successfully',
            'data' => $order,
        ], 201);
    }

    /**
     * Display the specified order
     */
    public function show(string $id): JsonResponse
    {
        $order = $this->salesService->getOrder($id);

        return response()->json([
            'data' => $order,
        ]);
    }

    /**
     * Update the specified order
     */
    public function update(UpdateOrderRequest $request, string $id): JsonResponse
    {
        $order = $this->salesService->updateOrder($id, $request->validated());

        return response()->json([
            'message' => 'Order updated successfully',
            'data' => $order,
        ]);
    }

    /**
     * Remove the specified order
     */
    public function destroy(string $id): JsonResponse
    {
        $this->salesService->deleteOrder($id);

        return response()->json([
            'message' => 'Order deleted successfully',
        ]);
    }

    /**
     * Get sales statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $stats = $this->salesService->getStatistics($request->all());

        return response()->json([
            'data' => $stats,
        ]);
    }
}

