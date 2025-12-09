<?php

namespace App\Modules\Sales\Services;

use App\Modules\Sales\Jobs\ProcessOrderJob;
use App\Modules\Sales\Repositories\SalesRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalesService
{
    public function __construct(
        protected SalesRepositoryInterface $repository
    ) {}

    /**
     * List orders with filters
     */
    public function listOrders(array $filters = []): array
    {
        return $this->repository->list($filters);
    }

    /**
     * Create a new order
     */
    public function createOrder(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $order = $this->repository->create($data);

            // Dispatch job to process order asynchronously
            ProcessOrderJob::dispatch($order['id'])
                ->onQueue('sales');

            Log::info('Order created', [
                'order_id' => $order['id'],
            ]);

            return $order;
        });
    }

    /**
     * Get order by ID
     */
    public function getOrder(string $id): array
    {
        $order = $this->repository->find($id);

        if (!$order) {
            throw new \Exception("Order not found: {$id}");
        }

        return $order;
    }

    /**
     * Update order
     */
    public function updateOrder(string $id, array $data): array
    {
        return DB::transaction(function () use ($id, $data) {
            $order = $this->repository->update($id, $data);

            Log::info('Order updated', [
                'order_id' => $id,
            ]);

            return $order;
        });
    }

    /**
     * Delete order
     */
    public function deleteOrder(string $id): void
    {
        DB::transaction(function () use ($id) {
            $this->repository->delete($id);

            Log::info('Order deleted', [
                'order_id' => $id,
            ]);
        });
    }

    /**
     * Get sales statistics
     */
    public function getStatistics(array $filters = []): array
    {
        return $this->repository->getStatistics($filters);
    }
}

